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
