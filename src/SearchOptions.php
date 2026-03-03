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

namespace GlpiPlugin\Carbon;

use CommonDBTM;
use Computer as GlpiComputer;
use Location as GlpiLocation;
use ComputerType as GlpiComputerType;
use ComputerModel;
use Glpi\Asset\Asset_PeripheralAsset;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QuerySubQuery;
use Monitor as GlpiMonitor;
use MonitorType as GlpiMonitorType;
use MonitorModel;
use NetworkEquipment;
use NetworkEquipmentType as GlpiNetworkEquipmentType;
use NetworkEquipmentModel;

/**
 * This file is *REQUIRED* in the whole life time of the plugin
 * used in install process
 */

/**
 * Centralize ID of search options in a single place for btter handling
 * in case of conflict with an other plugin
 */
class SearchOptions
{
    private const SEARCH_OPTION_BASE = 128000;

    public const COMPUTER_USAGE_PROFILE_START_TIME = self::SEARCH_OPTION_BASE + 101;
    public const COMPUTER_USAGE_PROFILE_STOP_TIME  = self::SEARCH_OPTION_BASE + 102;
    public const COMPUTER_USAGE_PROFILE_DAY_1      = self::SEARCH_OPTION_BASE + 110;
    public const COMPUTER_USAGE_PROFILE_DAY_2      = self::SEARCH_OPTION_BASE + 111;
    public const COMPUTER_USAGE_PROFILE_DAY_3      = self::SEARCH_OPTION_BASE + 112;
    public const COMPUTER_USAGE_PROFILE_DAY_4      = self::SEARCH_OPTION_BASE + 113;
    public const COMPUTER_USAGE_PROFILE_DAY_5      = self::SEARCH_OPTION_BASE + 114;
    public const COMPUTER_USAGE_PROFILE_DAY_6      = self::SEARCH_OPTION_BASE + 115;
    public const COMPUTER_USAGE_PROFILE_DAY_7      = self::SEARCH_OPTION_BASE + 116;

    public const USAGE_INFO_COMPUTER_USAGE_PROFILE = self::SEARCH_OPTION_BASE + 202;

    public const HISTORICAL_DATA_SOURCE     = self::SEARCH_OPTION_BASE + 301;
    public const HISTORICAL_DATA_DL_ENABLED = self::SEARCH_OPTION_BASE + 302;

    public const CARBON_INTENSITY_SOURCE    = self::SEARCH_OPTION_BASE + 401;
    public const CARBON_INTENSITY_ZONE      = self::SEARCH_OPTION_BASE + 402;
    public const CARBON_INTENSITY_INTENSITY = self::SEARCH_OPTION_BASE + 403;

    public const POWER_CONSUMPTION = self::SEARCH_OPTION_BASE + 500;
    public const USAGE_PROFILE     = self::SEARCH_OPTION_BASE + 501;
    public const IS_HISTORIZABLE   = self::SEARCH_OPTION_BASE + 502;
    public const IS_IGNORED        = self::SEARCH_OPTION_BASE + 503;

    public const CARBON_EMISSION_DATE             = self::SEARCH_OPTION_BASE + 600;
    public const CARBON_EMISSION_ENERGY_PER_DAY   = self::SEARCH_OPTION_BASE + 601;
    public const CARBON_EMISSION_PER_DAY          = self::SEARCH_OPTION_BASE + 602;
    public const CARBON_EMISSION_ENERGY_QUALITY   = self::SEARCH_OPTION_BASE + 603;
    public const CARBON_EMISSION_EMISSION_QUALITY = self::SEARCH_OPTION_BASE + 604;
    public const CALCULATION_DATE                 = self::SEARCH_OPTION_BASE + 605;
    public const CALCULATION_ENGINE               = self::SEARCH_OPTION_BASE + 606;
    public const CALCULATION_ENGINE_VERSION       = self::SEARCH_OPTION_BASE + 607;

    public const COMPUTER_TYPE_CATEGORY   = self::SEARCH_OPTION_BASE + 800;
    public const COMPUTER_TYPE_IS_IGNORED = self::SEARCH_OPTION_BASE + 801;

