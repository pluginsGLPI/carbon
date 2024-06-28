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
use Computer;
use ComputerModel;
use Location;
use DateTime;
use CronTask;
use GlpiPlugin\Carbon\History\Computer as ComputerHistory;
use GlpiPlugin\Carbon\History\Monitor as MonitorHistory;

class CarbonEmission extends CommonDBChild
{
    public static $itemtype = 'itemtype';
    public static $items_id = 'items_id';

    public static function getTypeName($nb = 0)
    {
        return _n("Carbon Emission", "Carbon Emissions", $nb, 'carbon emission');
    }

    public static function cronHistorize(CronTask $task): int
    {
        $histories = [
            ComputerHistory::class,
            MonitorHistory::class,
        ];
        $task->setVolume(0); // start with zero
        foreach ($histories as $history_type) {
            /** @var AbstractAsset $history */
            $history = new $history_type();
            $history->setLimit(0);
            $count = $history->historizeItems();
            $task->addVolume($count);
        }

        return ($count > 0 ? 1 : 0);
    }

    /**
     * @deprecated uses deprecated methods
     */
    public static function cronInfo($name)
    {
        switch ($name) {
            case 'ComputeCarbonEmissionsTask':
                return [
                    'description' => __('Compute carbon emissions for all computers', 'carbon')
                ];

            case 'Historize':
                return ['description' => __('Compute daily environnemental impact for all assets', 'carbon'),
                    'parameter' => __('Maximum number of entries to calculate', 'carbon'),
                ];
        }
        return [];
    }

    public function prepareInpurForAdd($input)
    {
        $date = new DateTime($input['date']);
        $date->setTime(0, 0, 0);
        $input['date'] = $date->format('Y-m-d');
        return $input;
    }

    /** Format a weight passing a weight in grams
     *
     * @param integer $weight  Weight in grams
     *
     * @return string  formatted weight
     **/
    public static function getWeight(int $weight): string
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
        foreach ($units as $val) {
            if ($weight < $multiple) {
                break;
            }
            $weight = $weight / $multiple;
        }

        $val .= "CO2eq";
       //TRANS: %1$s is a number maybe float or string and %2$s the unit
        return sprintf(__('%1$s %2$s'), round($weight, 2), $val);
    }
}
