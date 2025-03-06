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

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DBmysql;
use Infocom;
use Location;
use QueryExpression;
use QuerySubQuery;

class Toolbox
{
    /**
     * Get the oldest asset date in the database
     * @param array $crit
     *
     * @return DateTimeImmutable
     */
    public function getOldestAssetDate(array $crit = []): ?DateTimeImmutable
    {
        /** @var DBmysql $DB */
        global $DB;

        $itemtypes = Config::getSupportedAssets();
        if (isset($crit['itemtype'])) {
            $itemtypes = [$crit['itemtype']];
            unset($crit['itemtype']);
        }
        $oldest_date = null;
        $infocom_table = Infocom::getTable();
        foreach ($itemtypes as $itemtype) {
            if (Infocom::canApplyOn($itemtype)) {
                $item_table = getTableForItemType($itemtype);
                $dates = $DB->request([
                    'SELECT' => [
                        'MIN' => [
                            "$item_table.date_creation as date_creation",
                            "$item_table.date_mod as date_mod",
                            "$infocom_table.use_date as use_date",
                            "$infocom_table.delivery_date as delivery_date",
                            "$infocom_table.buy_date as buy_date",
                        ],
                    ],
                    'FROM' => $item_table,
                    'LEFT JOIN' => [
                        $infocom_table => [
                            'FKEY' => [
                                $infocom_table => 'items_id',
                                $item_table    => 'id',
                                ['AND' => ['itemtype' => $itemtype]],
                            ]
                        ]
                    ],
                    'WHERE' => $crit,
                ])->current();
                $itemtype_oldest_date = $dates['use_date']
                ?? $dates['delivery_date']
                ?? $dates['buy_date']
                ?? $dates['date_creation']
                ?? $dates['date_mod']
                ?? null;
                if ($oldest_date === null) {
                    $oldest_date = $itemtype_oldest_date;
                } else if ($itemtype_oldest_date !== null) {
                    $oldest_date = min($oldest_date, $itemtype_oldest_date);
                }
            }
        }
        if ($oldest_date === null) {
            return null;
        }
        if (($output = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $oldest_date)) === false) {
            // Infocom dates are date (without time)
            $output = DateTimeImmutable::createFromFormat('Y-m-d', $oldest_date);
            $output = $output->setTime(0, 0, 0, 0);
        }

