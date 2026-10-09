<?php

/**
 * -------------------------------------------------------------------------
 * Carbon plugin for GLPI
 *
 * @copyright Copyright (C) 2024-2025 Teclib' and contributors.
 * @license   https://www.gnu.org/licenses/gpl-3.0.txt GPLv3+
 * @link      https://github.com/pluginsGLPI/carbon
 *
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Carbon plugin for GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Carbon\Controller;

use CommonDBTM;
use Exception;
use Glpi\Controller\AbstractController;
use Glpi\Event;
use Glpi\Http\Firewall;
use Glpi\Http\RedirectResponse;
use Glpi\Security\Attribute\SecurityStrategy;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\CommonAsset;
use GlpiPlugin\Carbon\Impact\History\AbstractAsset;
use GlpiPlugin\Carbon\Impact\Usage\Engine;
use GlpiPlugin\Carbon\UsageImpact;
use GlpiPlugin\Carbon\UsageInfo;
use Html;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UsageImpactController extends AbstractController
{
    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    #[Route(
        path: 'front/usageimpact.form.php',
        name: 'usageimpact',
        methods: ['GET', 'POST']
    )]
    public function handle(Request $request): Response
    {
        Session::checkRight(UsageInfo::$rightname, READ);

        if ($request->isMethod('GET')) {
            return new Response('', 403);
        }

        switch (true) {
            case $request->request->has('update'):
                return $this->update($request);
            case $request->request->has('reset_all'):
                return $this->resetAll();
            case $request->request->has('reset'):
                return $this->reset($request);
            case $request->request->has('calculate'):
                return $this->calculate($request);
            default:
                throw new Exception('Method not allowed', 405);
        }
    }

    private function update(Request $request): Response
    {
        $input = $request->request->all();
        $id = $request->request->getInt('id', -1);
        $usage_info = new UsageInfo();
        $usage_info->check($id, UPDATE, $input);
        $usage_info->update($input);

        Event::log(
            $id,
            strtolower($usage_info->fields['itemtype']),
            4,
            'inventory',
            //TRANS: %s is the user login
            sprintf(__('%s updates an item'), $_SESSION['glpiname'])
        );

        return new RedirectResponse(Html::getBackUrl());
    }

    private function resetAll(): Response
    {
        $usage_impact = new UsageImpact();
        $usage_ghg_impact = new CarbonEmission();
        $usage_impact_truncated = $usage_impact->truncate();
        $usage_ghg_impact_truncated = $usage_ghg_impact->truncate();
        if (!$usage_impact_truncated || !$usage_ghg_impact_truncated) {
            Session::addMessageAfterRedirect(__('Reset failed.', 'carbon'), false, ERROR);
        }

        return new RedirectResponse(Html::getBackUrl());
    }

    private function reset(Request $request): Response
    {
        if (!$request->request->has('itemtype') || !$request->request->has('items_id')) {
            Session::addMessageAfterRedirect(__('Missing arguments in request.', 'carbon'), false, ERROR);
            return new RedirectResponse(Html::getBackUrl());
        }

        $itemtype = $request->request->getString('itemtype');
        $items_id = $request->request->getInt('items_id');

        $usage_impact = new UsageImpact();
        $usage_impact->getFromDBByCrit([
            'itemtype' => $itemtype,
            'items_id' => $items_id,
        ]);
        if (!$usage_impact->isNewItem()) {
            $usage_impact->check($usage_impact->getID(), PURGE);
        }

        $gwp_impact_class = '\\GlpiPlugin\\Carbon\\Impact\\History\\' . $itemtype;
        if (!class_exists($gwp_impact_class) || !is_subclass_of($gwp_impact_class, AbstractAsset::class)) {
            Session::addMessageAfterRedirect(__('Bad arguments.', 'carbon'), false, ERROR);
            return new RedirectResponse(Html::getBackUrl());
        }

        $history = new $gwp_impact_class();
        $asset_itemtype = $history->getItemtype();
        if (!is_a($asset_itemtype, CommonDBTM::class, true)) {
            Session::addMessageAfterRedirect(__('Bad arguments.', 'carbon'), false, ERROR);
            return new RedirectResponse(Html::getBackUrl());
        }

        $item = new $asset_itemtype();
        $item->check($items_id, UPDATE);

        if (!CommonAsset::deleteUsageImpact($item)) {
            Session::addMessageAfterRedirect(__('Reset failed.', 'carbon'), false, ERROR);
        }

        return new RedirectResponse(Html::getBackUrl());
    }

    private function calculate(Request $request): Response
    {
        if (!$request->request->has('itemtype') || !$request->request->has('items_id')) {
            Session::addMessageAfterRedirect(__('Missing arguments in request.', 'carbon'), false, ERROR);
            return new RedirectResponse(Html::getBackUrl());
        }

        $itemtype = $request->request->getString('itemtype');
        if (!is_a($itemtype, CommonDBTM::class, true)) {
            Session::addMessageAfterRedirect(__('Bad arguments.', 'carbon'), false, ERROR);
            return new RedirectResponse(Html::getBackUrl());
        }

        $item = new $itemtype();
        $items_id = $request->request->getInt('items_id');
        $item->check($items_id, UPDATE);

        $history_class = '\\GlpiPlugin\\Carbon\\Impact\\History\\' . $itemtype;
        if (!class_exists($history_class) || !is_subclass_of($history_class, AbstractAsset::class)) {
            Session::addMessageAfterRedirect(__('Bad arguments.', 'carbon'), false, ERROR);
            return new RedirectResponse(Html::getBackUrl());
        }

        $history = new $history_class();
        if ($history->getItemsToEvaluate([$item::getTableField('id') => $items_id])->count() !== 1) {
            Session::addMessageAfterRedirect(__('Missing data prevents historization of this asset.', 'carbon'), false, ERROR);
        } elseif (!$history->calculateImpact($items_id)) {
            Session::addMessageAfterRedirect(__('Update of global warming potential failed.', 'carbon'), false, ERROR);
        }

        $usage_impact = Engine::getEngineFromItemtype($item);
        if ($usage_impact === null) {
            Session::addMessageAfterRedirect(__('Unable to find calculation engine for this asset.', 'carbon'), false, ERROR);
            return new RedirectResponse(Html::getBackUrl());
        }

        if (!$usage_impact->evaluateItem()) {
            Session::addMessageAfterRedirect(__('Update of usage impact failed.', 'carbon'), false, ERROR);
        }

        return new RedirectResponse(Html::getBackUrl());
    }
}
