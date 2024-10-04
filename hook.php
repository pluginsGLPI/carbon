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

use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\Install;
use GlpiPlugin\Carbon\Uninstall;
use GlpiPlugin\Carbon\EnvironnementalImpact;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\CarbonIntensityZone;
use GlpiPlugin\Carbon\MonitorType;
use GlpiPlugin\Carbon\NetworkEquipmentType;
use ComputerType as GlpiComputerType;
use MonitorType as GlpiMonitorType;
use GlpiPlugin\Carbon\CarbonIntensitySource_CarbonIntensityZone;
use GlpiPlugin\Carbon\History\Computer as ComputerHistory;
use GlpiPlugin\Carbon\History\Monitor as MonitorHistory;
use GlpiPlugin\Carbon\History\NetworkEquipment as NetworkEquipmentHistory;
use NetworkEquipmentType as GlpiNetworkEquipmentType;

/**
 * Plugin install process
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

    if ($version === '0.0.0') {
        try {
            return $install->install($args);
        } catch (\Exception $e) {
            $backtrace = Toolbox::backtrace(false);
            trigger_error($e->getMessage() . PHP_EOL . $backtrace, E_USER_WARNING);
            return false;
        }
    } else {
        try {
            return $install->upgrade($version, $args);
        } catch (\Exception $e) {
            $backtrace = Toolbox::backtrace(false);
            trigger_error($e->getMessage() . PHP_EOL . $backtrace, E_USER_WARNING);
            return false;
        }
    }

    return true;
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
    $uninstall = new Uninstall(new Migration(PLUGIN_CARBON_VERSION));
    try {
        $uninstall->uninstall();
    } catch (\Exception $e) {
        $backtrace = Toolbox::backtrace(false);
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
        CarbonIntensityZone::class   => CarbonIntensityZone::getTypeName(),
    ];
}

function plugin_carbon_postShowTab(array $param)
{
    switch ($param['item']::getType()) {
        case Computer::class:
            if ($param['options']['itemtype'] == EnvironnementalImpact::class) {
                ComputerHistory::showHistorizableDiagnosis($param['item']);
                EnvironnementalImpact::showCharts($param['item']);
            }
            break;
        case Monitor::class:
            if ($param['options']['itemtype'] == EnvironnementalImpact::class) {
                MonitorHistory::showHistorizableDiagnosis($param['item']);
                EnvironnementalImpact::showCharts($param['item']);
            }
            break;
        case NetworkEquipment::class:
            if ($param['options']['itemtype'] == EnvironnementalImpact::class) {
                NetworkEquipmentHistory::showHistorizableDiagnosis($param['item']);
                EnvironnementalImpact::showCharts($param['item']);
            }
            break;
    }
}

/**
 * Undocumented function
 *
 * @param [type] $itemtype
 * @return array
 */
function plugin_carbon_getAddSearchOptionsNew($itemtype): array
{
    $sopt = [];

    if (!in_array($itemtype, PLUGIN_CARBON_TYPES)) {
        return $sopt;
    }

    $item_type_class = '\\GlpiPlugin\\Carbon\\' . $itemtype . 'Type';
    $glpi_item_type_class = '\\' . $itemtype . 'Type';
    if (class_exists($item_type_class) && is_subclass_of($item_type_class, CommonDBTM::class)) {
        $sopt[] = [
            'id'           => PLUGIN_CARBON_SEARCH_OPTION_BASE + 500,
            'table'        => getTableForItemType($item_type_class),
            'field'        => 'power_consumption',
            'name'         => __('Power consumption', 'carbon'),
            'datatype'     => 'number',
            'min'          => 0,
            'max'          => 10000,
            'unit'         => 'W',
            'linkfield'    => 'monitors_id',
            'joinparams' => [
                'jointype' => 'child',
                'beforejoin' => [
                    'table' => getTableForItemType($glpi_item_type_class),
                    'joinparams' => [
                        'jointype' => 'child',
                    ]
                ]
            ],
            'computation' => "IF(TABLE.`power_consumption` IS NULL, 0, TABLE.`power_consumption`)",
        ];
    }

    if ($itemtype === Computer::class) {
        $sopt[] = [
            'id'           => PLUGIN_CARBON_SEARCH_OPTION_BASE + 501,
            'table'         => ComputerUsageProfile::getTable(),
            'field'         => 'name',
            'name'          => ComputerUsageProfile::getTypeName(),
            'datatype'      => 'dropdown',
            'massiveaction' => false,
            'joinparams' => [
                'jointype' => 'empty',
                'beforejoin' => [
                    'table'    => EnvironnementalImpact::getTable(),
                    'joinparams' => [
                        'jointype' => 'child',
                    ]
                ]
            ]
        ];

        $sopt[] = [
            'id'           => PLUGIN_CARBON_SEARCH_OPTION_BASE + 502,
            'table'         => ComputerType::getTable(),
            'field'         => 'id',
            'name'          => __('Is historizable', 'carbon'),
            'datatype'      => 'number',
            'massiveaction' => false,
            'linkfield'     => 'computers_id',
            'joinparams' => [
                'jointype' => 'child',
                'beforejoin' => [
                    'table' => GlpiComputerType::getTable(),
                    'joinparams' => [
                        'jointype' => 'child',
                    ]
                ]
            ],
        ];
    }

    return $sopt;
}

