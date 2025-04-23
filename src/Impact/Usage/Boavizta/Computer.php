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
 * @copyright Copyright (C) 2024 by the carbon plugin team.
 * @license   MIT https://opensource.org/licenses/mit-license.php
 * @link      https://github.com/pluginsGLPI/carbon
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Carbon\Impact\Usage\Boavizta;

use CommonDBTM;
use Computer as GlpiComputer;
use ComputerModel as GlpiComputerModel;
use ComputerType as GlpiComputerType;
use DeviceProcessor;
use GlpiPlugin\Carbon\UsageInfo;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use DBmysql;
use DbUtils;
use GlpiPlugin\Carbon\Location;
use Item_DeviceMemory;
use Item_DeviceProcessor;
use Item_Devices;
use Item_Disk;
use Infocom;
use QueryExpression;

class Computer extends AbstractAsset
{
    protected static string $itemtype = GlpiComputer::class;
    protected static string $type_itemtype  = GlpiComputerType::class;
    protected static string $model_itemtype = GlpiComputerModel::class;

    protected string $endpoint        = 'server';

    public function getEvaluableQuery(array $crit = [], bool $entity_restrict = true): array
    {
        $item_table = self::$itemtype::getTable();
        $item_model_table = self::$model_itemtype::getTable();
        $glpi_computertypes_table = GlpiComputerType::getTable();
        $computertypes_table = ComputerType::getTable();
        $location_table = Location::getTable();
        $usage_info_table = UsageInfo::getTable();
        $computerUsageProfile_table = ComputerUsageProfile::getTable();
        $infocom_table = Infocom::getTable();

        $request = [
            'SELECT' => [
                self::$itemtype::getTableField('id'),
            ],
            'FROM' => $item_table,
            'INNER JOIN' => [
                $location_table => [
                    'FKEY'   => [
                        $item_table  => 'locations_id',
                        $location_table => 'id',
                    ]
                ],
                $usage_info_table => [
                    'FKEY'   => [
                        $item_table  => 'id',
                        $usage_info_table => 'computers_id',
                    ]
                ],
                $computerUsageProfile_table => [
                    'FKEY'   => [
                        $usage_info_table  => 'plugin_carbon_computerusageprofiles_id',
                        $computerUsageProfile_table => 'id',
                    ]
                ],
            ],
            'LEFT JOIN' => [
                $item_model_table => [
                    'FKEY'   => [
                        $item_table  => 'computermodels_id',
                        $item_model_table => 'id',
                    ]
                ],
                $glpi_computertypes_table => [
                    'FKEY'   => [
                        $item_table  => 'computertypes_id',
                        $glpi_computertypes_table => 'id',
                    ]
                ],
                $computertypes_table => [
                    'FKEY'   => [
                        $computertypes_table  => 'computertypes_id',
                        $glpi_computertypes_table => 'id',
                        [
                            'AND' => [
                                'NOT' => [GlpiComputerType::getTableField('id') => null],
                            ]
                        ]
                    ]
                ],
                $infocom_table => [
                    'FKEY' => [
                        $infocom_table => 'items_id',
                        $item_table => 'id',
                        ['AND' => [Infocom::getTableField('itemtype') => self::$itemtype]],
                    ]
                ],
            ],
            'WHERE' => [
                'AND' => [
                    self::$itemtype::getTableField('is_deleted') => 0,
                    self::$itemtype::getTableField('is_template') => 0,
                    ['NOT' => [Location::getTableField('boavizta_zone') => '']],
                    ['NOT' => [Location::getTableField('boavizta_zone') => null]],
                    [
                        'OR' => [
                            ComputerType::getTableField('power_consumption') => ['>', 0],
                            self::$model_itemtype::getTableField('power_consumption') => ['>', 0],
                        ],
                    ], [
                        'OR' => [
                            ['NOT' => [Infocom::getTableField('use_date') => null]],
                            ['NOT' => [Infocom::getTableField('delivery_date') => null]],
                            ['NOT' => [Infocom::getTableField('buy_date') => null]],
                            ['NOT' => [Infocom::getTableField('date_creation') => null]],
                            ['NOT' => [Infocom::getTableField('date_mod') => null]],
                        ]
                    ], [
                        'AND' => [
                            ['NOT' => [ComputerType::getTableField('category') => null]],
                            [ComputerType::getTableField('category') => ['>', 0]],
                        ]
                    ]
                ],
            ] + $crit
        ];

        if ($entity_restrict) {
            $entity_restrict = (new DbUtils())->getEntitiesRestrictCriteria($item_table, '', '', 'auto');
            $request['WHERE'] += $entity_restrict;
        }

        return $request;
    }

    protected function doEvaluation(CommonDBTM $item): ?array
    {
        // TODO: determine if the computer is a server, a computer, a laptop, a tablet...
        // then adapt $this->endpoint depending on the result

        $type = $this->getType($item);
        $this->endpoint = $this->getEndpoint($type);

        // Find boavizta zone  code
        $zone_code = $this->getZoneCode($item);
        if ($zone_code === null) {
            return null;
        }

        $average_power = $this->getAveragePower($item->getID());

        // Ask for embodied impact only
        $configuration = $this->analyzeHardware($item);
        if (count($configuration) === 0) {
            return null;
        }
        $description = [
            'configuration' => $configuration,
            'usage' => [
                'usage_location' => $zone_code,
                'avg_power' => $average_power
            ],
        ];
        $response = $this->query($description);
        $impacts = $this->parseResponse($response);

        return $impacts;
    }

