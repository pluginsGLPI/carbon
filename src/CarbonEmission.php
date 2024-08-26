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

use CommonDBChild;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use QueryExpression;
use QuerySubQuery;

class CarbonEmission extends CommonDBChild
{
    public static $itemtype = 'itemtype';
    public static $items_id = 'items_id';

    public static function getTypeName($nb = 0)
    {
        return _n("Carbon Emission", "Carbon Emissions", $nb, 'carbon emission');
    }

    public function prepareInputForAdd($input)
    {
        $input = parent::prepareInputForAdd($input);
        if ($input === false || count($input) === 0) {
            return false;
        }
        $date = new DateTime($input['date']);
        $date->setTime(0, 0, 0);
        $input['date'] = $date->format('Y-m-d');
        return $input;
    }

    /**
     * Gets date intervals where data are missing
     *
     * @param integer $id
     * @param DateTimeInterface $start
     * @param DateTimeInterface|null $stop
     * @return DBmysqlIterator
     */
    public function findGaps(string $itemtype, int $id, DateTimeInterface $start, ?DateTimeInterface $stop = null): array
    {
        global $DB;

        $table = CarbonEmission::getTable();

        // Build WHERE clause for boundaries
        $boundaries = [];
        if ($start !== null) {
            $boundaries[] = ['date' => ['>=', $start->format('Y-m-d 00:00:00')]];
        }
        if ($stop !== null) {
            $boundaries[] = ['date' => ['<=', $stop->format('Y-m-d 23:59:59')]];
        }

        $gaps = [];
        if ($start !== null) {
            $first = $DB->request([
                'SELECT' => [
                    'date',
                ],
                'FROM' => $table,
                'WHERE' => [
                    'itemtype' => $itemtype,
                    'items_id' => $id,
                ] + $boundaries,
                'ORDER' => ['date ASC'],
                'LIMIT' => 1,
            ])->current();
            if ($first === null) {
                return [
                    [
                        'start' => $start->format('Y-m-d H:i:s'),
                        'end'   => ($stop ?? new DateTime('now'))->format('Y-m-d H:i:s'),
                    ]
                ];
            }
            $first_date = new DateTime($first['date']);
            if ($first_date > $start) {
                // $first_date->modify('-1 second');
                $gaps[] = [
                    'start' => $start->format('Y-m-d H:i:s'),
                    'end'   => $first_date->format('Y-m-d H:i:s'),
                ];
            }
        }

        $request = [
            'SELECT' => [
                // 'date AS start', 'next_available_date AS end'
                new QueryExpression('`date` + INTERVAL 1 DAY AS `start`'),
                new QueryExpression('`next_available_date` - INTERVAL 1 DAY AS `end`'),
            ],
            'FROM'  => new QuerySubQuery([
                'SELECT' => [
                    '*',
                    new QueryExpression('IF(date + INTERVAL 1 DAY < next_available_date, 1, 0) as gap_tag')
                ],
                'FROM'   => new QuerySubQuery([
                    'SELECT' => [
                        'date',
                        new QueryExpression('LEAD(`date`, 1) OVER (ORDER BY `date`) AS `next_available_date`'),
                    ],
                    'FROM' => $table,
                    'WHERE' => [
                        'itemtype' => $itemtype,
                        'items_id' => $id,
                    ] + $boundaries
                ], 'rows')
            ], 'gaps'),
            'WHERE' => [
                'gap_tag' => 1
            ],
        ];

        $iterator = $DB->request($request);
        $gaps = array_merge($gaps, iterator_to_array($iterator));

        if ($stop !== null) {
            $last = $DB->request([
                'SELECT' => [
                    'date',
                ],
                'FROM' => $table,
                'WHERE' => [
                    'itemtype' => $itemtype,
                    'items_id' => $id,
                ] + $boundaries,
                'ORDER' => ['date DESC'],
                'LIMIT' => 1,
            ])->current();
            $last_date = new DateTime($last['date']);
            if ($last_date < $stop) {
                // $last_date->modify('+1 day');
                $gaps[] = [
                    'start' => $last_date->format('Y-m-d H:i:s'),
                    'end'   => $stop->format('Y-m-d H:i:s'),
                ];
            }
        }

        return $gaps;
    }
}