        return $output;
    }

    /**
     * Find the date where an asset leaves the inventory
     *
     * @param array $crit
     * @return DateTimeImmutable|null
     */
    public function getLatestAssetDate(array $crit = []): ?DateTimeImmutable
    {
        /** @var DBmysql $DB */
        global $DB;

        $itemtypes = Config::getSupportedAssets();
        if (isset($crit['itemtype'])) {
            $itemtypes = [$crit['itemtype']];
            unset($crit['itemtype']);
        }
        $latest_date = null;
        $infocom_table = Infocom::getTable();
        foreach ($itemtypes as $itemtype) {
            if (Infocom::canApplyOn($itemtype)) {
                $item_table = getTableForItemType($itemtype);
                $dates = $DB->request([
                    'SELECT' => [
                        'MIN' => [
                            "$infocom_table.decommission_date as decommission_date",
                        ],
                    ],
                    'FROM' => $item_table,
                    'LEFT JOIN' => [
                        $infocom_table => [
                            'FKEY' => [
                                $infocom_table => 'items_id',
                                $item_table    => 'id',
                                ['AND' => ['itemtype' => $itemtype]],
                            ]
                        ]
                    ],
                    'WHERE' => $crit,
                ])->current();
                $itemtype_latest_date = $dates['decommission_date']
                ?? null;
                if ($latest_date === null) {
                    $latest_date = $itemtype_latest_date;
                } else if ($itemtype_latest_date !== null) {
                    $latest_date = max($latest_date, $itemtype_latest_date);
                }
            }
        }
        if ($latest_date === null) {
            return null;
        }
        if (($output = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $latest_date)) === false) {
            // Infocom dates are date (without time)
            $output = DateTimeImmutable::createFromFormat('Y-m-d', $latest_date);
            $output = $output->setTime(23, 59, 59, 0);
        }

        return $output;
    }

    /**
     * Get default date where environnemental imapct should be known
     * when no inventory data is available
     */
    public function getDefaultCarbonIntensityDownloadDate(): DateTimeImmutable
    {
        $start_date = new DateTime('1 year ago');
        $start_date->setDate((int) $start_date->format('Y'), 1, 1);
        $start_date->setTime(0, 0, 0);
        $start_date->modify('-1 month');
        return DateTimeImmutable::createFromMutable($start_date);
    }

    /**
     * Format a weight passing a weight in grams
     *
     * @param float $weight  Weight in grams
     *
     * @return string  formatted weight
     **/
    public static function getWeight(float $weight): string
    {
       //TRANS: list of unit (o for octet)
        $units = [
            __('g', 'carbon'),
            __('Kg', 'carbon'),
            __('t', 'carbon'),
            __('Kt', 'carbon'),
            __('Mt', 'carbon'),
            __('Gt', 'carbon'),
            __('Tt', 'carbon'),
            __('Pt', 'carbon'),
            __('Et', 'carbon'),
            __('Zt', 'carbon'),
            __('Yt', 'carbon'),
        ];
        $multiple = 1000;
        foreach ($units as $human_readable_unit) {
            if ($weight < $multiple) {
                break;
            }
            $weight = $weight / $multiple;
        }

       //TRANS: %1$s is a number maybe float or string and %2$s the unit
        return sprintf(__('%1$s&nbsp;%2$s'), round($weight, 2), $human_readable_unit);
    }

        /**
     * Format a power passing a power in grams
     *
     * @param float $p  Power in Watt
     *
     * @return string  formatted power
     **/
    public static function getPower(float $p): string
    {
       //TRANS: list of unit (W for watt)
        $units = [
            __('W', 'carbon'),
            __('KW', 'carbon'),
            __('MW', 'carbon'),
            __('GW', 'carbon'),
            __('TW', 'carbon'),
            __('PW', 'carbon'),
            __('EW', 'carbon'),
            __('ZW', 'carbon'),
            __('YW', 'carbon'),
        ];
        $multiple = 1000;
        foreach ($units as $human_readable_unit) {
            if ($p < $multiple) {
                break;
            }
            $p = $p / $multiple;
        }

       //TRANS: %1$s is a number maybe float or string and %2$s the unit
        return sprintf(__('%1$s&nbsp;%2$s'), round($p, 2), $human_readable_unit);
    }

    /**
     * Find the best multiplier to normalize a values of a serie
     *
     * @param array $serie serie of numbers (each item may be an array witk keys x and y)
     * @param array $units unis ordered by power
     * @return array modified serie and selected unit
     */
    public static function scaleSerie(array $serie, array $units): array
    {
        if (count($serie) === 0) {
            return ['serie' => $serie, 'unit' => array_shift($units)];
        };

        $average = 0;
        foreach ($serie as $value) {
            if (is_scalar($value)) {
                $average += $value;
            } else if (is_array($value)) {
                $average += $value['y'];
            } else {
                continue;
            }
        }
        $average /= count($serie);

        $multiple = 1000;
        $power = 0;
        $first_multiple = reset($units);
        $human_readable_unit = $first_multiple;
        foreach ($units as $human_readable_unit) {
            if ($average < $multiple) {
                break;
            }
            $average = $average / $multiple;
            $power++;
        }

        foreach ($serie as &$number) {
            if (is_scalar($number)) {
                $number = number_format($number / ($multiple ** $power), PLUGIN_CARBON_DECIMALS, '.', '');
            } else if (is_array($number)) {
                $number['y'] = number_format($number['y'] / ($multiple ** $power), PLUGIN_CARBON_DECIMALS, '.', '');
            }
        }

        return ['serie' => $serie, 'unit' => $human_readable_unit];
    }

    /**
     * Get asset class names for energy consumption impact
     *
     * @return array
     */
    public static function getUsageImpactClasses(): array
    {
        $base_namespace = __NAMESPACE__ . '\\Impact\\History';
        $history_types = [];
        foreach (PLUGIN_CARBON_TYPES as $itemtype) {
            $history_type = [
                $base_namespace,
                $itemtype
            ];
            $history_types[] = implode('\\', $history_type);
        }

        return $history_types;
    }

    /**
     * Get asset class names for embodied impact
     *
     * @return array
     */
    public static function getEmbodiedImpactClasses(): array
    {
        $base_namespace = Config::getEmbodiedImpactEngine();
        $embodied_impact_types = [];
        foreach (PLUGIN_CARBON_TYPES as $itemtype) {
            $embodied_impact_types[] = implode('\\', [
                $base_namespace,
                $itemtype
            ]);
        }

        return [$base_namespace . '\\Computer'];
        // return $embodied_impact_types;
    }

    /**
     * Get an array of 2 dates from the beginning of the current year to yesterday
     *
     * @param DateTimeImmutable $date
     * @return array
     */
    public function yearToLastMonth(DateTimeImmutable $date): array
    {
            $end_date = DateTime::createFromImmutable($date);
            $end_date->setTime(0, 0, 0, 0);
            $end_date->setDate((int) $end_date->format('Y'), (int) $end_date->format('m'), 0); // Last day of previous month
            $start_date = clone $end_date;
            $start_date->setDate((int) $end_date->format('Y') - 1, (int) $end_date->format('m') + 1, 1);

        return [$start_date, $end_date];
    }

    public static function isLocationExistForZone(string $name): bool
    {
        /** @var DBmysql $DB */
        global $DB;

        $result = $DB->request([
            'COUNT' => 'c',
            'FROM'   => Location::getTable(),
            'WHERE'  => [
                'country' => $name,
            ],
        ]);
        return $result->current()['c'] > 0;
    }

    /**
     * Gets date intervals where data are missing in a table
     *
     * @see https://bertwagner.com/posts/gaps-and-islands/
     *
     * @param string $table                table to search for gaps
     * @param DateTimeInterface $start     start date to search
     * @param DateInterval $interval       Interval between each data sample (do not use intervals in months or years)
     * @param DateTimeInterface|null $stop stop date to search
     * @return array                       list of gaps
     */
    public static function findTemporalGapsInTable(string $table, DateTimeInterface $start, DateInterval $interval, ?DateTimeInterface $stop = null, array $criteria = [])
    {
        /** @var DBmysql $DB */
        global $DB;

        if ($interval->m !== 0 || $interval->y !== 0) {
            throw new \InvalidArgumentException('Interval must be in days, hours, minutes or seconds');
        }
        $interval_in_seconds = $interval->s + $interval->i * 60 + $interval->h * 3600 + $interval->d * 86400;

        // Get start date as unix timestamp
        $start_timestamp = $start->format('U');
        $start_timestamp = $start_timestamp - ($start_timestamp % $interval_in_seconds);
        $boundaries[] = new QueryExpression('UNIX_TIMESTAMP(`date`) >= ' . $start_timestamp);

        // get stop date as unix timestamp
        if ($stop === null) {
            // Assume stop date is yesterday at midnight
            $stop = new DateTime();
            $stop->setTime(0, 0, 0);
            $stop->sub(new DateInterval('P1D'));
        }
        $stop_timestamp = $stop->format('U');
        $stop_timestamp = $stop_timestamp - ($stop_timestamp % $interval_in_seconds);
        $boundaries[] = new QueryExpression('UNIX_TIMESTAMP(`date`) <= ' . $stop_timestamp);

        // prepare sub query to get start and end date of an atomic date range
        // An atomic date range is set to 1 hour
        // To reduce problems with DST, we use the unix timestamp of the date
        $atomic_ranges_subquery = new QuerySubQuery([
            'SELECT' => [
                new QueryExpression('UNIX_TIMESTAMP(`date`) as `start_date`'),
                new QueryExpression("UNIX_TIMESTAMP(`date`) + $interval_in_seconds as `end_date`"),
            ],
            'FROM'   => $table,
            'WHERE'  => $criteria + $boundaries,
        ], 'atomic_ranges');

        // For each atomic date range, find the end date of previous atomic date range
        $groups_subquery = new QuerySubQuery([
            'SELECT' => [
                new QueryExpression('ROW_NUMBER() OVER (ORDER BY `start_date`, `end_date`) AS `row_number`'),
                'start_date',
                'end_date',
                new QueryExpression('LAG(`end_date`, 1) OVER (ORDER BY `start_date`, `end_date`) AS `previous_end_date`')
            ],
            'FROM' => $atomic_ranges_subquery
        ], 'groups');

        // For each atomic date range, find if it is the start of an island
        $islands_subquery = new QuerySubQuery([
            'SELECT' => [
                '*',
                // new QueryExpression('CASE WHEN `groups`.`previous_end_date` >= `start_date` THEN 0 ELSE 1 END AS `is_island_start`'), // For debugging purpose
                new QueryExpression('SUM(CASE WHEN `groups`.`previous_end_date` >= `start_date` THEN 0 ELSE 1 END) OVER (ORDER BY `groups`.`row_number`) AS `ìsland_id`')
            ],
            'FROM' => $groups_subquery
        ], 'islands');

        $request = [
            'SELECT' => [
                'MIN' => 'start_date as island_start_date',
                'MAX' => 'end_date as island_end_date',
            ],
            'FROM' => $islands_subquery,
            'GROUPBY' => ['ìsland_id'],
            'ORDER' => ['island_start_date']
        ];

        $result = $DB->request($request);
        if ($result->count() === 0) {
            // No island at all, the whole range is a gap
            return [
                [
                    'start' => date('Y-m-d H:i:s', $start_timestamp),
                    'end'   => date('Y-m-d H:i:s', $stop_timestamp),
                ]
            ];
        }

        // Find gaps from islands
        $expected_start_date = $start_timestamp;
        $gaps = [];
        foreach ($result as $row) {
            if ($expected_start_date < $row['island_start_date']) {
                // The current island starts after the expected start date
                // Then there is a gap
                $gaps[] = [
                    'start' => date('Y-m-d H:i:s', $expected_start_date),
                    'end'   => date('Y-m-d H:i:s', $row['island_start_date']),
                ];
            }
            $expected_start_date = $row['island_end_date'];
        }
        if ($expected_start_date < $stop_timestamp) {
            // The last island ends before the stop date
            // Then there is a gap
            $gaps[] = [
                'start' => date('Y-m-d H:i:s', $expected_start_date),
                'end'   => date('Y-m-d H:i:s', $stop_timestamp),
            ];
        }

        return $gaps;
    }
}
