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

abstract class AbstractPermanent extends AbstractAsset implements EngineInterface
{
    /**
     * Returns the consumed energy for the specified day.
     *
     * {@inheritDoc}
     */
    public function getEnergyPerDay(DateTime $day): float
    {
        $power = $this->getPower();

        $delta_time = 24;

        // units:
        // power is in Watt
        // delta_time is in seconds
        $energy_in_kwh = ($power * $delta_time) / (1000.0);

        return $energy_in_kwh;
    }

    public function getCarbonEmissionPerDay(DateTime $day, CarbonIntensityZone $zone): ?float
    {
        $power = $this->getPower();

        $start_time = clone $day;
        $start_time->setTime(0, 0, 0, 0);
        $length = new DateInterval('PT' . 86400 . 'S'); // 24h = 86400 seconds
        $iterator = $this->requestCarbonIntensitiesPerDay($start_time, $length, $zone);

        $total_emission = 0.0;
        $energy_in_kwh = ($power) / 1000.0;
        foreach ($iterator as $row) {
            $total_emission += $row['intensity'] * $energy_in_kwh;
        }

        return $total_emission;
    }
}
