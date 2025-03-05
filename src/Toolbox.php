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
use DateTime;
use DateTimeImmutable;
use DBmysql;
use Infocom;
use Location;

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
            $start_date->modify('-12 months + 1 day');

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
}
