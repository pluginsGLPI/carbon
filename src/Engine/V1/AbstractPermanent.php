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
use DateTimeImmutable;
use DateTimeInterface;
use DateInterval;
use GlpiPlugin\Carbon\Zone;
use GlpiPlugin\Carbon\DataTracking\TrackedFloat;

abstract class AbstractPermanent extends AbstractAsset implements EngineInterface
{
    /**
     * Returns the consumed energy for the specified day.
     *
     * {@inheritDoc}
     */
    public function getEnergyPerDay(DateTimeInterface $day): TrackedFloat
    {
        $power = $this->getPower();

        $delta_time = 24;

        // units:
        // power is in Watt
        // delta_time is in seconds
        $energy_in_kwh = ($power->getValue() * $delta_time) / (1000.0);

        return new TrackedFloat(
            $energy_in_kwh,
            $power
        );
    }

    public function getCarbonEmissionPerDay(DateTimeInterface $day, Zone $zone): ?TrackedFloat
    {
        $power = $this->getPower();

        $start_time = clone $day;
        $start_time->setTime(0, 0, 0, 0);
        $length = new DateInterval('PT' . 86400 . 'S'); // 24h = 86400 seconds
        $iterator = $this->requestCarbonIntensitiesPerDay(DateTimeImmutable::createFromMutable($start_time), $length, $zone);
        if ($iterator->count() === 0 && !$zone->hasHistoricalData()) {
            // Fallback to the closest value available
            $row = array_fill(0, 24, $this->getFallbackCarbonIntensity($day, $zone));
            $iterator = new ArrayObject($row);
            $iterator = $iterator->getIterator();
        }
        $count = $iterator->count();
        if ($count != 24) {
            trigger_error(sprintf(
                'required count of carbon intensity %d samples not met. Got %d samples for date %s',
                24,
                $count,
                $start_time->format('Y-m-d H:i:s')
            ), E_USER_WARNING);
            return null;
        }

        $total_emission = 0.0;
        $quality = null;
        $energy_in_kwh = ($power->getValue()) / 1000.0;

        foreach ($iterator as $row) {
            $total_emission += $row['intensity'] * $energy_in_kwh;
            if ($quality === null) {
                $quality = $row['data_quality'];
            } else {
                $quality = min($row['data_quality'], $quality);
            }
        }

        return new TrackedFloat(
            $total_emission,
            $power,
            $quality
        );
    }
}