function plugin_carbon_addDefaultSelect($itemtype): string
{
    switch ($itemtype) {
        default:
            return '';
        case Computer::class:
    }

    $display_preference = new DisplayPreference();
    $display_preferences = $display_preference->find([
        'itemtype' => $itemtype,
        'num'      => 0,
        'users_id' => [0, Session::getLoginUserID()],
    ]);
    if (count($display_preferences) === 0) {
        return '';
    }
}

function plugin_carbon_addDefaultJoin($itemtype, $ref_table, &$already_link_tables): string
{
    switch ($itemtype) {
        default:
            return '';
        case Computer::class:
    }

    $display_preference = new DisplayPreference();
    $display_preferences = $display_preference->find([
        'itemtype' => $itemtype,
        'num'      => 0,
        'users_id' => [0, Session::getLoginUserID()],
    ]);
    if (count($display_preferences) === 0) {
        return '';
    }
}

function plugin_carbon_hook_add_location(CommonDBTM $item)
{
    if (!in_array('country', array_keys($item->fields))) {
        return;
    }
    $zone = CarbonIntensityZone::getByLocation($item);
    if ($zone === null) {
        return;
    }
    $source_zone = new CarbonIntensitySource_CarbonIntensityZone();
    $source_zone->getFromDBByCrit([
        $zone->getForeignKeyField() => $zone->fields['id'],
        CarbonIntensitySource::getForeignKeyField() => $zone->fields['plugin_carbon_carbonintensitysources_id_historical'],
    ]);
    if ($source_zone === null) {
        return;
    }
    $source_zone->toggleZone(true);
}

function plugin_carbon_hook_update_location(CommonDBTM $item)
{
    if (!in_array('country', $item->updates)) {
        return;
    }
    $zone = CarbonIntensityZone::getByLocation($item);
    if ($zone === null) {
        return;
    }
    $source_zone = new CarbonIntensitySource_CarbonIntensityZone();
    $source_zone->getFromDBByCrit([
        $zone->getForeignKeyField() => $zone->fields['id'],
        CarbonIntensitySource::getForeignKeyField() => $zone->fields['plugin_carbon_carbonintensitysources_id_historical'],
    ]);
    if ($source_zone === null) {
        return;
    }
    $source_zone->toggleZone(true);
}

function plugin_carbon_hook_add_asset(CommonDBTM $item)
{
    if (!in_array($item::getType(), PLUGIN_CARBON_TYPES)) {
        return;
    }
    $location_fk = Location::getForeignKeyField();
    if (!in_array($location_fk, array_keys($item->fields))) {
        return;
    }
    if (Location::isNewID($item->fields[$location_fk])) {
        return;
    }
    $zone = CarbonIntensityZone::getByAsset($item);
    if ($zone === null) {
        return;
    }
    $source_zone = new CarbonIntensitySource_CarbonIntensityZone();
    $source_zone->getFromDBByCrit([
        $zone->getForeignKeyField() => $zone->fields['id'],
        CarbonIntensitySource::getForeignKeyField() => $zone->fields['plugin_carbon_carbonintensitysources_id_historical'],
    ]);
    if ($source_zone === null) {
        return;
    }
    $source_zone->toggleZone(true);
}

function plugin_carbon_hook_update_asset(CommonDBTM $item)
{
    if (!in_array($item::getType(), PLUGIN_CARBON_TYPES)) {
        return;
    }
    $location_fk = Location::getForeignKeyField();
    if (!in_array($location_fk, $item->updates)) {
        return;
    }
    if (Location::isNewID($item->fields[$location_fk])) {
        return;
    }
    $zone = CarbonIntensityZone::getByAsset($item);
    if ($zone === null) {
        return;
    }
    $source_zone = new CarbonIntensitySource_CarbonIntensityZone();
    $source_zone->getFromDBByCrit([
        $zone->getForeignKeyField() => $zone->fields['id'],
        CarbonIntensitySource::getForeignKeyField() => $zone->fields['plugin_carbon_carbonintensitysources_id_historical'],
    ]);
    if ($source_zone === null) {
        return;
    }
    $source_zone->toggleZone(true);
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
                ComputerType::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'MassUpdatePower' => __('Update power consumption', 'carbon'),
            ];
    }

    return [];
}
