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
