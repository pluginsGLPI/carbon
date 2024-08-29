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
use GlpiPlugin\Carbon\CarbonIntensityZone;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\DataTracking\TrackedFloat;
use GlpiPlugin\Carbon\DataTracking\TrackedInt;

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
     * @return TrackedFloat energy (KWh)
     *
     * {@inheritDoc}
     */
    public function getEnergyPerDay(DateTime $day): TrackedFloat
    {
        $usage_profile = $this->getUsageProfile();

        if ($usage_profile === null || !self::isUsageDay($usage_profile, $day)) {
            return new TrackedFloat(0, null, TrackedFloat::DATA_QUALITY_MANUAL);
        }

        $power = $this->getPower();

        $day_s = $day->format('Y-m-d');
        $start_date = DateTime::createFromFormat('Y-m-d H:i:s', $day_s . $usage_profile->fields['time_start']);
        $stop_date = DateTime::createFromFormat('Y-m-d H:i:s', $day_s . $usage_profile->fields['time_stop']);
        $delta_time = $stop_date->getTimestamp() - $start_date->getTimestamp();

        // units:
        // power is in Watt
        // delta_time is in seconds
        $energy_in_kwh = ($power->getValue() * $delta_time) / (1000.0 * 60 * 60);

        return new TrackedFloat(
            $energy_in_kwh,
            $power,
            TrackedFloat::DATA_QUALITY_MANUAL
        );
    }

    public function getCarbonEmissionPerDay(DateTime $day, CarbonIntensityZone $zone): ?TrackedFloat
    {
        $usage_profile = $this->getUsageProfile();

        if ($usage_profile === null || !self::isUsageDay($usage_profile, $day)) {
            return new TrackedFloat(0, null, TrackedFloat::DATA_QUALITY_MANUAL);
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
        return $this->computeEmissionPerDay($start_time, $power, $length, $zone);
    }

    protected function computeEmissionPerDay(DateTime $start_time, TrackedInt $power, DateInterval $length, CarbonIntensityZone $zone): ?TrackedFloat
    {
        if ($power->getValue() === 0) {
            return 0;
        }

        $iterator = $this->requestCarbonIntensitiesPerDay($start_time, $length, $zone);

        $total_seconds = (int) $length->format('%S');
        if ($total_seconds === 0) {
            return new TrackedFloat(
                0,
                $power
            );
        }

        if ($iterator->count() === 0) {
            return null;
        }

        $counted_seconds = 0;
        $total_emission = 0.0;
        while (($row = $iterator->current()) !== null) {
            $current_hour = DateTime::createFromFormat('Y-m-d H:i:s', $row['date']);
            // Calculate seconds to next hour
            $next_hour = clone $current_hour;
            $next_hour->add(new DateInterval('PT1H'));
            $next_hour->setTime($next_hour->format('H'), 0, 0, 0);
            $seconds = $next_hour->format('U') - $current_hour->format('U');

            if ($counted_seconds + $seconds > $total_seconds) {
                // Calculate emission of incomplete hour
                $seconds = $total_seconds - $counted_seconds;
            }

            // Calculate emission
            $energy_in_kwh = ($power->getValue() * $seconds) / (1000.0 * 60 * 60);
            $total_emission += $row['intensity'] * $energy_in_kwh;

            $counted_seconds += $seconds;
            if ($counted_seconds >= $total_seconds) {
                return new TrackedFloat(
                    $total_emission,
                    $power,
                    $row['data_quality']
                );
            }
            $iterator->next();
        }

        return new TrackedFloat(
            $total_emission,
            $power
        );
    }
}
