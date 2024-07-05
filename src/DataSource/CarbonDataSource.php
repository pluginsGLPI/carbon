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

namespace GlpiPlugin\Carbon\DataSource;

use DateTime;

/**
 * The common interface for all classes implementing carbon intensity fetching from various sources.
 * Sources are most of the time REST API, but this is not limitative.
 *
 * Depending on the source, the time range of the intensities may vary.
 *
 * The method returns an array constructed as this:
 * [
 *      'source' => the source name,
 *      'a zone name' => [
 *            [
 *                'datetime' => the date and time of the intensity,
 *                'intensity' => the intensity,
 *            ],
 *            ...
 *        ],
 *      ...
 * ]
 *
 * For example:
 * [
 *      'source' => 'FR_SOURCE',
 *      'France_west' => [
 *            [
 *                'datetime' => "2024-07-03T01:00:00+00:00",
 *                'intensity' => 12,
 *            ],
 *            [
 *                'datetime' => ""2024-07-03T02:00:00+00:00"",
 *                'intensity' => 13,
 *            ],
 *       ],
 *      'France_east' => [
 *            [
 *                'datetime' => "2024-07-03T01:00:00+00:00",
 *                'intensity' => 41,
 *            ],
 *            [
 *                'datetime' => ""2024-07-03T02:00:00+00:00"",
 *                'intensity' => 40,
 *            ],
 *       ],
 * ]
 *
 * The carbon intensity unit is gCO2/kWh
 *
 */

interface CarbonDataSource
{
    /**
     * Fetch carbon intensities from the source.
     *
     * @return an array organized as described above
     */
    public function fetchCarbonIntensity(): array;
}
