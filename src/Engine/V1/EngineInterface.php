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
use GlpiPlugin\Carbon\CarbonIntensityZone;
/**
 * Compute environnemental impact of a computer
 */
interface EngineInterface
{
    public function getPower(): int;

    /**
     * Returns the carbon emission for the specified day.
     *
     * @param DateTime $day the day
     * @param CarbonIntensityZone $zone_id the zone where the asset is located at the given date
     *
     * @return float or null
     *
     * If no carbon intensity data are available for the specified day, returns null
     * Otherwise, returns the CO2 emission of the day, which can be 0
     *
     * Unit of returned value, if float, is grams of CO2
     */
    public function getCarbonEmissionPerDay(DateTime $day, CarbonIntensityZone $zone): ?float;

    /**
     * Returns the consumed energy for the specified day.
     *
     * @param DateTime $day the day
     *
     * @return float
     *
     * Returns the consumed energy
     *
     * Unit of returned value is kWh (kiloWattHour)
     */
    public function getEnergyPerDay(DateTime $day): float;
}
