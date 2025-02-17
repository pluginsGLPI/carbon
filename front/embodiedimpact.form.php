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
use GlpiPlugin\Carbon\DataSource\Boaviztapi;
use GlpiPlugin\Carbon\DataSource\RestApiClient;
use GlpiPlugin\Carbon\EmbodiedImpact;
use GlpiPlugin\Carbon\Impact\Embedded\AbstractEmbeddedImpact;
use GlpiPlugin\Carbon\Impact\Embedded\Boavizta\AbstractAsset;

include('../../../inc/includes.php');

// Check if plugin is activated...
if (!Plugin::isPluginActive('carbon')) {
    Html::displayNotFoundError();
}

Session::checkRight(EmbodiedImpact::$rightname, READ);

$environmental_impact = new EmbodiedImpact();

if (isset($_POST['update'])) {
    $environmental_impact->check($_POST['id'], UPDATE);
    $environmental_impact->update($_POST);
    Event::log(
        $_POST['id'],
        'computers',
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

    if (!EmbodiedImpact::canPurge()) {
        Session::addMessageAfterRedirect(__('Reset denied.', 'carbon'), false, ERROR);
        Html::back();
    }

    $embodied_impact_namespace = Config::getEmbeddedImpactEngine();
    $embodied_impact_class = $embodied_impact_namespace . '\\' . (string) $_POST['itemtype'];
    if (!class_exists($embodied_impact_class) || !is_subclass_of($embodied_impact_class, AbstractEmbeddedImpact::class)) {
        Session::addMessageAfterRedirect(__('Bad arguments.', 'carbon'), false, ERROR);
        Html::back();
    }

    $embodied_impact = new $embodied_impact_class();
    $itemtype = $embodied_impact->getItemtype();
    $item = new $itemtype();
    $item->getFromDB($_POST['items_id']);
    if (!$item->canUpdate()) {
        Session::addMessageAfterRedirect(__('Reset denied.', 'carbon'), false, ERROR);
        Html::back();
    }

    if (!$embodied_impact->resetImpact($_POST['items_id'])) {
        Session::addMessageAfterRedirect(__('Reset failed.', 'carbon'), false, ERROR);
    }
} else if (isset($_POST['calculate'])) {
    if (!isset($_POST['itemtype']) || !isset($_POST['items_id'])) {
        Session::addMessageAfterRedirect(__('Missing arguments in request.', 'carbon'), false, ERROR);
        Html::back();
    }

    if (!EmbodiedImpact::canUpdate()) {
        Session::addMessageAfterRedirect(__('Update denied.', 'carbon'), false, ERROR);
        Html::back();
    }

    $embodied_impact_namespace = Config::getEmbeddedImpactEngine();
    $embodied_impact_class = $embodied_impact_namespace . '\\' . (string) $_POST['itemtype'];
    if (!class_exists($embodied_impact_class) || !is_subclass_of($embodied_impact_class, AbstractEmbeddedImpact::class)) {
        Session::addMessageAfterRedirect(__('Bad arguments.', 'carbon'), false, ERROR);
        Html::back();
    }

    /** @var AbstractAsset $embodied_impact */
    $embodied_impact = new $embodied_impact_class();
    $itemtype = $embodied_impact->getItemtype();
    $item = new $itemtype();
    $item->getFromDB($_POST['items_id']);
    if (!$item->canUpdate()) {
        Session::addMessageAfterRedirect(__('Update denied.', 'carbon'), false, ERROR);
        Html::back();
    }

    $embodied_impact->setClient(new Boaviztapi(new RestApiClient()));
    if (!$embodied_impact->calculateImpact($_POST['items_id'])) {
        Session::addMessageAfterRedirect(__('Update failed.', 'carbon'), false, ERROR);
    }
}

Html::back();
