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
use DBmysql;
use DbUtils;
use Glpi\DBAL\QueryExpression;
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
        $item_model_table = self::$model_itemtype::getTable();
        $assets_items_table = Asset_PeripheralAsset::getTable();
        $computers_table = GlpiComputer::getTable();
        $glpi_monitor_types_table = GlpiMonitorType::getTable();
        $glpi_monitor_types_fk = GlpiMonitorType::getForeignKeyField();
        $monitor_types_table = MonitorType::getTable();

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
                    'AND' => [Asset_PeripheralAsset::getTableField('itemtype_asset') => GlpiComputer::class]
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
        // TODO: determine if the computer is a server, a computer, a laptop, a tablet...
        // then adapt $this->endpoint depending on the result

        // Ask for embodied impact only
        $configuration = $this->analyzeHardware($item);

        $description = [
            'configuration' => $configuration,
            'usage' => [
                'avg_power' => 0,
            ],
        ];
        $response = $this->query($description);
        $impacts = $this->parseResponse($response);

        return $impacts;
    }

    protected function analyzeHardware(CommonDBTM $item): array
    {
        $configuration = [];

        // Disable usage
        $this->hardware['configuration'] = $configuration;
        $this->hardware['usage'] = [
            'avg_power' => 0,
        ];

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

        $request = [
            'SELECT' => new QueryExpression('COALESCE() as `power`'),
            'FROM' => $item_table,
            'LEFT JOIN' => [
                $item_model_table => [
                    $item_table => $model_fk,
                    $item_model_table => 'id',
                ],
                $item_glpi_type_table => [
                    'FKEY' => [
                        $item_table => $glpi_type_fk,
                        $item_glpi_type_table => 'id',
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
