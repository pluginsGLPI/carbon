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

use DateTime;
use DateTimeImmutable;

class Toolbox
{
    /**
     * Get the oldest asset date in the database
     *
     * @return DateTimeImmutable
     */
    public function getOldestAssetDate(): ?DateTimeImmutable
    {
        $itemtypes = Config::getSupportedAssets();
        $oldest_date = null;
        foreach ($itemtypes as $itemtype) {
            /** @var CommonDBTM $item */
            $item = new $itemtype();
            $result = $item->find([], ['date_creation DESC'], 1);
            if (count($result) === 1) {
                $row = array_pop($result);
                if ($oldest_date === null || $row['date_creation'] < $oldest_date) {
                    $oldest_date = $row['date_creation'];
                }
            }
        }

        if ($oldest_date === null) {
            return $this->getDefaultCarbonIntensityDownloadDate();
        }
        return DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $oldest_date);
    }

    /**
     * Get default date where environnemental imapct shouw be known
     * when no inventory data is available
     */
    public function getDefaultCarbonIntensityDownloadDate(): DateTimeImmutable
    {
        $start_date = new DateTime('1 year ago');
        $start_date->setDate($start_date->format('Y'), 1, 1);
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
        return sprintf(__('%1$s %2$s'), round($weight, 2), $human_readable_unit);
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
       //TRANS: list of unit (o for octet)
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
        return sprintf(__('%1$s %2$s'), round($p, 2), $human_readable_unit);
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
        // $average = array_sum($serie) / count($serie);

        $multiple = 1000;
        $power = 0;
        foreach ($units as $human_readable_unit) {
            if ($average < $multiple) {
                break;
            }
            $average = $average / $multiple;
            $power++;
        }

        foreach ($serie as &$number) {
            if (is_scalar($number)) {
                $number = number_format($number / ($multiple ** $power), PLUGIN_CARBON_DECIMALS);
            } else if (is_array($number)) {
                $number['y'] = number_format($number['y'] / ($multiple ** $power), PLUGIN_CARBON_DECIMALS);
            }
        }

        return ['serie' => $serie, 'unit' => $human_readable_unit];
    }

    public function getHistoryClasses(): array
    {
        $history_types = [];

        foreach (PLUGIN_CARBON_TYPES as $itemtype) {
            $history_type = [
                __NAMESPACE__,
                'History',
                $itemtype
            ];
            $history_types[] = implode('\\', $history_type);
        }

        return $history_types;
    }
}
