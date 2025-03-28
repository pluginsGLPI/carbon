<?php

/**
 * -------------------------------------------------------------------------
 * carbon plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2024 Teclib' and contributors.
 * @license   MIT https://opensource.org/licenses/mit-license.php
 * @link      https://github.com/pluginsGLPI/carbon
 * -------------------------------------------------------------------------
 */

use Glpi\Event;
use GlpiPlugin\Carbon\Config;
use GlpiPlugin\Carbon\EmbodiedImpact;
use GlpiPlugin\Carbon\Impact\Embodied\AbstractEmbodiedImpact;
use GlpiPlugin\Carbon\Impact\Embodied\Engine;

include('../../../inc/includes.php');

// Check if plugin is activated...
if (!Plugin::isPluginActive('carbon')) {
    Html::displayNotFoundError();
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