    /**
     * Get the type of the computer
     * @param CommonDBTM $item
     * @return int The type of the computer
     */
    protected function getType(CommonDBTM $item): int
    {
        /** @var DBmysql $DB */
        global $DB;

        $computer_table = GlpiComputer::getTable();
        $computer_type_table = ComputerType::getTable();
        $glpi_computer_type_table = GlpiComputerType::getTable();
        $result = $DB->request([
            'SELECT'     => ComputerType::getTableField('category'),
            'FROM'       => $computer_type_table,
            'INNER JOIN' => [
                $glpi_computer_type_table => [
                    'FKEY' => [
                        $computer_type_table => 'computertypes_id',
                        $glpi_computer_type_table => 'id',
                    ]
                ],
                $computer_table => [
                    'FKEY' => [
                        $glpi_computer_type_table => 'id',
                        $computer_table           => 'computertypes_id'
                    ],
                ],
            ],
            'WHERE' => [
                GlpiComputer::getTableField('id') => $item->getID(),
            ]
        ]);
        $row_count = $result->count();
        if ($row_count === 0) {
            return ComputerType::CATEGORY_UNDEFINED;
        } elseif ($result->count() > 1) {
            trigger_error(sprintf('SQL query shall return 1 row, got %d', $row_count), WARNING);
        }

        return $result->current()['category'];
    }

    /**
     * Get the endpoint to use for the given type
     */
    protected function getEndpoint(int $type)
    {
        switch ($type) {
            case ComputerType::CATEGORY_SERVER:
                return 'server';
            case ComputerType::CATEGORY_LAPTOP:
                return 'terminal/laptop';
            case ComputerType::CATEGORY_TABLET:
                return 'terminal/tablet';
            case ComputerType::CATEGORY_SMARTPHONE:
                return 'terminal/smartphone';
        }

        // ComputerType::CATEGORY_UNDEFINED
        // ComputerType::CATEGORY_DESKTOP
        return 'terminal/desktop';
    }

    protected function analyzeHardware(CommonDBTM $item): array
    {
        $configuration = [];
        // Yes, string expected here.
        $iterator = Item_Devices::getItemsAssociatedTo($item->getType(), (string) $item->getID());
        foreach ($iterator as $item_device) {
            switch ($item_device->getType()) {
                case Item_DeviceProcessor::class:
                    $cpu = DeviceProcessor::getById($item_device->fields['deviceprocessors_id']);
                    if ($cpu) {
                        if (isset($configuration['cpu'])) {
                            // The server does not support several CPU with different specifications
                            // then, just increment CPU count
                            $configuration['cpu']['units']++;
                        } else {
                            $configuration['cpu'] = [
                                'units'      => 1,
                                'name'       => $cpu->fields['designation'],
                            ];
                            if (isset($item_device->fields['nbcores'])) {
                                $configuration['cpu']['core_units'] = $item_device->fields['nbcores'];
                            }
                        }
                    }
                    break;
                case Item_DeviceMemory::class:
                    $configuration['memory'][] = [
                        'units' => 1,
                        'capacity' => ceil($item_device->fields['size'] / 1024),
                    ];
                    break;
                case Item_Disk::class:
                    $configuration['disk'][] = [
                        'units' => 1,
                        'capacity' => ceil($item_device->fields['capacity'] / 1024),
                    ];
                    break;
            }
        }

        return $configuration;
    }

    protected function getAveragePower(int $id): ?int
    {
        /** @var DBmysql $DB */
        global $DB;

        $dbutil = new DbUtils();
        $itemtype = static::$itemtype;
        $glpi_type_fk = static::$type_itemtype::getForeignKeyField();
        $model_fk = static::$model_itemtype::getForeignKeyField();
        $item_table = $dbutil->getTableForItemType($itemtype);
        $item_glpi_type_table  = $dbutil->getTableForItemType(static::$type_itemtype);
        $item_model_table = $dbutil->getTableForItemType(static::$model_itemtype);
        $carbon_item_type_table = $dbutil->getTableForItemType(ComputerType::class);

        $type_power  = ComputerType::getTableField('power_consumption');
        $type_power = DBmysql::quoteName($type_power);
        $model_power = static::$model_itemtype::getTableField('power_consumption');
        $model_power = DBmysql::quoteName($model_power);

        $request = [
            'SELECT' => new QueryExpression("COALESCE({$model_power}, {$type_power}, null) as `power`"),
            'FROM' => $item_table,
            'LEFT JOIN' => [
                $item_model_table => [
                    'FKEY' => [
                        $item_table => $model_fk,
                        $item_model_table => 'id',
                    ],
                ],
                $item_glpi_type_table => [
                    'FKEY' => [
                        $item_table => $glpi_type_fk,
                        $item_glpi_type_table => 'id',
                    ]
                ],
                $carbon_item_type_table => [
                    'FKEY' => [
                        $item_glpi_type_table => 'id',
                        $carbon_item_type_table => 'computertypes_id',
                    ]
                ]
            ],
            'WHERE' => [
                $itemtype::getTableField('id') => $id
            ]
        ];

        $result = $DB->request($request);
        if ($result->count() === 0) {
            return null;
        }
        $power = $result->current()['power'];
        return $power ?? 0;
    }
}
