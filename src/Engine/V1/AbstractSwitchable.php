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

use DateTime;
use DateInterval;
use GlpiPlugin\Carbon\ComputerUsageProfile;

abstract class AbstractSwitchable extends AbstractAsset implements SwitchableInterface
{
    /**
     * Tells if the asset is expected to run in the specified date and usage profile
     *
     * @param ComputerUsageProfile $usage_profile
     * @param DateTime $dateTime
     * @return boolean true if the asset is powered on
     */
    protected static function isUsageDay(ComputerUsageProfile $usage_profile, DateTime $dateTime): bool
    {
        $day_of_week = $dateTime->format('N');
        $key = 'day_' . strval($day_of_week);

        return $usage_profile->fields[$key] != 0;
    }

    /**
     * Returns the consumed energy for the specified day.
     *
     * {@inheritDoc}
     */
    public function getEnergyPerDay(DateTime $day): float
    {
        $usage_profile = $this->getUsageProfile();

        if ($usage_profile === null || !self::isUsageDay($usage_profile, $day)) {
            return 0;
        }

        $power = $this->getPower();

        $day_s = $day->format('Y-m-d');
        $start_date = DateTime::createFromFormat('Y-m-d H:i:s', $day_s . $usage_profile->fields['time_start']);
        $stop_date = DateTime::createFromFormat('Y-m-d H:i:s', $day_s . $usage_profile->fields['time_stop']);
        $delta_time = $stop_date->getTimestamp() - $start_date->getTimestamp();

        // units:
        // power is in Watt
        // delta_time is in seconds
        $energy_in_kwh = ($power * $delta_time) / (1000.0 * 60 * 60);

        return $energy_in_kwh;
    }

    /**
     * Returns the carbon emission for the specified day.
     *
     * {@inheritDoc}
     */
    public function getCarbonEmissionPerDay(DateTime $day): ?float
    {
        $usage_profile = $this->getUsageProfile();

        if ($usage_profile === null || !self::isUsageDay($usage_profile, $day)) {
            return 0.0;
        }

        $power = $this->getPower();

        // Assume that start and stop times are HH:ii:ss
        $seconds_start = explode(':', $usage_profile->fields['time_start']);
        $seconds_stop  = explode(':', $usage_profile->fields['time_stop']);

        $start_time = clone $day;
        $start_time->setTime($seconds_start[0], $seconds_start[1], $seconds_start[2]);
        $seconds_start = $seconds_start[0] * 3600 + $seconds_start[1] * 60 + $seconds_start[2];
        $seconds_stop = $seconds_stop[0] * 3600 + $seconds_stop[1] * 60 + $seconds_stop[2];
        $length = new DateInterval('PT' . ($seconds_stop - $seconds_start) . 'S');
        return $this->computeEmissionPerDay($start_time, $power, $length);
    }

    protected function computeEmissionPerDay(DateTime $start_time, int $power, DateInterval $length): ?float
    {
        if ($power === 0) {
            return 0;
        }

        $query_result = $this->requestCarbonIntensitiesPerDay($start_time, $length);

        if ($query_result->numrows() == 0) {
            return null;
        }

        $total_emission = 0.0;
        $previous_timestamp = 0;
        $length_seconds = $length->format('%S');
        foreach ($query_result as $row) {
            $emission_date = DateTime::createFromFormat('Y-m-d H:i:s', $row['emission_date']);
            if ($previous_timestamp == 0) {
                $previous_timestamp = $emission_date->getTimestamp();
                continue;
            }

            // calculate seconds where the asset is on within the hour
            $current_timestamp = $emission_date->format('U');
            $next_hour = $current_timestamp;
            $hour_fraction = ($next_hour - $start_time->format('U'));
            $hour_fraction = min($hour_fraction, $length_seconds);

            // units:
            // power is in Watt
            // delta_time is in seconds
            // intensity is in gCO2/kWh
            $energy_in_kwh = ($power * $hour_fraction) / (1000.0 * 60 * 60);
            $emission = $row['intensity'] * $energy_in_kwh;
            $total_emission += $emission;

            // Increment start_time using $emission_date
            // (because this lop assumes that carbon intensity is recorded for last hour)
            // TODO: to define if it should be for the next hour instead
            $start_time = clone $emission_date;
            $length_seconds -= $hour_fraction;
        }

        return $total_emission;
    }
}
