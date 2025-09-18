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

use Glpi\Dashboard\Right as GlpiDashboardRight;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\Install;
use GlpiPlugin\Carbon\Uninstall;
use GlpiPlugin\Carbon\UsageInfo;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\Zone;
use ComputerType as GlpiComputerType;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\CarbonIntensity;
use MonitorType as GlpiMonitorType;
use NetworkEquipmentType as GlpiNetworkEquipmentType;
use GlpiPlugin\Carbon\CarbonIntensitySource_Zone;
use GlpiPlugin\Carbon\Config;
use GlpiPlugin\Carbon\EmbodiedImpact;
use GlpiPlugin\Carbon\Location;
use GlpiPlugin\Carbon\MonitorType;
use GlpiPlugin\Carbon\NetworkEquipmentType;
use GlpiPlugin\Carbon\SearchOptions;
use GlpiPlugin\Carbon\Toolbox;
use Location as GlpiLocation;
use Profile as GlpiProfile;
use Toolbox as GlpiToolbox;

/**
 * Plugin install process
 * supported arguments for upgrade process
 *   -p force-upgrade          : force execution of upgrade from the previous version
 *                               or from the version specified wih version argument
 *   -p version                : specifi the version to begin a forced upgrade
 *   -p reset-report-dashboard : delete then recreate the dashboard of the reporting page
 *
 * @return boolean
 */
function plugin_carbon_install(array $args = []): bool
{
    if (!is_readable(__DIR__ . '/install/Install.php')) {
        return false;
    }
    require_once(__DIR__ . '/install/Install.php');
    $version = Install::detectVersion();
    $install = new Install(new Migration(PLUGIN_CARBON_VERSION));

    $success = true;
    $silent = !isCommandLine() && $_SESSION['glpi_use_mode'] !== Session::DEBUG_MODE;
    if ($silent) {
        // do not output messages
        ob_start();
    }
    if ($version === '0.0.0') {
        try {
            $success = $install->install($args);
        } catch (\Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            if (!isCommandLine()) {
                Session::addMessageAfterRedirect(
                    $e->getMessage(),
                    false,
                    ERROR
                );
            }
            $success = false;
        }
    } else {
        try {
            $success = $install->upgrade($version, $args);
        } catch (\Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            if (!isCommandLine()) {
                Session::addMessageAfterRedirect(
                    $e->getMessage(),
                    false,
                    ERROR
                );
            }
            $success = false;
        }
    }

    if ($silent) {
        // do not output messages
        ob_end_clean();
    }
    return $success;
}

/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_carbon_uninstall(): bool
{
    if (!is_readable(__DIR__ . '/install/Uninstall.php')) {
        return false;
    }
    require_once(__DIR__ . '/install/Uninstall.php');
    $uninstall = new Uninstall();
    try {
        $uninstall->uninstall();
    } catch (\Exception $e) {
        $backtrace = GlpiToolbox::backtrace('');
        trigger_error($e->getMessage() . PHP_EOL . $backtrace, E_USER_WARNING);
        return false;
    }

    return true;
}

function plugin_carbon_getDropdown()
{
    return [
        ComputerUsageProfile::class  => ComputerUsageProfile::getTypeName(),
        CarbonIntensitySource::class => CarbonIntensitySource::getTypeName(),
        Zone::class   => Zone::getTypeName(),
        CarbonIntensity::class       => CarbonIntensity::getTypeName(),
    ];
}

function plugin_carbon_postShowTab(array $param)
{
    if ($param['options']['itemtype'] !== UsageInfo::class) {
        return;
    }
    $asset_itemtype = $param['item']::getType();
    if (!in_array($asset_itemtype, PLUGIN_CARBON_TYPES)) {
        return;
    }

    $history_class = 'GlpiPlugin\\Carbon\\Impact\\History\\' . $asset_itemtype;
    $history_class::showHistorizableDiagnosis($param['item']);
    UsageInfo::showCharts($param['item']);
}

/**
 * Add search options to core itemtypes
 *
 * @param string $itemtype
 * @return array
 */
function plugin_carbon_getAddSearchOptionsNew($itemtype): array
{
    return SearchOptions::getCoreSearchOptions($itemtype);
}

/**
 * Callback before showing save / update button on an item form
 *
 * @param array $params 'item' => CommonDBTM
 *                       'options => array
 * @return void
 */
function plugin_carbon_postItemForm(array $params)
{
    switch ($params['item']->getType()) {
        case GlpiLocation::class:
            $location = new Location();
            $location->getFromDBByCrit([
                GlpiLocation::getForeignKeyField() => $params['item']->getID(),
            ]);
            $location->showForm($location->getID());
            break;
    }
}

function plugin_carbon_hook_add_asset(CommonDBTM $item)
{
    if (!in_array($item::getType(), PLUGIN_CARBON_TYPES)) {
        return;
    }
    $location_fk = GlpiLocation::getForeignKeyField();
    if (!in_array($location_fk, array_keys($item->fields))) {
        return;
    }
    if (GlpiLocation::isNewID($item->fields[$location_fk])) {
        return;
    }
    $zone = Zone::getByAsset($item);
    if ($zone === null) {
        return;
    }
    $source_zone = new CarbonIntensitySource_Zone();
    $source_zone->getFromDBByCrit([
        $zone->getForeignKeyField() => $zone->fields['id'],
        CarbonIntensitySource::getForeignKeyField() => $zone->fields['plugin_carbon_carbonintensitysources_id_historical'],
    ]);
    if ($source_zone->isNewItem()) {
        return;
    }
    $source_zone->toggleZone(true);
}

