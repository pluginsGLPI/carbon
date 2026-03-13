<?php

/**
 * -------------------------------------------------------------------------
 * Carbon plugin for GLPI
 *
 * @copyright Copyright (C) 2024-2025 Teclib' and contributors.
 * @copyright Copyright (C) 2024 by the carbon plugin team.
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

namespace GlpiPlugin\Carbon\Impact\Usage\Boavizta;

use CommonDBTM;
use Computer as GlpiComputer;
use ComputerModel as GlpiComputerModel;
use ComputerType as GlpiComputerType;
use DBmysql;
use DbUtils;
use DeviceProcessor;
use Glpi\DBAL\QueryExpression;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\Location;
use GlpiPlugin\Carbon\UsageImpact;
use GlpiPlugin\Carbon\UsageInfo;
use Infocom;
use Item_DeviceMemory;
use Item_DeviceProcessor;
use Item_Devices;
use Item_Disk;
use Location as GlpiLocation;

class Computer extends AbstractAsset
{
    protected static string $itemtype = GlpiComputer::class;
    protected static string $type_itemtype  = GlpiComputerType::class;
    protected static string $model_itemtype = GlpiComputerModel::class;

    protected string $endpoint        = 'server';

    public function getEvaluableQuery(string $itemtype, array $crit = [], bool $entity_restrict = true): array
    {
        $request = parent::getEvaluableQuery($itemtype);

        $item_table = getTableForItemType($itemtype);
        $computerUsageProfile_table = ComputerUsageProfile::getTable();
        $usage_info_table = UsageInfo::getTable();
        $request['INNER JOIN'][$usage_info_table] =  [
            'FKEY'   => [
                $item_table  => 'id',
                $usage_info_table => 'items_id',
                [
                    'AND' => [UsageInfo::getTableField('itemtype') => self::$itemtype],
                ],
            ],
        ];
        $request['INNER JOIN'][$computerUsageProfile_table] =  [
            'FKEY'   => [
                $usage_info_table  => ComputerUsageProfile::getForeignKeyField(),
                $computerUsageProfile_table => 'id',
            ],
        ];
        $request['WHERE'][] = [
            ['NOT' => [ComputerType::getTableField('category') => null]],
            [ComputerType::getTableField('category') => ['>', 0]],
        ];
        $request['WHERE'][] = $crit;

        return $request;
    }

    protected function doEvaluation(CommonDBTM $item): ?array
    {
        // TODO: determine if the computer is a server, a computer, a laptop, a tablet...
        // then adapt $this->endpoint depending on the result

        $type = $this->getType($item);
        $this->endpoint = $this->getEndpoint($type);

        // Find boavizta zone code
        $zone_code = Location::getZoneCode($item);
        if ($zone_code === null) {
            return null;
        }

        $average_power = $this->getAveragePower($item->getID());

        // Ask for usage impact only
        $configuration = $this->analyzeHardware($item);
        if (count($configuration) === 0) {
            return null;
        }
        $description = [
            'configuration' => $configuration,
            'usage' => [
                'usage_location' => $zone_code,
                'avg_power' => $average_power,
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
                    ],
                ],
                $computer_table => [
                    'FKEY' => [
                        $glpi_computer_type_table => 'id',
                        $computer_table           => 'computertypes_id',
                    ],
                ],
            ],
            'WHERE' => [
                GlpiComputer::getTableField('id') => $item->getID(),
            ],
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
                    ],
                ],
                $carbon_item_type_table => [
                    'FKEY' => [
                        $item_glpi_type_table => 'id',
                        $carbon_item_type_table => 'computertypes_id',
                    ],
                ],
            ],
            'WHERE' => [
                $itemtype::getTableField('id') => $id,
            ],
        ];

        $result = $DB->request($request);
        if ($result->count() === 0) {
            return null;
        }
        $power = $result->current()['power'];
        return $power ?? 0;
    }
}
