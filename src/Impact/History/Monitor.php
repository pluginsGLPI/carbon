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

namespace GlpiPlugin\Carbon\Impact\History;

use CommonDBTM;
use Computer as GlpiComputer;
use ComputerModel as GlpiComputerModel;
use ComputerType as GlpiComputerType;
use Computer_Item;
use DBmysql;
use DbUtils;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\Engine\V1\EngineInterface;
use GlpiPlugin\Carbon\Engine\V1\Monitor as EngineMonitor;
use GlpiPlugin\Carbon\MonitorType;
use GlpiPlugin\Carbon\UsageImpact;
use GlpiPlugin\Carbon\UsageInfo;
use Infocom;
use Location;
use Monitor as GlpiMonitor;
use MonitorType as GlpiMonitorType;
use MonitorModel as GlpiMonitorModel;

class Monitor extends AbstractAsset
{
    protected static string $itemtype = GlpiMonitor::class;
    protected static string $type_itemtype  = GlpiMonitorType::class;
    protected static string $model_itemtype = GlpiMonitorModel::class;

    public static function getEngine(CommonDBTM $item): EngineInterface
    {
        return new EngineMonitor($item);
    }

    public function getEvaluableQuery(array $crit = [], bool $entity_restrict = true): array
    {
        // Monitors must be attached to a computer to be used
        // then lets create the query based on the equivalent request for computers
        $item_table = self::$itemtype::getTable();
        $item_model_table = self::$model_itemtype::getTable();
        $computers_table = GlpiComputer::getTable();
        $computers_items_table = Computer_Item::getTable();
        $computer_model_table = GlpiComputerModel::getTable();
        $glpi_monitor_types_table = GlpiMonitorType::getTable();
        $glpi_monitor_types_fk = GlpiMonitorType::getForeignKeyField();
        $monitor_types_table = MonitorType::getTable();
        $infocom_table = Infocom::getTable();
        $location_table = Location::getTable();

        $request = (new Computer())->getEvaluableQuery();
        $computer_inner_joins = $request['INNER JOIN'];
        $computer_left_joins  = $request['LEFT JOIN'];
        unset($request['INNER JOIN'], $request['LEFT JOIN']);

        $glpi_computertypes_table = GlpiComputerType::getTable();
        $computertypes_table = ComputerType::getTable();
        unset($computer_left_joins[$infocom_table]);
        unset($computer_left_joins[$computer_model_table]);
        unset($computer_left_joins[$glpi_computertypes_table]);
        unset($computer_left_joins[$computertypes_table]);

        // Add joins to reach monitor from computer
        $request['FROM'] = $item_table;
        $request['LEFT JOIN'][$computers_items_table] = [
            'FKEY' => [
                $computers_items_table => 'items_id',
                $item_table => 'id',
                ['AND' => [Computer_Item::getTableField('itemtype') => self::$itemtype]],
            ]
        ];
        $request['INNER JOIN'][$computers_table] = [
            'FKEY' => [
                $computers_table => 'id',
                $computers_items_table => GlpiComputer::getForeignKeyField(),
            ],
        ];
        $request['LEFT JOIN'][$glpi_monitor_types_table] = [
            'FKEY' => [
                $glpi_monitor_types_table => 'id',
                $item_table => $glpi_monitor_types_fk,
            ],
        ];
        $request['LEFT JOIN'][$monitor_types_table] = [
            'FKEY' => [
                $glpi_monitor_types_table => 'id',
                $monitor_types_table => $glpi_monitor_types_fk,
            ],
        ];
        $request['LEFT JOIN'][$item_model_table] = [
            'FKEY' => [
                $item_model_table => 'id',
                $item_table => 'monitormodels_id',
            ],
        ];
        $request['LEFT JOIN'][$infocom_table] = [
            'FKEY' => [
                $infocom_table => 'items_id',
                $item_table => 'id',
                ['AND' => [Infocom::getTableField('itemtype') => self::$itemtype]],
            ]
        ];

        // re-add inner joins of computer, after those for monitor
        // Needed to join tables before theyr foreign keys are used
        $request['INNER JOIN'] = array_merge($request['INNER JOIN'], $computer_inner_joins);
        $request['LEFT JOIN'] = array_merge($request['LEFT JOIN'], $computer_left_joins);

        // Replace SELECT on computer by select on monitor
        $request['SELECT'] = [
            self::$itemtype::getTableField('id'),
        ];
        $request['WHERE'] = [
            'AND' => [
                self::$itemtype::getTableField('is_deleted') => 0,
                self::$itemtype::getTableField('is_template') => 0,
                // Check the monitor is located the same place as the attached computer
                // self::$itemtype::getTableField('locations_id') => new QueryExpression(DBmysql::quoteName(GlpiComputer::getTableField('locations_id'))),
                [
                    'OR' => [
                        [
                            ['NOT' => [Location::getTableField('country') => '']],
                            ['NOT' => [Location::getTableField('country') => null]],
                        ],
                        [
                            ['NOT' => [Location::getTableField('state') => '']],
                            ['NOT' => [Location::getTableField('state') => null]],
                        ]
                    ]
                ],
                [
                    'OR' => [
                        MonitorType::getTableField('power_consumption') => ['>', 0],
                        self::$model_itemtype::getTableField('power_consumption') => ['>', 0],
                    ],
                ],
            ],
        ] + $crit;

        if ($entity_restrict) {
            $entity_restrict = (new DbUtils())->getEntitiesRestrictCriteria($item_table, '', '', 'auto');
            $request['WHERE'] += $entity_restrict;
        }

        return $request;
    }

