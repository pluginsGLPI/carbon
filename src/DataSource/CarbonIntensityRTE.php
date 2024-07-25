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
use DateTimeImmutable;
use GlpiPlugin\Carbon\CarbonIntensityZone;

class CarbonIntensityRTE extends AbstractCarbonIntensity
{
    const RECORDS_URL = 'https://odre.opendatasoft.com/api/explore/v2.1/catalog/datasets/eco2mix-national-tr/records';
    const EXPORT_URL  = 'https://odre.opendatasoft.com/api/explore/v2.1/catalog/datasets/eco2mix-national-tr/exports/json';

    private RestApiClientInterface $client;

    public function __construct(RestApiClientInterface $client)
    {
        $this->client = $client;
    }

    public function getSourceName(): string
    {
        return 'RTE';
    }

    public function getDataInterval(): string
    {
        return 'P15M';
    }

    public function getZones(): array {
        return [
            'France',
        ];
    }

    public function getMaxIncrementalAge(): DateTimeImmutable
    {
        $recent_limit = new DateTime('15 days ago');
        $recent_limit->setTime(0, 0, 0);

        return DateTimeImmutable::createFromMutable($recent_limit);
    }

    public function createZones(): int
    {
        $zone = new CarbonIntensityZone();

        $input = ['name' => 'France'];
        if (!$zone->getFromDBByCrit($input)) {
            if (!$zone->add($input)) {
                $this->setZoneSetupComplete();
                return 1;
            } else {
                // Failed to create item
                return -1;
            }
        }

        return 1;
    }

    /**
     * Fetch carbon intensities from Opendata Réseaux-Énergies using real-time dataset.
     *
     * See https://odre.opendatasoft.com/explore/dataset/eco2mix-national-tr/api/?disjunctive.nature
     *
     * Dataset has a depth from MONTH-1 to HOUR-2.
     * Note that the HOUR-2 seems to be not fully guaranted.
     *
     * @param DateTimeImmutable $day date to download from [00::00:00 to 24:00:00[
     * @param string $zone
     * @return array
     */
    public function fetchDay(DateTimeImmutable $day, string $zone): array
    {
        $start = DateTime::createFromImmutable($day);
        $start->setTime(0, 0, 0);
        $stop = clone $start;
        $stop->setTime(23, 59, 59);

        $format = DateTime::ATOM;
        $timezone = $start->getTimezone();
        $from = $start->format($format);
        $to = $stop->format($format);

        $params = [
            'select' => 'taux_co2,date_heure',
            'where' => "date_heure IN [date'$from' TO date'$to']",
            'order_by' => 'date_heure desc',
            'limit' => 4 * 24, // 4 samples per hour = 4 * 24 hours
            'offset' => 0,
            'timezone' => $timezone,
        ];

        $response = $this->client->request('GET', self::RECORDS_URL, ['query' => $params]);
        if (!$response) {
            return [];
        }

        return $this->formatOutput($response['results']);
    }

    /**
     * Fetch carbon intensities from Opendata Réseaux-Énergies using export dataset.
     *
     * See https://odre.opendatasoft.com/explore/dataset/eco2mix-national-tr/api/?disjunctive.nature
     *
     * The method fetches the intensities for the date range specified in argument.
     * @param DateTimeImmutable $start
     * @param DateTimeImmutable $stop
     * @param string $zone
     * @return array
     */
    public function fetchRange(DateTimeImmutable $start, DateTimeImmutable $stop, string $zone): array
    {
        $format = DateTime::ATOM;
        $from = $start->format($format);
        $to = $stop->format($format);

        $timezone = $start->getTimezone();
        $params = [
            'where' => "date_heure IN [date'$from' TO date'$to']",
            'timezone' => $timezone,
        ];
        $response = $this->client->request('GET', self::EXPORT_URL, ['query' => $params]);
        if (!$response) {
            return [];
        }

        return $this->formatOutput($response);
    }

    private function formatOutput(array $response): array
    {
        $intensities = [];
        $intensity = 0.0;
        $count = 0;

        // compute mean value over each hour
        foreach ($response as $record) {
            $matches = [];
            $intensity += $record['taux_co2'];
            $count++;
            $matches = explode(':', $record['heure']);
            if ($matches[1] == '45') {
                // Last sample of the hour (assuming there are 4 samples per hour)
                $intensities[] = [
                    'datetime' => $record['date'] . ' ' . $matches[0] . ':00',
                    'intensity' => intval(round($intensity / $count)),
                ];
                $intensity = 0.0;
                $count = 0;
            }
        }

        return [
            'source' => self::getSourceName(),
            'France' => $intensities,
        ];
    }
}
