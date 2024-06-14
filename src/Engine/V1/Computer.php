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

namespace GlpiPlugin\Carbon\Engine\V1;

use Computer as GlpiComputer;
use DBmysqlIterator;
use Location;
use GlpiPlugin\Carbon\CarbonIntensityZone;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\ComputerPower;
use GlpiPlugin\Carbon\EnvironnementalImpact;
use GlpiPlugin\Carbon\CarbonIntensity;
use DateTime;

/**
 * Compute CO2 emission of a computer
 */
class Computer implements CommonInterface
{

    private string $itemtype = '\Computer';
    private int    $items_id;

    public function __construct(int $items_id) {
        $this->items_id = $items_id;
    }

    public function getCarbonEmission(DateTime $begin_date, DateTime $end_date) : int
    {
        $usage_profile = $this->getUsageProfile();

        if ($usage_profile === null || !self::isUsageDay($usage_profile, $begin_date))
            return 0;

        return 0;
    }

    /**
     * Returns the carbon emission for the specified day.
     *
     * {@inheritDoc}
     */
    public function getCarbonEmissionPerDay(DateTime $day) : ?float
    {
        $usage_profile = $this->getUsageProfile();

        if ($usage_profile === null || !self::isUsageDay($usage_profile, $day)) {
            return 0.0;
        }

        $power = ComputerPower::getPower($this->items_id);

        return $this->computeEmissionPerDay($day, $power, $usage_profile['time_start'], $usage_profile['time_stop']);
    }

    /**
     * Returns the consumed energy for the specified day.
     *
     * {@inheritDoc}
     */
    public function getEnergyPerDay(DateTime $day) : float
    {
        $usage_profile = $this->getUsageProfile();

        if ($usage_profile === null || !self::isUsageDay($usage_profile, $day)) {
            return 0;
        }

        $power = ComputerPower::getPower($this->items_id);

        $day_s = $day->format('Y-m-d');
        $start_date = DateTime::createFromFormat('Y-m-d H:i:s', $day_s . $usage_profile['time_start']);
        $stop_date = DateTime::createFromFormat('Y-m-d H:i:s', $day_s . $usage_profile['time_stop']);
        $delta_time = $stop_date->getTimestamp() - $start_date->getTimestamp();

        // units:
        // power is in Watt
        // delta_time is in seconds
        $energy_in_kwh = ($power * $delta_time) / (1000.0 * 60 * 60);

        return $energy_in_kwh;
    }

    public function getUsageProfile() : ?array
    {
        global $DB;

        $computers_table = GlpiComputer::getTable();
        $environnementalimpact_table = EnvironnementalImpact::getTable();
        $computerUsageProfile_table = ComputerUsageProfile::getTable();

        $request = [
            'SELECT' => [
                $computerUsageProfile_table . '.*',
            ],
            'FROM' => $computers_table,
            'INNER JOIN' => [
                $environnementalimpact_table => [
                    'FKEY'   => [
                        $computers_table  => 'id',
                        $environnementalimpact_table => 'computers_id',
                    ]
                ],
                $computerUsageProfile_table => [
                    'FKEY'   => [
                        $environnementalimpact_table  => 'plugin_carbon_computerusageprofiles_id',
                        $computerUsageProfile_table => 'id',
                    ]
                ]
            ],
            'WHERE' => [
                GlpiComputer::getTableField('id') => $this->items_id,
            ],
        ];

        $result = $DB->request($request);

        if ($result->numrows() == 1) {
            return $result->current();
        }

        return null;
    }

    private static function isUsageDay(array $usage_profile, DateTime $dateTime) : bool
    {
        $day_of_week = $dateTime->format('N');
        $key = 'day_' . strval($day_of_week);

        return $usage_profile[$key] != 0;
    }

    private function requestCarbonIntensitiesPerDay(DateTime $day, string $start_time, string $stop_time) : DBmysqlIterator
    {
        global $DB;

        $day_s = $day->format('Y-m-d');
        $start_date = DateTime::createFromFormat('Y-m-d H:i:s', $day_s . $start_time);
        $start_date_s = $start_date->format('Y-m-d H:i:s'); // may be can use directly concatenation
        $stop_date = DateTime::createFromFormat('Y-m-d H:i:s', $day_s . $stop_time);
        $stop_date_s = $stop_date->format('Y-m-d H:i:s'); // idem, may be can use directly concatenation

        $computers_table = GlpiComputer::getTable();
        $locations_table = Location::getTable();
        $zones_table = CarbonIntensityZone::getTable();
        $intensities_table = CarbonIntensity::getTable();

        $request = [
            'SELECT' => [
                CarbonIntensity::getTableField('intensity') . ' AS intensity',
                CarbonIntensity::getTableField('emission_date') . ' AS emission_date',
            ],
            'FROM' => $computers_table,
            'INNER JOIN' => [
                $locations_table => [
                    'FKEY'   => [
                        $computers_table  => 'locations_id',
                        $locations_table => 'id',
                    ]
                ],
                $zones_table => [
                    'FKEY'   => [
                        $locations_table  => 'country',
                        $zones_table => 'name',
                    ]
                ],
                $intensities_table => [
                    'FKEY'   => [
                        $zones_table  => 'id',
                        $intensities_table => 'plugin_carbon_carbonintensityzones_id',
                    ]
                ],
            ],
            'WHERE' => [
                'AND' => [
                    GlpiComputer::getTableField('id') => $this->items_id,
                    CarbonIntensity::getTableField('emission_date') => ['>=', $start_date_s],
                    'NOT' => [ CarbonIntensity::getTableField('emission_date') => ['>', $stop_date_s]],
                ],
            ],
            'ORDER' => CarbonIntensity::getTableField('emission_date') . ' ASC',
        ];

        return $DB->request($request);
    }

    private function computeEmissionPerDay(DateTime $day, int $power, string $start_time, string $stop_time) : ?float
    {
        $query_result = $this->requestCarbonIntensitiesPerDay($day, $start_time, $stop_time);

        if ($query_result->numrows() == 0) {
            return null;
        }

        $total_emission = 0.0;
        $previous_timestamp = 0;
        foreach ($query_result as $row) {
            $emission_date = DateTime::createFromFormat('Y-m-d H:i:s', $row['emission_date']);
            if ($previous_timestamp == 0) {
                $previous_timestamp = $emission_date->getTimestamp();
                continue;
            }
            $timestamp = $emission_date->getTimestamp();
            $delta_time = $timestamp - $previous_timestamp;
            $previous_timestamp = $timestamp;
            // units:
            // power is in Watt
            // delta_time is in seconds
            // intensity is in gCO2/kWh
            $energy_in_kwh = ($power * $delta_time) / (1000.0 * 60 * 60);
            $emission = $row['intensity'] * $energy_in_kwh;
            $total_emission += $emission;
        }

        $total_emission = round($total_emission, PLUGIN_CARBON_FLOAT_PRECISION);
        return $total_emission;
    }
}
