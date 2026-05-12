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
use DBmysql;
use DbUtils;
use Glpi\Asset\Asset_PeripheralAsset;
use Glpi\DBAL\QueryExpression;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\Location;
use GlpiPlugin\Carbon\MonitorType;
use GlpiPlugin\Carbon\UsageInfo;
use Monitor as GlpiMonitor;
use MonitorModel as GlpiMonitorModel;
use MonitorType as GlpiMonitorType;

class Monitor extends AbstractAsset
{
    protected static string $itemtype = GlpiMonitor::class;
    protected static string $type_itemtype  = GlpiMonitorType::class;
    protected static string $model_itemtype = GlpiMonitorModel::class;

    protected string $endpoint        = 'peripheral/monitor';

    public function getEvaluableQuery(string $itemtype, array $crit = [], bool $entity_restrict = true): array
    {
        // TODO : build the evaluable query from the computer evaluable query
        // the location should behandled like done in History namespace

        $item_table = self::$itemtype::getTable();
        self::$model_itemtype::getTable();
        $assets_items_table = Asset_PeripheralAsset::getTable();
        $computers_table = GlpiComputer::getTable();

        $request = parent::getEvaluableQuery($itemtype);
        $parent_inner_joins = $request['INNER JOIN'];
        $parent_left_joins  = $request['LEFT JOIN'];
        unset($request['INNER JOIN'], $request['LEFT JOIN']);

        $request['LEFT JOIN'][$assets_items_table] = [
            'FKEY' => [
                $assets_items_table => 'items_id_peripheral',
                $item_table => 'id',
                [
                    'AND' => [
                        Asset_PeripheralAsset::getTableField('itemtype_peripheral') => self::$itemtype,
                        Asset_PeripheralAsset::getTableField('itemtype_asset') => GlpiComputer::class,
                    ],
                ],
            ],
        ];
        $request['INNER JOIN'][$computers_table] = [
            'FKEY' => [
                $computers_table => 'id',
                $assets_items_table => 'items_id_asset',
                [
                    'AND' => [Asset_PeripheralAsset::getTableField('itemtype_asset') => GlpiComputer::class],
                ],
            ],
        ];

        // re-add inner joins of computer, after those for monitor
        // Needed to join tables before theyr foreign keys are used
        $request['INNER JOIN'] = array_merge($request['INNER JOIN'], $parent_inner_joins);
        $request['LEFT JOIN'] = array_merge($request['LEFT JOIN'], $parent_left_joins);

        // Replace SELECT on computer by select on monitor
        $request['SELECT'] = [
            self::$itemtype::getTableField('id'),
        ];

        // Append criteria to the WHERE clause
        $request['WHERE'][] = $crit;

        return $request;
    }

    protected function doEvaluation(CommonDBTM $item): ?array
    {
        // Ask for embodied impact only
        $configuration = $this->analyzeHardware();
        $this->endpoint .= '?' . $this->getCriteriasQueryString();

        // Find boavizta zone code
        $zone_code = Location::getZoneCode($item);
        if ($zone_code === null) {
            return null;
        }
        // Calculate the average power
        $average_power = $this->getAveragePower($item->getID());
        $lifespan = (new UsageInfo())->getLifespanInHours($item);
        if ($lifespan === null) {
            return null;
        }
        $use_ratio = $this->getUseRatio();
        $time_workload = $this->getWorkloadRepartition();

        $description = [
            'configuration' => $configuration,
            'usage' => [
                'avg_power'       => $average_power,
                'usage_location'  => $zone_code,
                'use_time_ratio'  => $use_ratio,
                'hours_life_time' => $lifespan,
                'time_workload'   => $time_workload,
            ],
        ];
        $response = $this->query($description);
        $impacts = $this->client->parseResponse($response, 'use');

        return $impacts;
    }

    protected function analyzeHardware(): array
    {
        $configuration = [];

        return $configuration;
    }

    protected function getAveragePower_disabled(int $id): ?int
    {
        /** @var DBmysql $DB */
        global $DB;

        $dbutil = new DbUtils();
        $itemtype = static::$itemtype;
        $model_fk = static::$model_itemtype::getForeignKeyField();
        $item_table = $dbutil->getTableForItemType($itemtype);
        $item_model_table = $dbutil->getTableForItemType(static::$model_itemtype);
        $carbon_item_type_table = $dbutil->getTableForItemType(MonitorType::class);

        $type_power  = MonitorType::getTableField('power_consumption');
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
                $carbon_item_type_table => [
                    'FKEY' => [
                        $item_table => 'monitortypes_id',
                        $carbon_item_type_table => 'monitortypes_id',
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

    /**
     * Calculate the use time ratio from the usage profile
     *
     * @return float Ratio between 0 and 1
     */
    protected function getUseRatio(): float
    {
        $usage_profile = new ComputerUsageProfile();
        $usage_profile_table = ComputerUsageProfile::getTable();
        $usage_info_table = getTableForItemType(UsageInfo::class);
        $assets_items_table = Asset_PeripheralAsset::getTable();
        $usage_profile->getFromDBByRequest([
            'INNER JOIN' => [
                $usage_info_table => [
                    'FKEY' => [
                        $usage_info_table => 'plugin_carbon_computerusageprofiles_id',
                        $usage_profile_table => 'id',
                    ],
                ],
                $assets_items_table => [
                    'FKEY' => [
                        $assets_items_table => 'items_id_asset',
                        $usage_info_table => 'items_id',
                        [
                            'AND' => [
                                Asset_PeripheralAsset::getTableField('itemtype_peripheral') => self::$itemtype,
                                Asset_PeripheralAsset::getTableField('itemtype_asset') => GlpiComputer::class,
                            ],
                        ],
                    ],
                ],
            ],
            'WHERE' => [
                UsageInfo::getTableField('itemtype') => GlpiComputer::class,
                UsageInfo::getTableField('items_id') => $this->item->getID(),
            ],
        ]);

        return $usage_profile->getPoweredOnRatio();
    }
}
