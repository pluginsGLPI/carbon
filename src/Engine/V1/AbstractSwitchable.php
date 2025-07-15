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

namespace GlpiPlugin\Carbon\Engine\V1;

use ArrayObject;
use DateTime;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use GlpiPlugin\Carbon\Zone;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\DataTracking\TrackedFloat;
use GlpiPlugin\Carbon\DataTracking\TrackedInt;

abstract class AbstractSwitchable extends AbstractAsset implements SwitchableInterface
{
    /**
     * Tells if the asset is expected to run in the specified date and usage profile
     *
     * @param ComputerUsageProfile $usage_profile
     * @param DateTimeInterface $dateTime
     * @return boolean true if the asset is powered on
     */
    protected static function isUsageDay(ComputerUsageProfile $usage_profile, DateTimeInterface $dateTime): bool
    {
        $day_of_week = $dateTime->format('N');
        $key = 'day_' . strval($day_of_week);

        return $usage_profile->fields[$key] != 0;
    }

    /**
     * Returns the consumed energy for the specified day.
     * @param DateTimeInterface $day
     * @return TrackedFloat energy (KWh)
     *
     * {@inheritDoc}
     */
    public function getEnergyPerDay(DateTimeInterface $day): TrackedFloat
    {
        $usage_profile = $this->getUsageProfile();

        if ($usage_profile === null || !self::isUsageDay($usage_profile, $day)) {
            return new TrackedFloat(0, null, TrackedFloat::DATA_QUALITY_MANUAL);
        }

        $power = $this->getPower();

        $day_s = $day->format('Y-m-d');
        $start_date = DateTime::createFromFormat('Y-m-d H:i:s', $day_s . ' ' . $usage_profile->fields['time_start']);
        $stop_date = DateTime::createFromFormat('Y-m-d H:i:s', $day_s . ' ' . $usage_profile->fields['time_stop']);
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

    public function getCarbonEmissionPerDay(DateTimeInterface $day, Zone $zone): ?TrackedFloat
    {
        $usage_profile = $this->getUsageProfile();

        if ($usage_profile === null || !self::isUsageDay($usage_profile, $day)) {
            return new TrackedFloat(0, null, TrackedFloat::DATA_QUALITY_MANUAL);
        }

        $power = $this->getPower();

        // Assume that start and stop times are HH:ii:ss
        $seconds_start = explode(':', $usage_profile->fields['time_start']);
        $seconds_stop  = explode(':', $usage_profile->fields['time_stop']);
        // Convert to integers
        $seconds_start[0] = (int) $seconds_start[0];
        $seconds_start[1] = (int) $seconds_start[1];
        $seconds_start[2] = (int) $seconds_start[2];
        $seconds_stop[0] = (int) $seconds_stop[0];
        $seconds_stop[1] = (int) $seconds_stop[1];
        $seconds_stop[2] = (int) $seconds_stop[2];

        $start_time = clone $day;
        $start_time->setTime($seconds_start[0], $seconds_start[1], $seconds_start[2]);
        $seconds_start = $seconds_start[0] * 3600 + $seconds_start[1] * 60 + $seconds_start[2];
        $seconds_stop = $seconds_stop[0] * 3600 + $seconds_stop[1] * 60 + $seconds_stop[2];
        $length = new DateInterval('PT' . ($seconds_stop - $seconds_start) . 'S');
        $start_time = DateTimeImmutable::createFromMutable($start_time);
        return $this->computeEmissionPerDay($start_time, $power, $length, $zone);
    }

    protected function computeEmissionPerDay(DateTimeImmutable $start_time, TrackedInt $power, DateInterval $length, Zone $zone): ?TrackedFloat
    {
        if ($power->getValue() === 0) {
            return new TrackedFloat(0);
        }

        $total_seconds = (int) $length->format('%S');
        $expected_count = (int) ceil($total_seconds / 3600);
        $iterator = $this->requestCarbonIntensitiesPerDay($start_time, $length, $zone);
        if ($iterator->count() === 0 && !$zone->hasHistoricalData()) {
            $row = array_fill(0, $expected_count, $this->getFallbackCarbonIntensity($start_time, $zone));
            $iterator = new ArrayObject($row);
            $iterator = $iterator->getIterator();
        }
        $count = $iterator->count();
        if ($iterator->count() != $expected_count) {
            trigger_error(sprintf(
                "required count of carbon intensity %d samples not met. Got %d samples for date %s",
                $expected_count,
                $count,
                $start_time->format('Y-m-d H:i:s')
            ), E_USER_WARNING);
            return null;
        }
        if ($total_seconds === 0) {
            return new TrackedFloat(
                0,
                $power
            );
        }

        if ($count === 0) {
            return null;
        }

        $counted_seconds = 0;
        $total_emission = 0.0;
        while (($row = $iterator->current()) !== null) {
            $current_hour = DateTime::createFromFormat('Y-m-d H:i:s', $row['date']);
            // Calculate seconds to next hour
            $next_hour = clone $current_hour;
            $next_hour->add(new DateInterval('PT1H'));
            $next_hour->setTime((int) $next_hour->format('H'), 0, 0, 0);
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
