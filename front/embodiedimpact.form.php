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
use Glpi\Exception\Http\NotFoundHttpException;
use GlpiPlugin\Carbon\Config;
use GlpiPlugin\Carbon\EmbodiedImpact;
use GlpiPlugin\Carbon\Impact\Embodied\AbstractEmbodiedImpact;
use GlpiPlugin\Carbon\Impact\Embodied\Engine;

include(__DIR__ . '/../../../inc/includes.php');

// Check if plugin is activated...
if (!Plugin::isPluginActive('carbon')) {
    throw new NotFoundHttpException();
}

Session::checkRight(EmbodiedImpact::$rightname, READ);

$embodied_impact = new EmbodiedImpact();

if (isset($_POST['update'])) {
    $embodied_impact->check($_POST['id'], UPDATE);
    $embodied_impact->update($_POST);
    Event::log(
        $_POST['id'],
        strtolower($embodied_impact->fields['itemtype']),
        4,
        'inventory',
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION['glpiname'])
    );
    Html::back();
} else if (isset($_POST['reset'])) {
    if (!isset($_POST['id'])) {
        Session::addMessageAfterRedirect(__('Missing arguments in request.', 'carbon'), false, ERROR);
        Html::back();
    }

    if (!EmbodiedImpact::canPurge()) {
        Session::addMessageAfterRedirect(__('Reset denied.', 'carbon'), false, ERROR);
        Html::back();
    }
    $embodied_impact->check($_POST['id'], PURGE);

    $itemtype = $embodied_impact->fields['itemtype'];
    $item = new $itemtype();
    $item->getFromDB($embodied_impact->fields['items_id']);
    if (!$item->canUpdateItem()) {
        Session::addMessageAfterRedirect(__('Reset denied.', 'carbon'), false, ERROR);
        Html::back();
    }

    if (!$embodied_impact->delete($embodied_impact->fields)) {
        Session::addMessageAfterRedirect(__('Reset failed.', 'carbon'), false, ERROR);
    }
} else if (isset($_POST['calculate'])) {
    if (!isset($_POST['itemtype']) || !isset($_POST['items_id'])) {
        Session::addMessageAfterRedirect(__('Missing arguments in request.', 'carbon'), false, ERROR);
        Html::back();
    }

    $embodied_impact = Engine::getEngineFromItemtype($_POST['itemtype']);
    if ($embodied_impact === null) {
        Session::addMessageAfterRedirect(__('Unable to find calculation engine for this asset.', 'carbon'), false, ERROR);
        Html::back();
    }

    $itemtype = $embodied_impact::getItemtype();
    $item = new $itemtype();
    $item->check($_POST['items_id'], UPDATE);

    if (!$embodied_impact->evaluateItem($_POST['items_id'])) {
        Session::addMessageAfterRedirect(__('Update failed.', 'carbon'), false, ERROR);
    }
}

Html::back();
