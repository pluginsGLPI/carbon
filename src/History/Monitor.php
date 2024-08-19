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
use DbUtils;
use GlpiPlugin\Carbon\Engine\V1\EngineInterface;
use GlpiPlugin\Carbon\Engine\V1\Monitor as EngineMonitor;
use Monitor as GlpiMonitor;
use MonitorType;
use MonitorModel;

class Monitor extends AbstractAsset
{
    protected static string $itemtype = GlpiMonitor::class;
    protected static string $type_itemtype  = MonitorType::class;
    protected static string $model_itemtype = MonitorModel::class;

    public static function getEngine(CommonDBTM $item): EngineInterface
    {
        return new EngineMonitor($item->getID());
    }

    public function getHistorizableQuery(): array
    {
        $monitors_table = self::$itemtype::getTable();
        $computers_table = GlpiComputer::getTable();
        $computers_items_table = Computer_Item::getTable();
        $glpi_monitors_table = GlpiMonitor::getTable();
        $request = (new Computer())->getHistorizableQuery();
        $request['INNER JOIN'][$computers_items_table] = [
            'FKEY' => [
                $computers_table => 'id',
                $computers_items_table => GlpiComputer::getForeignKeyField(),
            ]
        ];
        $request['INNER JOIN'][$glpi_monitors_table] = [
            'FKEY' => [
                $glpi_monitors_table => 'id',
                $computers_items_table => 'items_id',
                ['AND' => [Computer_Item::getTableField('itemtype') => self::$itemtype]],
            ],
        ];

        $request['WHERE']['AND'] += [
            self::$itemtype::getTableField('is_deleted') => 0,
            self::$itemtype::getTableField('is_template') => 0,
        ];

        return $request;
    }
}
