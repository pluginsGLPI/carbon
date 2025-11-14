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
use DateTimeImmutable;
use DateTimeInterface;
use DateInterval;
use GlpiPlugin\Carbon\DataTracking\TrackedFloat;
use GlpiPlugin\Carbon\Source;
use GlpiPlugin\Carbon\Source_Zone;

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

    public function getCarbonEmissionPerDay(DateTimeInterface $day, Source_Zone $source_zone): ?TrackedFloat
    {
        $power = $this->getPower();

        $start_time = clone $day;
        $start_time->setTime(0, 0, 0, 0);
        $length = new DateInterval('PT' . 86400 . 'S'); // 24h = 86400 seconds
        $source = Source::getById($source_zone->fields['plugin_carbon_sources_id']);
        $fallback_source_zone = null;
        $iterator = null;

        // Try to read real time carbon intensities
        if ($source->fields['fallback_level'] === 0) {
            $iterator = $this->requestCarbonIntensitiesPerDay(DateTimeImmutable::createFromMutable($start_time), $length, $source_zone);
            if ($iterator->count() === 0) {
                // Need to fallback to an alternate source
                $fallback_source_zone = new Source_Zone();
                $fallback_source_zone->getFallbackFromDB($source_zone);
            }
        } else {
            // The source is already a fallback (exapmple: Quebec does has any realtime source)
            $fallback_source_zone = $source_zone;
        }

        $expected_count = 24;

        // Try a fallback source
        if ($fallback_source_zone !== null) {
            $row = array_fill(0, $expected_count, $this->getFallbackCarbonIntensity($start_time, $fallback_source_zone));
            $iterator = new ArrayObject($row);
            $iterator = $iterator->getIterator();
        }

        $count = $iterator ? $iterator->count() : 0;
        if ($count != $expected_count) {
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
