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