    public static function getHistorizableDiagnosis(CommonDBTM $item): ?array
    {
        /** @var DBmysql $DB */
        global $DB;

        $history = new self();
        $request = $history->getEvaluableQuery();
        // Select fields to review
        $request['SELECT'] = [
            self::$itemtype::getTableField('is_deleted'),
            self::$itemtype::getTableField('is_template'),
            Computer_Item::getTableField('computers_id'),
            UsageInfo::getTableField('plugin_carbon_computerusageprofiles_id'),
            Location::getTableField('id as location_id'),
            Location::getTableField('state'),
            Location::getTableField('country'),
            GlpiMonitorModel::getTableField('id as model_id'),
            GlpiMonitorModel::getTableField('power_consumption as model_power_consumption'),
            GlpiMonitorType::getTableField('id as type_id'),
            MonitorType::getTableField('id as plugin_carbon_type_id'),
            MonitorType::getTableField('power_consumption  as type_power_consumption'),
            Infocom::getTableField('use_date'),
            Infocom::getTableField('delivery_date'),
            Infocom::getTableField('buy_date'),
        ];
        // Change inner joins into left joins to identify missing data
        // Warning : the order of the array merge below is important or the resulting SQL query will fail
        $request['LEFT JOIN'] = $request['LEFT JOIN'] + $request['INNER JOIN'];
        unset($request['INNER JOIN']);
        // remove where criterias
        unset($request['WHERE']);
        // Limit to the item only
        $request['WHERE'][self::$itemtype::getTableField('id')] = $item->getID();

        $iterator = $DB->request($request);
        $data = $iterator->current();
        if ($data === null) {
            return null;
        }

        // Each state is analyzed, with bool results
        // false means that data is missing or invalid for historization
        $status['is_deleted'] = ($data['is_deleted'] === 0);   // Actually the result is whether it is "not deleted"
        $status['is_template'] = ($data['is_template'] === 0); // Actually the result is whether it is "not template"
        $status['has_computer'] = !GlpiComputer::isNewID($data['computers_id']);
        $status['has_usage_profile'] = !ComputerUsageProfile::isNewID($data['plugin_carbon_computerusageprofiles_id']);
        $status['has_location'] = !Location::isNewID($data['location_id']);
        $status['has_state_or_country'] = (strlen($data['state'] ?? '') > 0) || (strlen($data['country'] ?? '') > 0);
        $status['has_model'] = !GlpiMonitorModel::isNewID($data['model_id']);
        $status['has_model_power_consumption'] = !GlpiMonitorType::isNewID($data['model_power_consumption']);
        $status['has_type'] = !GlpiMonitorType::isNewID($data['type_id']);
        $status['has_type_power_consumption'] = (($data['type_power_consumption'] ?? 0) !== 0);

        $item_oldest_date = $data['use_date']
            ?? $data['delivery_date']
            ?? $data['buy_date']
            ?? $data['date_creation']
            // ?? $data['date_mod']
            ?? null;
        $status['has_inventory_entry_date'] = ($item_oldest_date !== null);

        return $status;
    }

    public static function showHistorizableDiagnosis(CommonDBTM $item)
    {
        $status = self::getHistorizableDiagnosis($item);
        $usage_impact = new UsageImpact();
        $usage_impact->getFromDBByCrit([
            'itemtype' => $item::getType(),
            'items_id' => $item->getID(),
        ]);
        TemplateRenderer::getInstance()->display('@carbon/history/status-item.html.twig', [
            'has_status' => ($status !== null),
            'status' => $status,
        ]);
    }
}
