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

use DateTime;
use GlpiPlugin\Carbon\Zone;
use GlpiPlugin\Carbon\DataTracking\TrackedFloat;
use GlpiPlugin\Carbon\DataTracking\TrackedInt;

/**
 * Compute environmental impact of a computer
 */
interface EngineInterface
{
    /**
     * Returns the power of the computer
     *
     * @return TrackedInt
     */
    public function getPower(): TrackedInt;

    /**
     * Returns the carbon emission for the specified day.
     *
     * @param DateTime $day the day
     * @param Zone $zone the zone where the asset is located at the given date
     *
     * @return TrackedFloat|null
     *
     * If no carbon intensity data are available for the specified day, returns null
     * Otherwise, returns the CO2 emission of the day, which can be 0
     *
     * Unit of returned value, if float, is grams of CO2
     */
    public function getCarbonEmissionPerDay(DateTime $day, Zone $zone): ?TrackedFloat;

    /**
     * Returns the consumed energy for the specified day.
     *
     * @param DateTime $day the day
     *
     * @return TrackedFloat
     *
     * Returns the consumed energy
     *
     * Unit of returned value is kWh (kiloWattHour)
     */
    public function getEnergyPerDay(DateTime $day): TrackedFloat;

    /**
     * Get embodied impact
     *
     * @return array
     */
    // public function getEmbodiedImpact(): array;
}
