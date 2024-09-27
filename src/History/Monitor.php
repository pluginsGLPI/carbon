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

namespace GlpiPlugin\Carbon\History;

use CommonDBTM;
use Computer as GlpiComputer;
use Computer_Item;
use DBmysql;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Carbon\Engine\V1\EngineInterface;
use GlpiPlugin\Carbon\Engine\V1\Monitor as EngineMonitor;
use GlpiPlugin\Carbon\MonitorType;
use Location;
use Monitor as GlpiMonitor;
use MonitorType as GlpiMonitorType;
use MonitorModel as GlpiMonitorModel;
use QueryExpression;

class Monitor extends AbstractAsset
{
    protected static string $itemtype = GlpiMonitor::class;
    protected static string $type_itemtype  = GlpiMonitorType::class;
    protected static string $model_itemtype = GlpiMonitorModel::class;

    public static function getEngine(CommonDBTM $item): EngineInterface
    {
        return new EngineMonitor($item->getID());
    }

    public function getHistorizableQuery(): array
    {
        // Monitors must be attached to a computer to be used
        // then lets create the query based on the equivalent request for computers
        $item_table = self::$itemtype::getTable();
        $item_model_table = self::$model_itemtype::getTable();
        $computers_table = GlpiComputer::getTable();
        $computers_items_table = Computer_Item::getTable();
        $glpi_monitor_types_table = GlpiMonitorType::getTable();
        $glpi_monitor_types_fk = GlpiMonitorType::getForeignKeyField();
        $monitor_types_table = MonitorType::getTable();
        $request = (new Computer())->getHistorizableQuery();
        // Add joins to reach monitor from computer
        $request['INNER JOIN'][$computers_items_table] = [
            'FKEY' => [
                $computers_table => 'id',
                $computers_items_table => GlpiComputer::getForeignKeyField(),
                ['AND' => [Computer_Item::getTableField('itemtype') => self::$itemtype]],
            ]
        ];
        $request['INNER JOIN'][$item_table] = [
            'FKEY' => [
                $item_table => 'id',
                $computers_items_table => 'items_id',
            ],
        ];
        $request['INNER JOIN'][$glpi_monitor_types_table] = [
            'FKEY' => [
                $glpi_monitor_types_table => 'id',
                $item_table => $glpi_monitor_types_fk,
            ],
        ];
        $request['INNER JOIN'][$monitor_types_table] = [
            'FKEY' => [
                $glpi_monitor_types_table => 'id',
                $monitor_types_table => $glpi_monitor_types_fk,
            ],
        ];
        $request['INNER JOIN'][$item_model_table] = [
            'FKEY' => [
                $item_model_table => 'id',
                $item_table => 'monitormodels_id',
            ],
        ];

        // Replace SELECT on computer by select on monitor
        $request['SELECT'] = [
            self::$itemtype::getTableField('id'),
        ];
        $request['WHERE'] = [
            'AND' => [
                self::$itemtype::getTableField('is_deleted') => 0,
                self::$itemtype::getTableField('is_template') => 0,
                // Check the monitor is located the same place as the attached computer
                self::$itemtype::getTableField('locations_id') => new QueryExpression(DBmysql::quoteName(GlpiComputer::getTableField('locations_id'))),
                ['NOT' => [Location::getTableField('country') => '']],
                ['NOT' => [Location::getTableField('country') => null]],
                [
                    'OR' => [
                        MonitorType::getTableField('power_consumption') => ['>', 0],
                        self::$model_itemtype::getTableField('power_consumption') => ['>', 0],
                    ],
                ],
            ],
        ];

        return $request;
    }


    public static function showHistorizableDiagnosis(CommonDBTM $item)
    {
        global $DB;

        $history = new self();
        $request = $history->getHistorizableQuery();
        // Select fields to review
        $request['SELECT'] = [
            self::$itemtype::getTableField('is_deleted'),
            self::$itemtype::getTableField('is_template'),
            Computer_Item::getTableField('computers_id'),
            Location::getTableField('id as location_id'),
            Location::getTableField('country'),
            GlpiMonitorModel::getTableField('id as model_id'),
            GlpiMonitorModel::getTableField('power_consumption as model_power_consumption'),
            GlpiMonitorType::getTableField('id as type_id'),
            MonitorType::getTableField('id as plugin_carbon_type_id'),
            MonitorType::getTableField('power_consumption  as type_power_consumption'),
        ];
        // Change inner joins into left joins to identify missing data
        $request['LEFT JOIN'] = $request['INNER JOIN'];
        unset($request['INNER JOIN']);
        // remove where criterias
        unset($request['WHERE']);
        // Limit to the item only
        $request['WHERE'][self::$itemtype::getTableField('id')] = $item->getID();

        $iterator = $DB->request($request);
        $data = $iterator->current();

        // Each state is analyzed, with bool results
        // false means that data is missing or invalid for historization
        $status['is_deleted'] = ($data['is_deleted'] === 0);
        $status['is_template'] = ($data['is_template'] === 0);
        $status['has_computer'] = ($data['computers_id'] !== 0);
        $status['has_location'] = ($data['location_id'] !== 0);
        $status['has_country'] = (strlen($data['country'] ?? '') > 0);
        $status['has_model'] = ($data['model_id'] !== 0);
        $status['has_model_power_consumption'] = (($data['model_power_consumption'] ?? 0) !== 0);
        $status['has_type'] = ($data['type_id'] !== 0);
        $status['has_type_power_consumption'] = (($data['type_power_consumption'] ?? 0) !== 0);

        TemplateRenderer::getInstance()->display('@carbon/history/status-item.html.twig', [
            'have_status' => ($iterator->count() === 1),
            'status' => $status,
        ]);
    }
}
