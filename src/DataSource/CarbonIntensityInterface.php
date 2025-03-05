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

use DateTimeImmutable;
use GlpiPlugin\Carbon\CarbonIntensity;
use Symfony\Component\Console\Helper\ProgressBar;

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

interface CarbonIntensityInterface
{
    /**
     * Fetch carbon intensities from the source.
     *
     * @return an array organized as described above
     */
    // public function fetchCarbonIntensity(): array;

    /**
     * Is the setup of zones complete ?
     *
     * @return boolean
     */
    public function isZoneSetupComplete(): bool;

    /**
     * Is the download of the zone complete (except daily updates)
     *
     * @param string $zone_name
     * @return boolean
     */
    public function isZoneDownloadComplete(string $zone_name): bool;

    /**
     * are all zones fully downloaded (except dayli updates)
     *
     * @return boolean
     */
    // public function isDownloadComplete(): bool;

    /**
     * Get the source name of the data source
     *
     * @return string
     */
    public function getSourceName(): string;

    /**
     * Create zones handled by the data source
     *
     * @return integer count if item processed
     */
    public function createZones(): int;

    /**
     * Get the absolute starrt date of data from the source
     *
     * @return DateTimeImmutable
     */
    public function getHardStartDate(): DateTimeImmutable;

    /**
     * Get zones handled by the data source
     *
     * @return array
     */
    public function getZones(array $crit = []): array;

    public function getMaxIncrementalAge(): DateTimeImmutable;

    /**
     * Get the date interval between 2 intensity samples
     *
     * @return string
     */
    public function getDataInterval(): string;

    /**
     * Download all available data. Obeys limit of the autoamtic action.
     * If the returned count is negative, then something went wrong
     * and the absolute value of the count tells how many items were saved
     *
     * @param string $zone
     * @param DateTimeImmutable $start_date date where the download must start
     * @param DateTimeImmutable $stop_date date where the download must start
     * @param CarbonIntensity $intensity Instance used to update the database
     * @param integer $limit
     * @param ProgressBar $progress progress bar to update during the download (CLI)
     * @return integer count of successfully saved items
     */
    public function fullDownload(string $zone, DateTimeImmutable $start_date, DateTimeImmutable $stop_date, CarbonIntensity $intensity, int $limit = 0, ?ProgressBar $progress = null): int;

    /**
     * Download recent carbon intensity data day by day
     *
     * @param string $zone zone to process
     * @param DateTimeImmutable $start_date DAte where the downlos must begin
     * @param CarbonIntensity $intensity Instance of CarbonIntensity to use to save data
     * @param integer $limit maximum count of items to process
     * @return integer count of processed items. Negative count on failure
     */
    public function incrementalDownload(string $zone, DateTimeImmutable $start_date, CarbonIntensity $intensity, int $limit = 0): int;
}
