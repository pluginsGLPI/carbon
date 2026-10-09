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
use GlpiPlugin\Carbon\EmbodiedImpact;
use GlpiPlugin\Carbon\Impact\Embodied\Engine;
use Html;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EmbodiedImpactController extends AbstractController
{
    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    #[Route(
        path: 'front/embodiedimpact.form.php',
        name: 'embodiedimpact',
        methods: ['GET', 'POST']
    )]
    public function handle(Request $request): Response
    {
        Session::checkRight(EmbodiedImpact::$rightname, READ);

        // if method is GET, then throw an exception, workaround bug in GLPI up to 11.0.7
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
                throw new Exception('Method not allowed');
        }
    }

    private function update(Request $request): Response
    {
        $input = $request->request->all();
        $id = $request->request->getInt('id', -1);
        $embodied_impact = new EmbodiedImpact();
        $embodied_impact->check($id, UPDATE, $input);
        $embodied_impact->update($input);

        Event::log(
            $id,
            strtolower($embodied_impact->fields['itemtype']),
            4,
            'inventory',
            //TRANS: %s is the user login
            sprintf(__('%s updates an item'), $_SESSION['glpiname'])
        );

        return new RedirectResponse(Html::getBackUrl());
    }

    private function resetAll(): Response
    {
        $embodied_impact = new EmbodiedImpact();
        if ($embodied_impact->truncate()) {
            Session::addMessageAfterRedirect(__('All embodied impact data has been reset.', 'carbon'), false, INFO);
        } else {
            Session::addMessageAfterRedirect(__('Reset failed.', 'carbon'), false, ERROR);
        }

        return new RedirectResponse(Html::getBackUrl());
    }

    private function reset(Request $request): Response
    {
        if (!$request->request->has('id')) {
            Session::addMessageAfterRedirect(__('Missing arguments in request.', 'carbon'), false, ERROR);
            return new RedirectResponse(Html::getBackUrl());
        }

        if (!EmbodiedImpact::canPurge()) {
            Session::addMessageAfterRedirect(__('Reset denied.', 'carbon'), false, ERROR);
            return new RedirectResponse(Html::getBackUrl());
        }

        $embodied_impact = new EmbodiedImpact();
        $embodied_impact->check($request->request->getInt('id'), PURGE);

        $itemtype = $embodied_impact->fields['itemtype'];
        if (!is_a($itemtype, CommonDBTM::class, true)) {
            Session::addMessageAfterRedirect(__('Bad arguments.', 'carbon'), false, ERROR);
            return new RedirectResponse(Html::getBackUrl());
        }

        $item = new $itemtype();
        $item->check((int) $embodied_impact->fields['items_id'], UPDATE);

        if (!$embodied_impact->delete($embodied_impact->fields)) {
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
        $item->check($request->request->getInt('items_id'), UPDATE);

        $engine = Engine::getEngineFromItemtype($item);
        if ($engine === null) {
            Session::addMessageAfterRedirect(__('Unable to find calculation engine for this asset.', 'carbon'), false, ERROR);
            return new RedirectResponse(Html::getBackUrl());
        }

        $engine->evaluateItem();

        return new RedirectResponse(Html::getBackUrl());
    }
}
