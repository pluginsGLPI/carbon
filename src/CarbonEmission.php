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
use Entity;
use Location;
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
        // $date = new DateTime($input['date']);
        // $date->setTime(0, 0, 0);
        // $input['date'] = $date->format('Y-m-d');
        return $input;
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

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
            'table'              => self::getTable(),
            'field'              => 'entities_id',
            'name'               => sprintf('%s-%s', Entity::getTypeName(1), __('ID'))
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => 'glpi_locations',
            'field'              => 'name',
            'linkfield'          => 'locations_id',
            'name'               => Location::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => SearchOptions::CARBON_EMISSION_DATE,
            'table'              => self::getTable(),
            'field'              => 'date',
            'name'               => sprintf('Date')
        ];

        $tab[] = [
            'id'                 => SearchOptions::CARBON_EMISSION_ENERGY_PER_DAY,
            'table'              => self::getTable(),
            'field'              => 'energy_per_day',
            'name'               => sprintf('Energy', 'carbon')
        ];

        $tab[] = [
            'id'                 => SearchOptions::CARBON_EMISSION_PER_DAY,
            'table'              => self::getTable(),
            'field'              => 'emission_per_day',
            'name'               => sprintf('Emission', 'carbon')
        ];

        $tab[] = [
            'id'                 => SearchOptions::CARBON_EMISSION_ENERGY_QUALITY,
            'table'              => self::getTable(),
            'field'              => 'energy_quality',
            'name'               => sprintf('Energy quality', 'carbon')
        ];

        $tab[] = [
            'id'                 => SearchOptions::CARBON_EMISSION_EMISSION_QUALITY,
            'table'              => self::getTable(),
            'field'              => 'emission_quality',
            'name'               => sprintf('Emission quality', 'carbon')
        ];

        return $tab;
    }

    /**
     * Gets date intervals where data are missing
     * Gaps are returned as an array of start and end
     * where start is the 1st msising date and end is the last missing date
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
            $unix_start = $start->format('U');
            $unix_start = $unix_start - ($unix_start % (3600 * 24));
            $boundaries[] = new QueryExpression('UNIX_TIMESTAMP(`date`) >= ' . $unix_start);
        }
        if ($stop !== null) {
            $unix_stop = $stop->format('U');
            $unix_stop = $unix_stop - ($unix_stop % (3600 * 24) );
            $boundaries[] = new QueryExpression('UNIX_TIMESTAMP(`date`) <= ' . $unix_stop);
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
                        'start' => $start->format('U'),
                        'end'   => ($stop ?? new DateTime('now'))->format('U'),
                    ]
                ];
            }
            $first_date = new DateTime($first['date']);
            $first_date->modify(('-1 day'));
            if ($first_date > $start) {
                $gaps[] = [
                    'start' => $start->format('U'),
                    'end'   => $first_date->format('U'),
                ];
            }
        }

        $date_1 = 'UNIX_TIMESTAMP(`date`)';
        $date_2 = 'LEAD(UNIX_TIMESTAMP(`date`), 1) OVER (ORDER BY UNIX_TIMESTAMP(`date`))';
        $request = [
            'SELECT' => [
                new QueryExpression('date + 86400 as start'), // 1st missing day
                new QueryExpression('next_available_date - 86400 as end'), // last missing day
            ],
            'FROM'  => new QuerySubQuery([
                'SELECT' => [
                    new QueryExpression("$date_1 as `date`"),
                    new QueryExpression("$date_2  AS `next_available_date`"),
                    new QueryExpression("$date_2 - $date_1 as `diff`"),
                ],
                'FROM' => $table,
                'WHERE' => [
                    'itemtype' => $itemtype,
                    'items_id' => $id,
                ] + $boundaries
            ], 'rows'),
            'WHERE' => ['diff' => ['>', 86400 + 3600]] // 1 day + 1 hour to ignore DST changes
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
            $last_date->modify('+1 day');
            if ($last_date < $stop) {
                $gaps[] = [
                    'start' => $last_date->format('U'),
                    'end'   => $stop->format('U'),
                ];
            }
        }

        return $gaps;
    }
}