function plugin_carbon_hook_update_asset(CommonDBTM $item)
{
    if (!in_array($item::getType(), PLUGIN_CARBON_TYPES)) {
        return;
    }
    $location_fk = GlpiLocation::getForeignKeyField();
    if (!in_array($location_fk, $item->updates)) {
        return;
    }
    if (GlpiLocation::isNewID($item->fields[$location_fk])) {
        return;
    }
    $zone = Zone::getByAsset($item);
    if ($zone === null) {
        return;
    }
    $source_zone = new CarbonIntensitySource_Zone();
    $source_zone->getFromDBByCrit([
        $zone->getForeignKeyField() => $zone->fields['id'],
        CarbonIntensitySource::getForeignKeyField() => $zone->fields['plugin_carbon_carbonintensitysources_id_historical'],
    ]);
    if ($source_zone->isNewItem()) {
        return;
    }
    $source_zone->toggleZone(true);
}

/**
 * Callback when an asset is being purged from the database
 *
 * @param CommonDBTM $item
 * @return void
 */
function plugin_carbon_hook_pre_purge_asset(CommonDBTM $item)
{
    if (!in_array($item::getType(), PLUGIN_CARBON_TYPES)) {
        return;
    }

    $itemtype = $item->getType();
    $item_id = $item->getID();
    $carbon_emission = new CarbonEmission();
    $carbon_emission->deleteByCriteria([
        'itemtype' => $itemtype,
        'items_id' => $item_id
    ]);

    $embodied_impact = new EmbodiedImpact();
    $embodied_impact->deleteByCriteria([
        'itemtype' => $itemtype,
        'items_id' => $item_id
    ]);

    $usage_info = new UsageInfo();
    $usage_info->deleteByCriteria([
        'itemtype' => $itemtype,
        'items_id' => $item_id
    ]);
}

/**
 * Delete plugin's data linked to asset types
 *
 * @param CommonDBTM $item
 * @return void
 */
function plugin_carbon_hook_pre_purge_assettype(CommonDBTM $item)
{
    $itemtype = $item->getType();
    $pos = strrpos($itemtype, 'Type');
    if ($pos !== strlen($itemtype) - 4) { // 4 is length of 'Type'
        return;
    }

    $asset_itemtype = substr($itemtype, 0, $pos);
    if (!in_array($asset_itemtype, PLUGIN_CARBON_TYPES)) {
        return;
    }

    $carbon_type_itemtype = 'GlpiPlugin\\Carbon\\' . $itemtype;
    $carbon_type = new $carbon_type_itemtype();
    $carbon_type->deleteByCriteria([
        $item->getForeignKeyField() => $item->getID(),
    ]);
}

function plugin_carbon_MassiveActions($itemtype)
{
    switch ($itemtype) {
        case Computer::class:
            return [
                ComputerUsageProfile::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'MassAssociateItems' => __('Associate to an usage profile', 'carbon'),
            ];
        case GlpiComputerType::class:
            return [
                ComputerType::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'MassUpdatePower' => __('Update type power consumption', 'carbon'),
                ComputerType::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'MassUpdateCategory' => __('Update category', 'carbon'),
            ];
        case GlpiMonitorType::class:
            return [
                MonitorType::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'MassUpdatePower' => __('Update type power consumption', 'carbon'),
            ];
        case GlpiNetworkEquipmentType::class:
            return [
                NetworkEquipmentType::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'MassUpdatePower' => __('Update type power consumption', 'carbon'),
            ];
        case GlpiLocation::class:
            return [
                Location::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'MassUpdateBoaviztaZone' => __('Update zone for Boavizta engine', 'carbon'),
            ];
    }

    return [];
}

function plugin_carbon_profileAdd(CommonDBTM $item)
{
    if (!isset($item->input['_carbon:report']['1_0'])) {
        // Access to reporting not affected
        return;
    }
    if (($dashboard_id = Toolbox::getDashboardId()) === null) {
        // Dashboard of the plugin notfound (should not happen)
        return;
    }

    $dashboard_right = new GlpiDashboardRight();

    $grant_access = ($item->input['_carbon:report']['1_0'] == 1);
    $dashboard_right->getFromDBByCrit([
        'itemtype' => GlpiProfile::class,
        'items_id' => $item->getID(),
        'dashboards_dashboards_id' => $dashboard_id,
    ]);
    if ($grant_access) {
        // Create right for profile if not exists
        if ($dashboard_right->isNewItem()) {
            $dashboard_right->add([
                'itemtype' => GlpiProfile::class,
                'items_id' => $item->getID(),
                'dashboards_dashboards_id' => $dashboard_id,
            ]);
        }
        return;
    }

    // delete right if exists
    if (!$dashboard_right->isNewItem()) {
        $dashboard_right->delete([
            'id' => $dashboard_right->getID(),
        ]);
    }
}

function plugin_carbon_profileUpdate(CommonDBTM $item)
{
    plugin_carbon_profileAdd($item);
}

function plugin_carbon_locationAdd(CommonDBTM $item)
{
    $location = new Location();
    $location->onGlpiLocationAdd($item, Config::getGeocoder());
}


function plugin_carbon_locationPreUpdate(CommonDBTM $item)
{
    $location = new Location();
    $location->onGlpiLocationPreUpdate($item, Config::getGeocoder());
}

function plugin_carbon_locationUpdate(CommonDBTM $item)
{
    $location = new Location();
    $location->onGlpiLocationUpdate($item);
}

function plugin_carbon_locationPrePurge(CommonDBTM $item)
{
    $location = new Location();
    $location->onGlpiLocationPrePurge($item);
}
