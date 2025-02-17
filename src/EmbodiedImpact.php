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
 * @license   MIT https://opensource.org/licenses/mit-license.php
 * @link      https://github.com/pluginsGLPI/carbon
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Carbon;

use CommonDBTM;
use DBmysqlIterator;
use QuerySubQuery;

/**
 * Embodied impact of assets
 *
 * Embodied impact is the impact of the asset while it is built and destroyed or recycled
 */
class EmbodiedImpact extends CommonDBTM
{
    // Use core computer right
    public static $rightname = 'computer';

    public function canEdit($ID)
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
}
