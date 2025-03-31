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

use Glpi\Dashboard\Right as GlpiDashboardRight;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\Install;
use GlpiPlugin\Carbon\Uninstall;
use GlpiPlugin\Carbon\UsageInfo;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\Zone;
use ComputerType as GlpiComputerType;
use GlpiPlugin\Carbon\CarbonIntensity;
use MonitorType as GlpiMonitorType;
use NetworkEquipmentType as GlpiNetworkEquipmentType;
use GlpiPlugin\Carbon\CarbonIntensitySource_Zone;
use GlpiPlugin\Carbon\Impact\History\Computer as ComputerHistory;
use GlpiPlugin\Carbon\Impact\History\Monitor as MonitorHistory;
use GlpiPlugin\Carbon\Impact\History\NetworkEquipment as NetworkEquipmentHistory;
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
            $backtrace = GlpiToolbox::backtrace('');
            trigger_error($e->getMessage() . PHP_EOL . $backtrace, E_USER_WARNING);
            return false;
        }
    } else {
        try {
            return $install->upgrade($version, $args);
        } catch (\Exception $e) {
            $backtrace = GlpiToolbox::backtrace('');
            trigger_error($e->getMessage() . PHP_EOL . $backtrace, E_USER_WARNING);
            return false;
        }
    }
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
    /** @var DBmysql $DB */
    global $DB;

    $sopt = [];

    if (!in_array($itemtype, PLUGIN_CARBON_TYPES)) {
        return $sopt;
    }

    $item_type_class = 'GlpiPlugin\\Carbon\\' . $itemtype . 'Type';
    $glpi_item_type_class = $itemtype . 'Type';
    if (class_exists($item_type_class) && is_subclass_of($item_type_class, CommonDBTM::class)) {
        $itemtype_fk = $itemtype::getForeignKeyField();
        $sopt[] = [
            'id'           => SearchOptions::POWER_CONSUMPTION,
            'table'        => getTableForItemType($item_type_class),
            'field'        => 'power_consumption',
            'name'         => __('Power consumption', 'carbon'),
            'datatype'     => 'number',
            'massiveaction' => false,
            'min'          => 0,
            'max'          => 10000,
            'unit'         => 'W',
            'linkfield'    => $itemtype_fk,
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
            'id'           => SearchOptions::USAGE_PROFILE,
            'table'         => ComputerUsageProfile::getTable(),
            'field'         => 'name',
            'name'          => ComputerUsageProfile::getTypeName(),
            'datatype'      => 'dropdown',
            'massiveaction' => false,
            'joinparams' => [
                'jointype' => 'empty',
                'beforejoin' => [
                    'table'    => UsageInfo::getTable(),
                    'joinparams' => [
                        'jointype' => 'child',
                    ]
                ]
            ]
        ];

        $computation = "IF(`glpi_computers_id_e1f6cdb2d63e8a0252da5d4cb339a927`.`is_deleted` = 0
        AND `glpi_computers_id_e1f6cdb2d63e8a0252da5d4cb339a927`.`is_template` = 0
        AND NOT `glpi_locations`.`country`  = ''
        AND NOT `glpi_locations`.`country` IS NULL
        AND `glpi_plugin_carbon_computerusageprofiles_09f8403aa14af64cd70f350288a0331b`.`id` > 0
        AND (
            `glpi_plugin_carbon_computertypes_a643ab3ffd70abf99533ed214da87d60`.`power_consumption` > 0
            OR `glpi_computermodels`.`power_consumption` > 0
        ), 1, 0)";
        $sopt[] = [
            'id'           => SearchOptions::IS_HISTORIZABLE,
            'table'         => getTableForItemType($itemtype),
            'field'         => 'id',
            'linkfield'     => 'id',
            'name'          => __('Is historizable', 'carbon'),
            'datatype'      => 'bool',
            'massiveaction' => false,
            'joinparams' => [
                'jointype' => 'empty',
                'beforejoin' => [
                    [
                        'table' => GlpiLocation::getTable(),
                        'joinparams' => [
                            'jointype' => 'empty',
                            'nolink'   => true,
                        ]
                    ],
                    [
                        'table' => ComputerType::getTable(),
                        'joinparams' => [
                            'jointype' => 'child',
                            'nolink'   => true,
                            'beforejoin' => [
                                'table' => GlpiComputerType::getTable(),
                                'joinparams' => [
                                    'jointype' => 'empty',
                                ]
                            ]
                        ]
                    ],
                    [
                        'table' => ComputerModel::getTable(),
                        'joinparams' => [
                            'jointype' => 'empty',
                            'nolink'   => true,
                        ]
                    ],
                    [
                        'table' => ComputerUsageProfile::getTable(),
                        'joinparams' => [
                            'jointype' => 'empty',
                            'nolink'   => true,
                            'beforejoin' => [
                                'table' => UsageInfo::getTable(),
                                'joinparams' => [
                                    'jointype' => 'itemtype_item',
                                ]
                            ]
                        ]
                    ],
                ],
            ],
            'computation' => $computation,
        ];
    } else if ($itemtype === Monitor::class) {
        $computation = "IF(`glpi_monitors_id_fd9c1a8262e8f3b6e96bc8948f2a6226`.`is_deleted` = 0
        AND `glpi_monitors_id_fd9c1a8262e8f3b6e96bc8948f2a6226`.`is_template` = 0
        AND NOT `glpi_locations_fad8b1764dcda16e3822068239df73f2`.`country`  = ''
        AND NOT `glpi_locations_fad8b1764dcda16e3822068239df73f2`.`country` IS NULL
        AND (
            `glpi_plugin_carbon_monitortypes_54b036337d1b9bbf4f13db0e1ae93bc9`.`power_consumption` > 0
            OR `glpi_monitormodels`.`power_consumption` > 0
        ), 1, 0)";
        $sopt[] = [
            'id'           => SearchOptions::IS_HISTORIZABLE,
            'table'         => getTableForItemType($itemtype),
            'field'         => 'id',
            'linkfield'     => 'id',
            'name'          => __('Is historizable', 'carbon'),
            'datatype'      => 'bool',
            'massiveaction' => false,
            'joinparams' => [
                'jointype' => 'empty',
                'beforejoin' => [
                    [
                        'table' => GlpiLocation::getTable(),
                        'joinparams' => [
                            'jointype' => 'empty',
                            'nolink'   => true,
                            'beforejoin' => [
                                'table' => Computer::getTable(),
                                'joinparams' => [
                                    'jointype' => 'empty',
                                    'beforejoin' => [
                                        'table' => Computer_Item::getTable(),
                                        'joinparams' => [
                                            'jointype' => 'itemtype_item',
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'table' => MonitorType::getTable(),
                        'joinparams' => [
                            'jointype' => 'child',
                            'nolink'   => true,
                            'beforejoin' => [
                                'table' => GlpiMonitorType::getTable(),
                                'joinparams' => [
                                    'jointype' => 'empty',
                                ]
                            ]
                        ]
                    ],
                    [
                        'table' => MonitorModel::getTable(),
                        'joinparams' => [
                            'jointype' => 'empty',
                            'nolink'   => true,
                        ]
                    ],
                ],
            ],
            'computation' => $computation,
        ];
    } else if ($itemtype === NetworkEquipment::class) {
        $computation = "IF(`glpi_networkequipments_id_aef00423a27f97ae31ca50f63fb1a6fb`.`is_deleted` = 0
        AND `glpi_networkequipments_id_aef00423a27f97ae31ca50f63fb1a6fb`.`is_template` = 0
        AND NOT `glpi_locations`.`country`  = ''
        AND NOT `glpi_locations`.`country` IS NULL
        AND (
            `glpi_plugin_carbon_networkequipmenttypes_640a9703b62363e5d254356fb4df69ef`.`power_consumption` > 0
            OR `glpi_networkequipmentmodels`.`power_consumption` > 0
        ), 1, 0)";
        $sopt[] = [
            'id'           => SearchOptions::IS_HISTORIZABLE,
            'table'         => getTableForItemType($itemtype),
            'field'         => 'id',
            'linkfield'     => 'id',
            'name'          => __('Is historizable', 'carbon'),
            'datatype'      => 'bool',
            'massiveaction' => false,
            'joinparams' => [
                'jointype' => 'empty',
                'beforejoin' => [
                    [
                        'table' => GlpiLocation::getTable(),
                        'joinparams' => [
                            'jointype' => 'empty',
                            'nolink'   => true,
                        ]
                    ],
                    [
                        'table' => NetworkEquipmentType::getTable(),
                        'joinparams' => [
                            'jointype' => 'child',
                            'nolink'   => true,
                            'beforejoin' => [
                                'table' => GlpiNetworkEquipmentType::getTable(),
                                'joinparams' => [
                                    'jointype' => 'empty',
                                ]
                            ]
                        ]
                    ],
                    [
                        'table' => NetworkEquipmentModel::getTable(),
                        'joinparams' => [
                            'jointype' => 'empty',
                            'nolink'   => true,
                        ]
                    ],
                ],
            ],
            'computation' => $computation,
        ];
    }

    return $sopt;
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
            ];
        case GlpiMonitorType::class:
            return [
                MonitorType::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'MassUpdatePower' => __('Update type power consumption', 'carbon'),
            ];
        case GlpiNetworkEquipmentType::class:
            return [
                NetworkEquipmentType::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'MassUpdatePower' => __('Update type power consumption', 'carbon'),
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
