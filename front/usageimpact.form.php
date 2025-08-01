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

use Glpi\Event;
use GlpiPlugin\Carbon\UsageInfo;
use GlpiPlugin\Carbon\Impact\History\AbstractAsset;
use GlpiPlugin\Carbon\Impact\Usage\Engine;
use GlpiPlugin\Carbon\UsageImpact;

include('../../../inc/includes.php');

// Check if plugin is activated...
if (!Plugin::isPluginActive('carbon')) {
    Html::displayNotFoundError();
}

Session::checkRight(UsageInfo::$rightname, READ);


if (isset($_POST['update'])) {
    $usage_info = new UsageInfo();
    $usage_info->check($_POST['id'], UPDATE);
    $usage_info->update($_POST);
    Event::log(
        $_POST['id'],
        strtolower($usage_info->fields['itemtype']),
        4,
        'inventory',
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION['glpiname'])
    );
    Html::back();
} else if (isset($_POST['reset'])) {
    if (!isset($_POST['itemtype']) || !isset($_POST['items_id'])) {
        Session::addMessageAfterRedirect(__('Missing arguments in request.', 'carbon'), false, ERROR);
        Html::back();
    }
    $usage_impact = new UsageImpact();
    $usage_impact->getFromDBByCrit([
        'itemtype' => $_POST['itemtype'],
        'items_id' => $_POST['items_id'],
    ]);
    if (!$usage_impact->isNewItem()) {
        $usage_impact->check($usage_impact->getID(), PURGE);
    }

    $gwp_impact_class = '\\GlpiPlugin\\Carbon\\Impact\\History\\' . $_POST['itemtype'];
    if (!class_exists($gwp_impact_class) || !is_subclass_of($gwp_impact_class, AbstractAsset::class)) {
        Session::addMessageAfterRedirect(__('Bad arguments.', 'carbon'), false, ERROR);
        Html::back();
    }

    /** @var AbstractAsset $history */
    $gwp_impact = new $gwp_impact_class();
    $itemtype = $gwp_impact->getItemtype();
    $item = new $itemtype();
    $item->getFromDB($_POST['items_id']);
    if (!$item->canUpdateItem()) {
        Session::addMessageAfterRedirect(__('Reset denied.', 'carbon'), false, ERROR);
        Html::back();
    }

    if (!$gwp_impact->resetForItem($item->getID())) {
        Session::addMessageAfterRedirect(__('Reset failed.', 'carbon'), false, ERROR);
    }

    if (!$usage_impact->isNewItem() && !$usage_impact->delete($usage_impact->fields)) {
        Session::addMessageAfterRedirect(__('Delete of usage impact failed.', 'carbon'), false, ERROR);
    }
} else if (isset($_POST['calculate'])) {
    if (!isset($_POST['itemtype']) || !isset($_POST['items_id'])) {
        Session::addMessageAfterRedirect(__('Missing arguments in request.', 'carbon'), false, ERROR);
        Html::back();
    }

    $itemtype = $_POST['itemtype'];
    if (!Toolbox::isCommonDBTM($itemtype)) {
        Session::addMessageAfterRedirect(__('Bad arguments.', 'carbon'), false, ERROR);
        Html::back();
    }
    $item = new $itemtype();
    $item->check($_POST['items_id'], UPDATE);

    $gwp_impact_class = '\\GlpiPlugin\\Carbon\\Impact\\History\\' . (string) $itemtype;
    if (!class_exists($gwp_impact_class) || !is_subclass_of($gwp_impact_class, AbstractAsset::class)) {
        Session::addMessageAfterRedirect(__('Bad arguments.', 'carbon'), false, ERROR);
        Html::back();
    }

    /** @var AbstractAsset $gwp_impact */
    $gwp_impact = new $gwp_impact_class();

    if (!$gwp_impact->canHistorize($_POST['items_id'])) {
        Session::addMessageAfterRedirect(__('Missing data prevents historization of this asset.', 'carbon'), false, ERROR);
    } else {
        if (!$gwp_impact->calculateImpact($_POST['items_id'])) {
            Session::addMessageAfterRedirect(__('Update of global warming potential failed.', 'carbon'), false, ERROR);
        }
    }

    $usage_impact = Engine::getEngineFromItemtype($_POST['itemtype']);
    if ($usage_impact === null) {
        Session::addMessageAfterRedirect(__('Unable to find calculation engine for this asset.', 'carbon'), false, ERROR);
        Html::back();
    }

    if (!$usage_impact->evaluateItem($_POST['items_id'])) {
        Session::addMessageAfterRedirect(__('Update of usage impact failed.', 'carbon'), false, ERROR);
    }
}

Html::back();