    public const LOCATION_BOAVIZTA_ZONE = self::SEARCH_OPTION_BASE + 900;

    public const MONITOR_TYPE_IS_IGNORED = self::SEARCH_OPTION_BASE + 1000;

    public const NETEQUIP_TYPE_IS_IGNORED = self::SEARCH_OPTION_BASE + 1100;

    // First search option ID for all imapcts
    // Impacts are numbered accross several consecutive IDs
    // - impact type
    // - impact quality
    // @see AbstractImpact::rawSearchOption
    public const IMPACT_BASE                 = self::SEARCH_OPTION_BASE + 1200;

    public const EMBODIED_IMPACT_GWP         = self::SEARCH_OPTION_BASE + 1200;
    public const EMBODIED_IMPACT_GWP_SOURCE  = self::SEARCH_OPTION_BASE + 1201;
    public const EMBODIED_IMPACT_GWP_QUALITY = self::SEARCH_OPTION_BASE + 1202;
    public const EMBODIED_IMPACT_ADP         = self::SEARCH_OPTION_BASE + 1203;
    public const EMBODIED_IMPACT_ADP_SOURCE  = self::SEARCH_OPTION_BASE + 1204;
    public const EMBODIED_IMPACT_ADP_QUALITY = self::SEARCH_OPTION_BASE + 1205;
    public const EMBODIED_IMPACT_PE          = self::SEARCH_OPTION_BASE + 1206;
    public const EMBODIED_IMPACT_PE_SOURCE   = self::SEARCH_OPTION_BASE + 1207;
    public const EMBODIED_IMPACT_PE_QUALITY  = self::SEARCH_OPTION_BASE + 1208;

