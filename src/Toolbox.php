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

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DBmysql;
use Glpi\Dashboard\Dashboard as GlpiDashboard;
use Infocom;
use Location;
use QueryExpression;
use QuerySubQuery;
use Mexitek\PHPColors\Color;

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
        $multiple = 990;
        foreach ($units as $human_readable_unit) {
            if ($weight < $multiple) {
                break;
            }
            $weight /= 1000;
        }

        $weight = self::dynamicRound($weight);

       //TRANS: %1$s is a number maybe float or string and %2$s the unit
        return sprintf(__('%1$s&nbsp;%2$s'), $weight, $human_readable_unit);
    }

    public static function dynamicRound(float $number): float
    {
        if ($number < 10) {
            $number = round($number, 2);
        } else if ($number < 100) {
            $number = round($number, 1);
        } else {
            $number = round($number, 0);
        }

        return $number;
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
        $multiple = 990;
        foreach ($units as $human_readable_unit) {
            if ($p < $multiple) {
                break;
            }
            $p /= 1000;
        }

        $p = self::dynamicRound($p);

       //TRANS: %1$s is a number maybe float or string and %2$s the unit
        return sprintf(__('%1$s&nbsp;%2$s'), $p, $human_readable_unit);
    }

    /**
     * Format a power passing a power in grams
     *
     * @param float $p  Power in Watt
     *
     * @return string  formatted power
     **/
    public static function getEnergy(float $p): string
    {
       //TRANS: list of unit (W for watt)
        $units = [
            __('Wh', 'carbon'),
            __('KWh', 'carbon'),
            __('MWh', 'carbon'),
            __('GWh', 'carbon'),
            __('TWh', 'carbon'),
            __('PWh', 'carbon'),
            __('EWh', 'carbon'),
            __('ZWh', 'carbon'),
            __('YWh', 'carbon'),
        ];
        $multiple = 990;
        foreach ($units as $human_readable_unit) {
            if ($p < $multiple) {
                break;
            }
            $p /= 1000;
        }

        $p = self::dynamicRound($p);

       //TRANS: %1$s is a number maybe float or string and %2$s the unit
        return sprintf(__('%1$s&nbsp;%2$s'), $p, $human_readable_unit);
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
            $average /= $multiple;
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
     * Get asset class names for energy and GWP consumption impact
     *
     * @return array
     */
    public static function getGwpUsageImpactClasses(): array
    {
        $base_namespace = __NAMESPACE__ . '\\Impact\\History';
        $types = [];
        foreach (PLUGIN_CARBON_TYPES as $itemtype) {
            $type = implode('\\', [
                $base_namespace,
                $itemtype
            ]);
            if (!class_exists($type)) {
                continue;
            }
            $types[] = $type;
        }

        return $types;
    }

    /**
     * Get asset class names for non-GWP impacts
     *
     * @return array
     */
    public static function getUsageImpactClasses(): array
    {
        $base_namespace = __NAMESPACE__ . '\\Impact\\Usage';
        $base_namespace = Config::getUsageImpactEngine();
        $types = [];
        foreach (PLUGIN_CARBON_TYPES as $itemtype) {
            $type = implode('\\', [
                $base_namespace,
                $itemtype
            ]);
            if (!class_exists($type)) {
                continue;
            }
            $types[] = $type;
        }

        return $types;
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
            $history_type = implode('\\', [
                $base_namespace,
                $itemtype
            ]);
            if (!class_exists($history_type)) {
                continue;
            }
            $embodied_impact_types[] = $history_type;
        }

        return $embodied_impact_types;
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
            $end_date->setDate((int) $end_date->format('Y'), (int) $end_date->format('m'), 1); // First day of current month (excluded)
            $start_date = clone $end_date;
            $start_date->setDate((int) $end_date->format('Y') - 1, (int) $end_date->format('m'), 1);

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
     * To use with Mysql 8.0+ or MariaDB 10.2+
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
        // $interval_in_seconds = $interval->s + $interval->i * 60 + $interval->h * 3600 + $interval->d * 86400;
        $sql_interval = self::dateIntervalToMySQLInterval($interval);

        // Get start date as unix timestamp
        $boundaries[] = new QueryExpression('`date` >= "' . $start->format('Y-m-d H:i:s') . '"');

        // get stop date as unix timestamp
        if ($stop === null) {
            // Assume stop date is yesterday at midnight
            $stop = new DateTime();
            $stop->setTime(0, 0, 0);
            $stop->sub(new DateInterval('P1D'));
        }
        $boundaries[] = new QueryExpression('`date` <= "' . $stop->format('Y-m-d H:i:s') . '"');

        // prepare sub query to get start and end date of an atomic date range
        // An atomic date range is set to 1 hour
        // To reduce problems with DST, we use the unix timestamp of the date
        $atomic_ranges_subquery = new QuerySubQuery([
            'SELECT' => [
                new QueryExpression('`date` as `start_date`'),
                new QueryExpression("DATE_ADD(`date`, $sql_interval) as `end_date`"),
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
                    'start' => $start->format('Y-m-d H:i:s'),
                    'end'   => $stop->format('Y-m-d H:i:s'),
                ]
            ];
        }

        // Find gaps from islands
        $expected_start_date = $start;
        $gaps = [];
        foreach ($result as $row) {
            if ($expected_start_date < new DateTimeImmutable($row['island_start_date'])) {
                // The current island starts after the expected start date
                // Then there is a gap
                $gaps[] = [
                    'start' => $expected_start_date->format('Y-m-d H:i:s'),
                    'end'   => $row['island_start_date'],
                ];
            }
            $expected_start_date = new DateTimeImmutable($row['island_end_date']);
        }
        if ($expected_start_date < $stop) {
            // The last island ends before the stop date
            // Then there is a gap
            $gaps[] = [
                'start' => $expected_start_date->format('Y-m-d H:i:s'),
                'end'   => $stop->format('Y-m-d H:i:s'),
            ];
        }

        return $gaps;
    }

    /**
     * Convert a DateInterval to a MySQL INTERVAL string
     *
     * @param DateInterval $interval
     * @return string
     */
    public static function dateIntervalToMySQLInterval(DateInterval $interval): string
    {
        $parts = [];

        if ($interval->y > 0) {
            $parts[] = "INTERVAL {$interval->y} YEAR";
        }
        if ($interval->m > 0) {
            $parts[] = "INTERVAL {$interval->m} MONTH";
        }
        if ($interval->d > 0) {
            $parts[] = "INTERVAL {$interval->d} DAY";
        }
        if ($interval->h > 0) {
            $parts[] = "INTERVAL {$interval->h} HOUR";
        }
        if ($interval->i > 0) {
            $parts[] = "INTERVAL {$interval->i} MINUTE";
        }
        if ($interval->s > 0) {
            $parts[] = "INTERVAL {$interval->s} SECOND";
        }

        return implode(' + ', $parts);
    }

    public static function getDashboardId(): ?int
    {
        $dashboard = new GlpiDashboard();
        $dashboard_key = 'plugin_carbon_board';
        /** @phpstan-ignore argument.type */
        if ($dashboard->getFromDB($dashboard_key) === false) {
            return null;
        }

        return $dashboard->fields['id']; // do not use getID()
    }

    /**
     * tune a foreground color luminosity depending on a background luminosity
     *
     * @param string $bg_color
     * @param string $fg_color
     * @param float $target_ratio
     * @param integer $max_steps
     * @return string
     */
    public static function getAdaptedFgColor(string $bg_color, string $fg_color, $target_ratio = 4.5, $max_steps = 100): string
    {
        $hsl = Color::hexToHsl($fg_color);
        $bg_color = new Color($bg_color);
        $fg_luminance = self::relative_luminance(new Color($fg_color));
        $bg_luminance = self::relative_luminance($bg_color);
        $direction = ($fg_luminance < $bg_luminance) ? -0.01 : 0.01;

        for ($i = 0; $i < $max_steps; $i++) {
            $rgb_test = new Color(Color::hslToHex($hsl));
            if (self::contrastRatio($rgb_test, $bg_color) >= $target_ratio) {
                return '#' . $rgb_test->getHex();
            }
            $hsl['L'] += $direction;
            $hsl['L'] = max(0.0, min(1.0, $hsl['L']));
        }

        // Return the last tested value id contrast is still not satisfying
        return '#' . Color::hslToHex($hsl);
    }

    protected static function relative_luminance(Color $color): float
    {
        $rgb = array_map(function ($rgb_component) {
            $rgb_component /= 255.0;
            return ($rgb_component <= 0.03928) ? ($rgb_component / 12.92) : pow(($rgb_component + 0.055) / 1.055, 2.4);
        }, $color->getRGB());
        return 0.2126 * $rgb['R'] + 0.7152 * $rgb['G'] + 0.0722 * $rgb['B'];
    }

    protected static function contrastRatio(Color $color_1, Color $color_2)
    {
        $l1 = self::relative_luminance($color_1);
        $l2 = self::relative_luminance($color_2);
        return ($l1 > $l2) ? ($l1 + 0.05) / ($l2 + 0.05) : ($l2 + 0.05) / ($l1 + 0.05);
    }
}
