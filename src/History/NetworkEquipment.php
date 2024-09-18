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
use DbUtils;
use Location;
use NetworkEquipment as GlpiNetworkEquipment;
use NetworkEquipmentType;
use NetworkEquipmentModel;
use GlpiPlugin\Carbon\Engine\V1\EngineInterface;
use GlpiPlugin\Carbon\Engine\V1\Monitor as EngineMonitor;

class NetworkEquipment extends AbstractAsset
{
    protected static string $itemtype = GlpiNetworkEquipment::class;
    protected static string $type_itemtype  = NetworkEquipmentType::class;
    protected static string $model_itemtype = NetworkEquipmentModel::class;

    public static function getEngine(CommonDBTM $item): EngineInterface
    {
        return new EngineMonitor($item->getID());
    }

    public function getHistorizableQuery(): array
    {
        $item_table = self::$itemtype::getTable();
        $item_model_table = self::$model_itemtype::getTable();
        $location_table = Location::getTable();
        $request = [
            'SELECT' => self::$itemtype::getTableField('*'),
            'FROM'   => self::$itemtype::getTable(),
            'INNER JOIN' => [
                $item_model_table => [
                    'FKEY'   => [
                        $item_table  => 'networkequipmentmodels_id',
                        $item_model_table => 'id',
                    ]
                ],
                $location_table => [
                    'FKEY'   => [
                        $item_table  => 'locations_id',
                        $location_table => 'id',
                    ]
                ],
            ],
            'WHERE' => [
                self::$itemtype::getTableField('is_deleted') => 0,
                self::$itemtype::getTableField('is_template') => 0,
                ['NOT' => [Location::getTableField('country') => '']],
                ['NOT' => [Location::getTableField('country') => null]],
            ]
        ];

        $entity_restrict = (new DbUtils())->getEntitiesRestrictCriteria($item_table, '', '', 'auto');
        $request['WHERE'] += $entity_restrict;

        return $request;
    }
}
