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
 * Embedded impact of assets
 *
 * Embedded impact is the impact of the asset while it is built and destroyed or recycled
 */
class EmbeddedImpact extends CommonDBTM
{
    // Use core computer right
    public static $rightname = 'computer';

    public function canEdit($ID)
    {
        return false;
    }

    /**
     * Get iterator of items without known embedded impact for a specified itemtype
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