    /**
     * Get search options added to a core itemtype by the plugin
     *
     * @template T of CommonDBTM
     * @param class-string<T> $itemtype
     * @return array
     */
    public static function getCoreSearchOptions(string $itemtype): array
    {
        $sopt = [];

        if ($itemtype === GlpiLocation::class) {
            $sopt[] = [
                'id'           => SearchOptions::LOCATION_BOAVIZTA_ZONE,
                'table'        => getTableForItemType(Location::class),
                'field'        => 'boavizta_zone',
                'name'         => __('Boavizta zone', 'carbon'),
                'datatype'     => 'specific',
                'searchtype'   => ['equals', 'notequals'],
                'massiveaction' => false,
                'linkfield'    => 'locations_id',
                'joinparams' => [
                    'jointype' => 'child',
                ],
            ];
        }

        if (in_array($itemtype, PLUGIN_CARBON_TYPES)) {
            $item_type_class = 'GlpiPlugin\\Carbon\\' . $itemtype . 'Type';
            $glpi_item_type_class = $itemtype . 'Type';
            /** @phpstan-ignore-next-line */
            if (class_exists($item_type_class) && is_subclass_of($item_type_class, CommonDBTM::class)) {
                $itemtype_fk = $itemtype::getForeignKeyField();
                $item_type_table = getTableForItemType($item_type_class);
                $glpi_item_type_table = getTableForItemType($glpi_item_type_class);
                $sopt[] = [
                    'id'           => SearchOptions::POWER_CONSUMPTION,
                    'table'        => $item_type_table,
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
                            'table' => $glpi_item_type_table,
                            'joinparams' => [
                                'jointype' => 'child',
                            ]
                        ]
                    ],
                    'computation' => "COALESCE(TABLE.`power_consumption`, 0)",
                ];

                $sopt[] = [
                    'id'           => SearchOptions::IS_IGNORED,
                    'table'        => $item_type_table,
                    'field'        => 'is_ignore',
                    'name'         => __('Ignore environmental impact', 'carbon'),
                    'datatype'     => 'bool',
                    'massiveaction' => false,
                    'linkfield'    => $itemtype_fk,
                    'joinparams' => [
                        'jointype' => 'child',
                        'beforejoin' => [
                            'table' => $glpi_item_type_table,
                            'joinparams' => [
                                'jointype' => 'child',
                            ]
                        ]
                    ],
                    'computation' => "COALESCE(TABLE.`is_ignore`, 0)",
                ];
            }
        }

        if ($itemtype === GlpiComputer::class && in_array($itemtype, PLUGIN_CARBON_TYPES)) {
            $sopt[] = [
                'id'            => SearchOptions::USAGE_PROFILE,
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

            $fallback_carbon_intensity_subquery = Location::getCarbonIntensityDataSourceRequest([
                Location::getTableField('id') => new QueryExpression('glpi_plugin_carbon_locations_3d6da7fccf9233a3f1a4e41183391a41.id')
            ]);
            $fallback_carbon_intensity_subquery = (new QuerySubQuery($fallback_carbon_intensity_subquery))->getQuery();
            $computation = "IF(`glpi_computers_id_963cd5e903dddc7ab00a3b70933369df`.`is_deleted` = 0
            AND `glpi_computers_id_963cd5e903dddc7ab00a3b70933369df`.`is_template` = 0
            AND `glpi_plugin_carbon_locations_3d6da7fccf9233a3f1a4e41183391a41`.`plugin_carbon_sources_zones_id` > 0
            AND `glpi_plugin_carbon_computerusageprofiles_09f8403aa14af64cd70f350288a0331b`.`id` > 0"
            // Do not check if an asset is ignored
            // . "AND COALESCE(`glpi_plugin_carbon_computertypes_a643ab3ffd70abf99533ed214da87d60`.`is_ignore`, 0) = 0"
            . " AND (
                `glpi_plugin_carbon_computertypes_a643ab3ffd70abf99533ed214da87d60`.`power_consumption` > 0
                OR `glpi_computermodels`.`power_consumption` > 0
            )
            AND ($fallback_carbon_intensity_subquery) > 0, 1, 0)";
            $sopt[] = [
                'id'            => SearchOptions::IS_HISTORIZABLE,
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
                            'table' => Location::getTable(),
                            'joinparams' => [
                                'jointype' => 'child',
                                'nolink'   => true,
                                'beforejoin' => [
                                    'table' => GlpiLocation::getTable(),
                                    'joinparams' => [
                                        'jointype' => 'empty',
                                    ]
                                ]
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
        } else if ($itemtype === GlpiComputerType::class && in_array(GlpiComputer::class, PLUGIN_CARBON_TYPES)) {
            $computer_type_table = getTableForItemType(ComputerType::class);
            $sopt[] = [
                'id'             => SearchOptions::COMPUTER_TYPE_CATEGORY,
                'table'          => getTableForItemType(ComputerType::class),
                'field'          => 'category',
                'name'           => __('Category', 'carbon'),
                'datatype'       => 'specific',
                'searchtype'     => ['equals', 'notequals'],
                'massiveaction'  => false,
                'joinparams'     => [
                    'jointype'   => 'child'
                ]
            ];

            $sopt[] = [
                'id'           => SearchOptions::COMPUTER_TYPE_IS_IGNORED,
                'table'        => $computer_type_table,
                'field'        => 'is_ignore',
                'name'         => __('Ignore environmental impact', 'carbon'),
                'datatype'     => 'bool',
                'massiveaction' => false,
                'joinparams'     => [
                    'jointype'   => 'child'
                ],
                'computation' => "COALESCE(TABLE.`is_ignore`, 0)",
            ];
        } else if ($itemtype === GlpiMonitorType::class && in_array(GlpiMonitor::class, PLUGIN_CARBON_TYPES)) {
            $sopt[] = [
                'id'           => SearchOptions::MONITOR_TYPE_IS_IGNORED,
                'table'        => getTableForItemType(MonitorType::class),
                'field'        => 'is_ignore',
                'name'         => __('Ignore environmental impact', 'carbon'),
                'datatype'     => 'bool',
                'massiveaction' => false,
                'joinparams'     => [
                    'jointype'   => 'child'
                ],
                'computation' => "COALESCE(TABLE.`is_ignore`, 0)",
            ];
        } else if ($itemtype === GlpiNetworkEquipmentType::class && in_array(NetworkEquipment::class, PLUGIN_CARBON_TYPES)) {
            $sopt[] = [
                'id'           => SearchOptions::NETEQUIP_TYPE_IS_IGNORED,
                'table'        => getTableForItemType(NetworkEquipmentType::class),
                'field'        => 'is_ignore',
                'name'         => __('Ignore environmental impact', 'carbon'),
                'datatype'     => 'bool',
                'massiveaction' => false,
                'joinparams'     => [
                    'jointype'   => 'child'
                ],
                'computation' => "COALESCE(TABLE.`is_ignore`, 0)",
            ];
        } else if ($itemtype === GlpiMonitor::class && in_array($itemtype, PLUGIN_CARBON_TYPES)) {
            $computation = "IF(`glpi_monitors_id_b24d2115745b49f0b5cdaf2ebb5e9ffe`.`is_deleted` = 0
            AND `glpi_monitors_id_b24d2115745b49f0b5cdaf2ebb5e9ffe`.`is_template` = 0
            AND `glpi_plugin_carbon_locations_3d6da7fccf9233a3f1a4e41183391a41`.`plugin_carbon_sources_zones_id` > 0"
            // Do not check if an asset is ignored
            // . "AND `glpi_plugin_carbon_monitortypes_54b036337d1b9bbf4f13db0e1ae93bc9`.`is_ignore` = 0"
            . " AND (
                `glpi_plugin_carbon_monitortypes_54b036337d1b9bbf4f13db0e1ae93bc9`.`power_consumption` > 0
                OR `glpi_monitormodels`.`power_consumption` > 0
            ), 1, 0)";
            $sopt[] = [
                'id'            => SearchOptions::IS_HISTORIZABLE,
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
                            'table' => Location::getTable(),
                            'joinparams' => [
                                'jointype' => 'child',
                                'beforejoin' => [
                                    'table' => GlpiLocation::getTable(),
                                    'joinparams' => [
                                        'jointype' => 'empty',
                                        'beforejoin' => [
                                            'table' => GlpiComputer::getTable(),
                                            'linkfield' => 'items_id_peripheral',
                                            'joinparams' => [
                                                'jointype' => 'empty',
                                                'beforejoin' => [
                                                    'table' => Asset_PeripheralAsset::getTable(),
                                                    'joinparams' => [
                                                        'jointype' => 'custom_condition_only',
                                                        'condition' => [
                                                            'ON' => [
                                                                'NEWTABLE' => 'items_id_peripheral',
                                                                'REFTABLE' => 'id',
                                                            ],
                                                            'AND' => [
                                                                'itemtype_peripheral' => GlpiMonitor::getType(),
                                                            ]
                                                        ],
                                                    ]
                                                ]
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
                            ]
                        ],
                    ],
                ],
                'computation' => $computation,
            ];
        } else if ($itemtype === NetworkEquipment::class && in_array($itemtype, PLUGIN_CARBON_TYPES)) {
            $computation = "IF(`glpi_networkequipments_id_401e18dd2eaa15834dcd21d0f6fc677c`.`is_deleted` = 0
            AND `glpi_networkequipments_id_401e18dd2eaa15834dcd21d0f6fc677c`.`is_template` = 0
            AND `glpi_plugin_carbon_locations_3d6da7fccf9233a3f1a4e41183391a41`.`plugin_carbon_sources_zones_id` > 0"
            // Do not check if an asset is ignored
            //  . "AND `glpi_plugin_carbon_networkequipmenttypes_640a9703b62363e5d254356fb4df69ef`.`is_ignore` = 0
             . " AND (
                `glpi_plugin_carbon_networkequipmenttypes_640a9703b62363e5d254356fb4df69ef`.`power_consumption` > 0
                OR `glpi_networkequipmentmodels`.`power_consumption` > 0
            ), 1, 0)";
            $sopt[] = [
                'id'            => SearchOptions::IS_HISTORIZABLE,
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
                            'table' => Location::getTable(),
                            'joinparams' => [
                                'jointype' => 'child',
                                'nolink'   => true,
                                'beforejoin' => [
                                    'table' => GlpiLocation::getTable(),
                                    'joinparams' => [
                                        'jointype' => 'empty',
                                    ]
                                ]
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
}
