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

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
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

    public function getZones(): array
    {
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
        $timezone = $start->getTimezone()->getName();
        $from = $start->format($format);
        $to = $stop->format($format);

        $params = [
            'select' => 'taux_co2,date_heure',
            'where' => "date_heure IN [date'$from' TO date'$to']",
            'order_by' => 'date_heure asc',
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

        $timezone = $start->getTimezone()->getName();
        $params = [
            'where' => "date_heure IN [date'$from' TO date'$to']",
            'order_by' => 'date_heure asc',
            'timezone' => $timezone,
        ];
        $response = $this->client->request('GET', self::EXPORT_URL, ['timeout' => 8, 'query' => $params]);
        if (!$response) {
            return [];
        }

        return $this->formatOutput($response);
    }

    private function formatOutput(array $response): array
    {
        // array sort records, just in case
        usort($response, function ($a, $b) {
            return $a['date_heure'] <=> $b['date_heure'];
        });

        // Deduplicate entries (solves switching from winter time to summer time)
        // because there are 2 samples at same UTC date time
        $filtered_response = $this->deduplicate($response);

        // Convert samples from 15 min to 1 hour
        $intensities = $this->convertToHourly($filtered_response);

        return [
            'source' => self::getSourceName(),
            'France' => $intensities,
        ];
    }

    protected function deduplicate(array $records): array
    {
        $filtered_response = [];
        foreach ($records as $record) {
            if (isset($filtered_response[$record['date_heure']])) {
                if ($filtered_response[$record['date_heure']]['taux_co2'] != $record['taux_co2']) {
                    // Inconsistency detected. What to do with this record?
                    continue;
                }
                continue;
            }

            $filtered_response[$record['date_heure']] = $record;
        }

        return $filtered_response;
    }

    /**
     * Convert records to 1 hour
     *
     * @param array $records
     * @return array
     */
    protected function convertToHourly(array $records): array
    {
        $intensities = [];
        $intensity = 0.0;
        $count = 0;
        $previous_record_date = null;

        foreach ($records as $record) {
            $date = new DateTime($record['date_heure'], new DateTimeZone("UTC"));
            $count++;
            $intensity += $record['taux_co2'];
            $minute = (int) $date->format('i');

            if ($previous_record_date !== null) {
                // Ensure that current date is 15 minutes ahead than previous record date
                $diff = $date->getTimestamp() - $previous_record_date->getTimestamp();
                if ($diff !== 15 * 60) {
                    if ($diff == 4500 && $this->switchTowinterTime($date)) {
                        // 4500 = 1h + 15m
                        $filled_date = DateTime::createFromFormat('Y-m-d H:i:sT', end($intensities)['datetime']);
                        $filled_date->add(new DateInterval('PT1H'));
                        $intensities[] = [
                            'datetime'  => $filled_date->format('Y-m-d H:i:00+00:00'), // Force UTC timezone
                            'intensity' => (end($intensities)['intensity'] + $record['taux_co2']) / 2,
                        ];
                    } else {
                        // Unexpected gap in the records. What to do with this ?
                        $date_1 = $previous_record_date->format('Y-m-d H:i:00+00:00');
                        $date_2 = $date->format('Y-m-d H:i:00+00:00');
                        trigger_error("Inconsistent date time increment: $diff seconds between $date_1 and $date_2", E_USER_WARNING);
                    }
                }
            }

            if ($minute === 45) {
                $intensities[] = [
                    'datetime' => $date->format('Y-m-d H:00:00+00:00'), // Force UTC timezone
                    'intensity' => (int) round($intensity / $count),
                ];
                $intensity = 0.0;
                $count = 0;
            }

            $previous_record_date = $date;
        }

        return $intensities;
    }

    private function switchTowinterTime(DateTime $date): bool
    {
        // We assume that the datetime is already switched to winter time
        // Therefore summer time is 03:00:00 and winter time is 02:00:00
        $date = clone $date;
        $date->setTimezone(new DateTimeZone('Europe/Paris'));
        $month = (int) $date->format('m');
        $day_of_month = (int) $date->format('d');
        $day_of_week = (int) $date->format('w');
        // Find if this is the day and time to switch to winter time
        if ($month === 10 && $day_of_week === 0 && $day_of_month >= 25) {
            if ($date->format('H:i:s') === '02:00:00') {
                return true;
            }
        }

        return false;
    }
}
