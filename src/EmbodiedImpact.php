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

use CommonDBTM;
use DBmysql;
use DBmysqlIterator;
use Glpi\DBAL\QuerySubQuery;

/**
 * Embodied impact of assets
 *
 * Embodied impact is the impact of the asset while it is built and destroyed or recycled
 */
class EmbodiedImpact extends CommonDBTM
{
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
            'datatype'           => 'number'
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'items_id',
            'name'               => __('Associated item ID'),
            'massiveaction'      => false,
            'datatype'           => 'specific',
            'additionalfields'   => ['itemtype']
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
            'field'              => 'gwp',
            'name'               => __('Global Warming Potential', 'carbon'),
            'massiveaction'      => false,
            'datatype'           => 'number',
            'unit'               => 'gCO<sub>2</sub>eq',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'adp',
            'name'               => __('Abiotic Depletion Potential', 'carbon'),
            'massiveaction'      => false,
            'datatype'           => 'number',
            'unit'               => 'KgSbeq',
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => $this->getTable(),
            'field'              => 'pe',
            'name'               => __('Primary energy', 'carbon'),
            'massiveaction'      => false,
            'datatype'           => 'number',
            'unit'               => 'J',
        ];

        $tab[] = [
            'id'                 => SearchOptions::CARBON_EMISSION_CALC_DATE,
            'table'              => self::getTable(),
            'field'              => 'date_mod',
            'name'               => __('Date of evaluation', 'carbon')
        ];

        $tab[] = [
            'id'                 => SearchOptions::CARBON_EMISSION_ENGINE,
            'table'              => self::getTable(),
            'field'              => 'engine',
            'name'               => __('Engine', 'carbon')
        ];

        $tab[] = [
            'id'                 => SearchOptions::CARBON_EMISSION_ENGINE_VER,
            'table'              => self::getTable(),
            'field'              => 'engine_version',
            'name'               => __('Engine version', 'carbon')
        ];

        return $tab;
    }

    /**
     * Get iterator of items without known embodied impact for a specified itemtype
     *
     * @param string $itemtype
     * @param array $crit Criteria array of WHERE, ORDER, GROUP BY, LEFT JOIN, INNER JOIN, RIGHT JOIN, HAVING, LIMIT
     * @return DBmysqlIterator
     */
    public static function getAssetsToCalculate(string $itemtype, array $crit = []): DBmysqlIterator
    {
        /** @var DBmysql $DB */
        global $DB;

        // Check $itemtype inherits from CommonDBTM
        if (!is_subclass_of($itemtype, CommonDBTM::class)) {
            throw new \LogicException('itemtype is not a CommonDBTM object');
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

        // Add itemtype to criteria
        $crit['WHERE']['itemtype'] = $itemtype;

        $table = self::getTable();
        $itemtype_table = $itemtype::getTable();
        // Prepare sub query to filter out items already calculated
        $sub_query = [
            'SELECT' => [
                'items_id',
            ],
            'FROM' => $table,
        ] + $crit;
        $iterator = $DB->request([
            'SELECT' => [
                'id',
            ],
            'FROM' => $itemtype_table,
            'WHERE' => [
                ['NOT' => ['id' =>  new QuerySubQuery($sub_query)]],
            ]
        ]);

        return $iterator;
    }

    public function calculateImpact(string $lca_type, int $limit = 0): int
    {
        $crit = [];
        if ($limit > 0) {
            $crit['LIMIT'] = $limit;
        }
        $iterator = $this->getAssetsToCalculate($lca_type::getItemtype(), $crit);
        $count = 0;
        foreach ($iterator as $item) {
            $lca = new $lca_type($item['id']);
            $lca::calculate($item);
            $count++;
        }

        return $iterator->count();
    }

    /**
     * Get impact value in a human r eadable format, selecting the best unit
     */
    public function getHumanReadableImpact(string $field): string
    {
        switch ($field) {
            case 'gwp':
                return Toolbox::getWeight($this->fields[$field]) . 'CO2eq';
            case 'adp':
                return Toolbox::getWeight($this->fields[$field]) . 'Sbeq';
            case 'pe':
                return Toolbox::getEnergy($this->fields[$field] / 3600); // Convert J into Wh
        }

        return '';
    }
}
