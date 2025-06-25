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

namespace GlpiPlugin\Carbon;

use CommonDBTM;
use Computer;
use Location as GlpiLocation;
use ComputerType as GlpiComputerType;
use ComputerModel;
use Computer_Item;
use Glpi\Asset\Asset_PeripheralAsset;
use Monitor;
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
    public const IS_HISTORIZABLE   = self::SEARCH_OPTION_BASE + 502;
    public const USAGE_PROFILE     = self::SEARCH_OPTION_BASE + 501;

    public const CARBON_EMISSION_DATE             = self::SEARCH_OPTION_BASE + 600;
    public const CARBON_EMISSION_ENERGY_PER_DAY   = self::SEARCH_OPTION_BASE + 601;
    public const CARBON_EMISSION_PER_DAY          = self::SEARCH_OPTION_BASE + 602;
    public const CARBON_EMISSION_ENERGY_QUALITY   = self::SEARCH_OPTION_BASE + 603;
    public const CARBON_EMISSION_EMISSION_QUALITY = self::SEARCH_OPTION_BASE + 604;
    public const CARBON_EMISSION_CALC_DATE        = self::SEARCH_OPTION_BASE + 605;
    public const CARBON_EMISSION_ENGINE           = self::SEARCH_OPTION_BASE + 606;
    public const CARBON_EMISSION_ENGINE_VER       = self::SEARCH_OPTION_BASE + 607;

    public const USAGE_IMPACT_DATE = self::SEARCH_OPTION_BASE + 700;
    public const USAGE_IMPACT_GWP = self::SEARCH_OPTION_BASE + 701;
    public const USAGE_IMPACT_GWP_QUALITY = self::SEARCH_OPTION_BASE + 702;
    public const USAGE_IMPACT_ADP = self::SEARCH_OPTION_BASE + 703;
    public const USAGE_IMPACT_ADP_QUALITY = self::SEARCH_OPTION_BASE + 704;
    public const USAGE_IMPACT_PE = self::SEARCH_OPTION_BASE + 705;
    public const USAGE_IMPACT_PE_QUALITY = self::SEARCH_OPTION_BASE + 706;

    public const COMPUTER_TYPE_CATEGORY = self::SEARCH_OPTION_BASE + 800;

    public const LOCATION_BOAVIZTA_ZONE = self::SEARCH_OPTION_BASE + 900;

    /*
     * Get search options added to a core itemtype by the plugin
     *
     * @param string $itemtype
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
        }

        if ($itemtype === Computer::class && in_array($itemtype, PLUGIN_CARBON_TYPES)) {
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
        } else if ($itemtype === GlpiComputerType::class && in_array(Computer::class, PLUGIN_CARBON_TYPES)) {
            $sopt[] = [
                'id'             => SearchOptions::COMPUTER_TYPE_CATEGORY,
                'table'          => getTableForItemType(ComputerType::class),
                'field'          => 'category',
                'name'           => __('Category', 'carbon'),
                'datatype'       => 'specific',
                'searchtype'     => ['equals', 'notequals'],
                'massive_action' => false,
                'joinparams'     => [
                    'jointype'   => 'child'
                ]
            ];
        } else if ($itemtype === Monitor::class && in_array($itemtype, PLUGIN_CARBON_TYPES)) {
            $computation = "IF(`glpi_monitors_id_fd9c1a8262e8f3b6e96bc8948f2a6226`.`is_deleted` = 0
            AND `glpi_monitors_id_fd9c1a8262e8f3b6e96bc8948f2a6226`.`is_template` = 0
            AND NOT `glpi_locations_fad8b1764dcda16e3822068239df73f2`.`country`  = ''
            AND NOT `glpi_locations_fad8b1764dcda16e3822068239df73f2`.`country` IS NULL
            AND (
                `glpi_plugin_carbon_monitortypes_54b036337d1b9bbf4f13db0e1ae93bc9`.`power_consumption` > 0
                OR `glpi_monitormodels`.`power_consumption` > 0
            ), 1, 0)";
            /** @phpstan-ignore if.alwaysFalse */
            if (version_compare(GLPI_VERSION, '11.0', '>=')) {
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
                                'table' => GlpiLocation::getTable(),
                                'joinparams' => [
                                    'jointype' => 'empty',
                                    'nolink'   => true,
                                    'beforejoin' => [
                                        'table' => Computer::getTable(),
                                        'linkfield' => 'items_id_peripheral',
                                        'joinparams' => [
                                            'jointype' => 'empty',
                                            'beforejoin' => [
                                                // ignore next line error until GLPI 11 is mandatory
                                                /** @phpstan-ignore-next-line */
                                                'table' => Asset_PeripheralAsset::getTable(),
                                                'joinparams' => [
                                                    'jointype' => 'custom_condition_only',
                                                    'condition' => [
                                                        'ON' => [
                                                            'NEWTABLE' => 'items_id_peripheral',
                                                            'REFTABLE' => 'id',
                                                        ],
                                                        'AND' => [
                                                            'itemtype_peripheral' => Monitor::getType(),
                                                        ]
                                                    ],
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
            } else {
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
            }
        } else if ($itemtype === NetworkEquipment::class && in_array($itemtype, PLUGIN_CARBON_TYPES)) {
            $computation = "IF(`glpi_networkequipments_id_aef00423a27f97ae31ca50f63fb1a6fb`.`is_deleted` = 0
            AND `glpi_networkequipments_id_aef00423a27f97ae31ca50f63fb1a6fb`.`is_template` = 0
            AND NOT `glpi_locations`.`country`  = ''
            AND NOT `glpi_locations`.`country` IS NULL
            AND (
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
}
