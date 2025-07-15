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

use DBmysql;
use DbUtils;
use CommonDBTM;
use Monitor as GlpiMonitor;
use MonitorType as GlpiMonitorType;
use MonitorModel as GlpiMonitorModel;
use QueryExpression;

class Monitor extends AbstractAsset
{
    protected static string $itemtype = GlpiMonitor::class;
    protected static string $type_itemtype  = GlpiMonitorType::class;
    protected static string $model_itemtype = GlpiMonitorModel::class;

    protected string $endpoint        = 'peripheral/monitor';

    protected function doEvaluation(CommonDBTM $item): ?array
    {
        // TODO: determine if the computer is a server, a computer, a laptop, a tablet...
        // then adapt $this->endpoint depending on the result

        // Ask for embodied impact only
        $configuration = $this->analyzeHardware($item);

        $description = [
            'configuration' => $configuration,
            'usage' => [
                'avg_power' => 0
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
            'avg_power' => 0
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

        $model_power = static::$model_itemtype::getTableField('power_consumption');
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
                    ]
                ],
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
