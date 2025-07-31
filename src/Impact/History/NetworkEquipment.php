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
use DBmysql;
use DbUtils;
use Glpi\Application\View\TemplateRenderer;
use Infocom;
use Location;
use NetworkEquipment as GlpiNetworkEquipment;
use NetworkEquipmentType as GlpiNetworkEquipmentType;
use NetworkEquipmentModel as GlpiNetworkEquipmentModel;
use GlpiPlugin\Carbon\Engine\V1\EngineInterface;
use GlpiPlugin\Carbon\Engine\V1\NetworkEquipment as EngineNetworkEquipment;
use GlpiPlugin\Carbon\NetworkEquipmentType;
use GlpiPlugin\Carbon\UsageImpact;

class NetworkEquipment extends AbstractAsset
{
    protected static string $itemtype = GlpiNetworkEquipment::class;
    protected static string $type_itemtype  = GlpiNetworkEquipmentType::class;
    protected static string $model_itemtype = GlpiNetworkEquipmentModel::class;

    public static function getEngine(CommonDBTM $item): EngineInterface
    {
        return new EngineNetworkEquipment($item);
    }

    public function getEvaluableQuery(array $crit = [], bool $entity_restrict = true): array
    {
        $item_table = self::$itemtype::getTable();
        $item_model_table = self::$model_itemtype::getTable();
        $item_glpitype_table = self::$type_itemtype::getTable();
        $item_type_table = NetworkEquipmentType::getTable();
        $location_table = Location::getTable();
        $infocom_table = Infocom::getTable();

        $request = [
            'SELECT' => self::$itemtype::getTableField('id'),
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
                $item_glpitype_table => [
                    'FKEY'   => [
                        $item_table  => 'networkequipmenttypes_id',
                        $item_glpitype_table => 'id',
                    ]
                ],
            ],
            'LEFT JOIN' => [
                $item_type_table => [
                    'FKEY'   => [
                        $item_type_table  => 'networkequipmenttypes_id',
                        $item_glpitype_table => 'id',
                        [
                            'AND' => [
                                'NOT' => [GlpiNetworkEquipmentType::getTableField('id') => null],
                            ]
                        ]
                    ]
                ],
                $infocom_table => [
                    'FKEY' => [
                        $infocom_table => 'items_id',
                        $item_table => 'id',
                        ['AND' => ['itemtype' => self::$itemtype]],
                    ]
                ],
            ],
            'WHERE' => [
                self::$itemtype::getTableField('is_deleted') => 0,
                self::$itemtype::getTableField('is_template') => 0,
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
                        NetworkEquipmentType::getTableField('power_consumption') => ['>', 0],
                        self::$model_itemtype::getTableField('power_consumption') => ['>', 0],
                    ],
                ],
                [
                    'OR' => [
                        ['NOT' => [Infocom::getTableField('use_date') => null]],
                        ['NOT' => [Infocom::getTableField('delivery_date') => null]],
                        ['NOT' => [Infocom::getTableField('buy_date') => null]],
                        ['NOT' => [Infocom::getTableField('date_creation') => null]],
                        // ['NOT' => [Infocom::getTableField('date_mod') => null]],
                    ]
                ]
            ] + $crit
        ];

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
            Location::getTableField('id as location_id'),
            Location::getTableField('state'),
            Location::getTableField('country'),
            GlpiNetworkEquipmentModel::getTableField('id as model_id'),
            GlpiNetworkEquipmentModel::getTableField('power_consumption as model_power_consumption'),
            GlpiNetworkEquipmentType::getTableField('id as type_id'),
            NetworkEquipmentType::getTableField('id as plugin_carbon_type_id'),
            NetworkEquipmentType::getTableField('power_consumption  as type_power_consumption'),
            Infocom::getTableField('use_date'),
            Infocom::getTableField('delivery_date'),
            Infocom::getTableField('buy_date'),
            self::$itemtype::getTableField('date_creation'),
            self::$itemtype::getTableField('date_mod'),
        ];
        $infocom_table = Infocom::getTable();
        $item_table = self::$itemtype::getTable();
        $request['LEFT JOIN'][$infocom_table] = [
            'FKEY' => [
                $infocom_table => 'items_id',
                $item_table => 'id',
                ['AND' => [Infocom::getTableField('itemtype') => self::$itemtype]],
            ]
        ];
        // Change inner joins into left joins to identify missing data
        $request['LEFT JOIN'] = $request['INNER JOIN'] + $request['LEFT JOIN'];
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
        $status['is_deleted'] = ($data['is_deleted'] === 0);
        $status['is_template'] = ($data['is_template'] === 0);
        $status['has_location'] = ($data['location_id'] !== 0);
        $status['has_state_or_country'] = (strlen($data['state'] ?? '') > 0) || (strlen($data['country'] ?? '') > 0);
        $status['has_model'] = ($data['model_id'] !== 0);
        $status['has_model_power_consumption'] = (($data['model_power_consumption'] ?? 0) !== 0);
        $status['has_type'] = ($data['type_id'] !== 0);
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
