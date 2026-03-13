<?php

/**
 * -------------------------------------------------------------------------
 * Carbon plugin for GLPI
 *
 * @copyright Copyright (C) 2024-2025 Teclib' and contributors.
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

namespace GlpiPlugin\Carbon;

use LogicException;
use CommonDBChild;
use CommonDBTM;
use DBmysql;
use DBmysqlIterator;
use GlpiPlugin\Carbon\Impact\Type;
use Toolbox as GlpiToolbox;

abstract class AbstractImpact extends CommonDBChild
{
    public static $itemtype = 'itemtype';
    public static $items_id = 'items_id';

    public static $rightname = 'carbon:report';

    public function canEdit($ID): bool
    {
        return false;
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false, // implicit field is id
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'items_id',
            'name'               => __('Associated item ID'),
            'massiveaction'      => false,
            'datatype'           => 'specific',
            'additionalfields'   => ['itemtype'],
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => $this->getTable(),
            'field'              => 'itemtype',
            'name'               => _n('Type', 'Types', 1),
            'massiveaction'      => false,
            'datatype'           => 'itemtypename',
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'recalculate',
            'name'               => __('', 'carbon'),
            'massiveaction'      => false,
            'datatype'           => 'bool',
        ];

        $id = SearchOptions::IMPACT_BASE;
        foreach (Type::getImpactTypes() as $type_id => $type) {
            $id = SearchOptions::IMPACT_BASE + $type_id * 2;
            $tab[] = [
                'id'                 => $id,
                'table'              => $this->getTable(),
                'field'              => $type,
                'name'               => Type::getEmbodiedImpactLabel($type),
                'massiveaction'      => false,
                'datatype'           => 'number',
                'unit'               => implode(' ', Type::getImpactUnit($type)),
            ];
            $id++;

            $tab[] = [
                'id'                 => $id,
                'table'              => $this->getTable(),
                'field'              => "{$type}_quality",
                'name'               => Type::getEmbodiedImpactLabel($type),
                'massiveaction'      => false,
                'datatype'           => 'number',
                'unit'               => implode(' ', Type::getImpactUnit($type)),
            ];
        }

        $tab[] = [
            'id'                 => SearchOptions::CALCULATION_DATE,
            'table'              => self::getTable(),
            'field'              => 'date_mod',
            'name'               => __('Date of evaluation', 'carbon'),
        ];

        $tab[] = [
            'id'                 => SearchOptions::CALCULATION_ENGINE,
            'table'              => self::getTable(),
            'field'              => 'engine',
            'name'               => __('Engine', 'carbon'),
        ];

        $tab[] = [
            'id'                 => SearchOptions::CALCULATION_ENGINE_VERSION,
            'table'              => self::getTable(),
            'field'              => 'engine_version',
            'name'               => __('Engine version', 'carbon'),
        ];

        return $tab;
    }

    /**
     * Get iterator of items without known embodied impact for a specified itemtype
     *
     * @template T of CommonDBTM
     * @param class-string<T> $itemtype
     * @param array $crit Criteria array of WHERE, ORDER, GROUP BY, LEFT JOIN, INNER JOIN, RIGHT JOIN, HAVING, LIMIT
     * @return DBmysqlIterator
     */
    public static function getItemsToEvaluate(string $itemtype, array $crit = []): DBmysqlIterator
    {
        /** @var DBmysql $DB */
        global $DB;

        // Check $itemtype inherits from CommonDBTM
        if (!GlpiToolbox::isCommonDBTM($itemtype)) {
            throw new LogicException('itemtype is not a CommonDBTM object');
        }

        // clean $crit array: remove mostly SELECT, FROM
        $crit = array_intersect_key($crit, array_flip([
            'WHERE',
            'ORDER',
            'GROUP BY',
            'LEFT JOIN',
            'INNER JOIN',
            'RIGHT JOIN',
            'HAVING',
            'LIMIT',
        ]));

        $table = self::getTable();
        $glpi_item_type_table = getTableForItemType($itemtype . 'Type');
        $glpi_item_type_fk = getForeignKeyFieldForTable($glpi_item_type_table);
        $item_type_table = getTableForItemType('GlpiPlugin\\Carbon\\' . $itemtype . 'Type');
        $item_table = $itemtype::getTable();

        $iterator = $DB->request(array_merge_recursive([
            'SELECT' => [
                $itemtype::getTableField('id'),
            ],
            'FROM' => $item_table,
            'LEFT JOIN' => [
                $table => [
                    'FKEY' => [
                        $table => 'items_id',
                        $item_table => 'id',

                    ],
                    'AND' => [
                        'itemtype' => $itemtype,
                    ],
                ],
                $item_type_table => [
                    [
                        'FKEY' => [
                            $item_type_table => $glpi_item_type_fk,
                            $item_table => $glpi_item_type_fk,
                        ],
                    ],
                ],
            ],
            'WHERE' => [
                [
                    // No calculated data or data to recalculate
                    'OR' => [
                        self::getTableField('items_id') => null,
                        self::getTableField('recalculate') => 1,
                    ],
                ], [
                    // Item not marked to exclude from calculation
                    'OR' => [
                        $item_type_table . '.is_ignore' => 0,
                        $item_type_table . '.id' => null,
                    ],
                ],
            ],
        ], $crit));

        return $iterator;
    }

    /**
     * Get impact value in a human r eadable format, selecting the best unit
     *
     * @param string $field the field of the impact
     */
    public function getHumanReadableImpact(string $field): string
    {
        return Toolbox::getHumanReadableValue(
            $this->fields[$field],
            Type::getImpactUnit($field)
        );
    }
}
