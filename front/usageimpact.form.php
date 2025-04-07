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
    if (!isset($_POST['id'])) {
        Session::addMessageAfterRedirect(__('Missing arguments in request.', 'carbon'), false, ERROR);
        Html::back();
    }
    $usage_impact = new UsageImpact();
    $usage_impact->check($_POST['id'], PURGE);

    $gwp_impact_class = '\\GlpiPlugin\\Carbon\\Impact\\History\\' . $usage_impact->fields['itemtype'];
    if (!class_exists($gwp_impact_class) || !is_subclass_of($gwp_impact_class, AbstractAsset::class)) {
        Session::addMessageAfterRedirect(__('Bad arguments.', 'carbon'), false, ERROR);
        Html::back();
    }

    /** @var AbstractAsset $history */
    $gwp_impact = new $gwp_impact_class();
    $itemtype = $gwp_impact->getItemtype();
    $item = new $itemtype();
    $item->getFromDB($usage_impact->fields['items_id']);
    if (!$item->canUpdateItem()) {
        Session::addMessageAfterRedirect(__('Reset denied.', 'carbon'), false, ERROR);
        Html::back();
    }

    if (!$gwp_impact->resetForItem($usage_impact->fields['items_id'])) {
        Session::addMessageAfterRedirect(__('Reset failed.', 'carbon'), false, ERROR);
    }

    if (!$usage_impact->delete($usage_impact->fields)) {
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
