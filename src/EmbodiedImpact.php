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
use Toolbox as GlpiToolbox;

/**
 * Embodied impact of assets
 *
 * Embodied impact is the impact of the asset while it is built and destroyed or recycled
 */
class EmbodiedImpact extends AbstractImpact
{
    public static function getTypeName($nb = 0)
    {
        return _n("Embodied impact", "Embodied impacts", $nb, 'carbon');
    }

    public function canEdit($ID): bool
    {
        return false;
    }

    /**
     * Get iterator of items without known embodied impact for a specified itemtype
     *
     * @template T of CommonDBTM
     * @param class-string<T> $itemtype
     * @param array $crit Criteria array of WHERE, ORDER, GROUP BY, LEFT JOIN, INNER JOIN, RIGHT JOIN, HAVING, LIMIT
     * @return DBmysqlIterator
     */
    public static function getAssetsToCalculate(string $itemtype, array $crit = []): DBmysqlIterator
    {
        /** @var DBmysql $DB */
        global $DB;

        // Check $itemtype inherits from CommonDBTM
        if (!GlpiToolbox::isCommonDBTM($itemtype)) {
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

        $table = self::getTable();
        $itemtype_table = $itemtype::getTable();

        $iterator = $DB->request(array_merge_recursive([
            'SELECT' => [
                $itemtype::getTableField('id'),
            ],
            'FROM' => $itemtype_table,
            'LEFT JOIN' => [
                $table => [
                    'FKEY' => [
                        $table => 'items_id',
                        $itemtype_table => 'id',

                    ],
                    'AND' => [
                        'itemtype' => $itemtype,
                    ],
                ],
            ],
            'WHERE' => [
                self::getTableField('items_id') => null,
            ],
        ], $crit));

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
