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
use DateTimeInterface;
use DateTimeZone;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\CarbonIntensitySource_Zone;
use GlpiPlugin\Carbon\Zone;
use GlpiPlugin\Carbon\DataTracking\AbstractTracked;
use GlpiPlugin\Carbon\Toolbox;

class CarbonIntensityRTE extends AbstractCarbonIntensity
{
    const RECORDS_URL = 'https://odre.opendatasoft.com/api/explore/v2.1/catalog/datasets/eco2mix-national-tr/records';
    const EXPORT_URL_TO_2023  = 'https://odre.opendatasoft.com/api/explore/v2.1/catalog/datasets/eco2mix-national-tr/exports/json';
    const EXPORT_URL_TO_2012  = 'https://odre.opendatasoft.com/api/explore/v2.1/catalog/datasets/eco2mix-national-cons-def/exports/json';

    private RestApiClientInterface $client;

    public function __construct(RestApiClientInterface $client, string $url = '')
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

    public function getHardStartDate(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, '2012-01-01T00:00:00+00:00');
    }

    public function getMaxIncrementalAge(): DateTimeImmutable
    {
        $recent_limit = new DateTime('15 days ago');
        $recent_limit->setTime(0, 0, 0);

        return DateTimeImmutable::createFromMutable($recent_limit);
    }

    public function createZones(): int
    {
        $source = $this->getOrCreateSource();
        if ($source === null) {
            return -1;
        }
        $source_id = $source->getID();

        $zone = new Zone();
        $input = [
            'name' => 'France',
        ];
        if ($zone->getFromDBByCrit($input) === false) {
            $input['plugin_carbon_carbonintensitysources_id_historical'] = $source_id;
            if (!$zone->add($input)) {
                return -1;
            }
        } else {
            if ($zone->fields['plugin_carbon_carbonintensitysources_id_historical'] == 0) {
                $input['plugin_carbon_carbonintensitysources_id_historical'] = $source_id;
                $input['id'] = $zone->getID();
            }
            $zone->update($input);
        }

        $source_zone = new CarbonIntensitySource_Zone();
        $source_zone->add([
            CarbonIntensitySource::getForeignKeyField() => $source_id,
            Zone::getForeignKeyField() => $zone->getID(),
            'is_download_enabled' => Toolbox::isLocationExistForZone($zone->fields['name']),
        ]);
        $this->setZoneSetupComplete();
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
        $stop = clone $start;
        $stop->setTime(23, 59, 59);

        $format = DateTime::ATOM;
        $timezone = $this->prepareTimezone($start->getTimezone()->getName());
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

        $response = $this->client->request('GET', self::RECORDS_URL, ['timeout' => 8, 'query' => $params]);
        if (!$response) {
            return [];
        }
        if (isset($response['error_code'])) {
            trigger_error($this->formatError($response));
            return [];
        }

        // Drop data with no carbon intensity (may be returned by the provider)
        $response['results'] = array_filter($response['results'], function ($item) {
            return $item['taux_co2'] != 0;
        });

        // Drop last rows until we reach
        $safety_count = 0;
        while (($last_item = end($response['results'])) !== false) {
            $time = DateTime::createFromFormat(DateTimeInterface::ATOM, $last_item['date_heure']);
            if ($time->format('i') === '45') {
                // We expect 15 minutes steps
                break;
            }
            array_pop($response['results']);
            $safety_count++;
            if ($safety_count > 3) {
                break;
            }
        }

        return $this->formatOutput($response['results'], 15);
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

        $timezone = $this->prepareTimezone($start->getTimezone()->getName());
        $params = [
            'select' => 'taux_co2,date_heure',
            'where' => "date_heure IN [date'$from' TO date'$to']",
            'order_by' => 'date_heure asc',
            'timezone' => $timezone,
        ];
        if ($stop < DateTime::createFromFormat(DateTimeInterface::ATOM, '2023-02-01T00:00:00+00:00')) {
            $step = 15;
            $url = self::EXPORT_URL_TO_2012;
        } else {
            $step = 15;
            $url = self::EXPORT_URL_TO_2023;
        }
        $response = $this->client->request('GET', $url, ['timeout' => 8, 'query' => $params]);
        if (!$response) {
            trigger_error('No response from RTE API for ' . $zone, E_USER_WARNING);
            return [];
        }
        if (isset($response['error_code'])) {
            trigger_error($this->formatError($response));
            return [];
        }

        return $this->formatOutput($response, $step);
    }

    private function formatOutput(array $response, int $step): array
    {
        // array sort records, just in case
        usort($response, function ($a, $b) {
            return $a['date_heure'] <=> $b['date_heure'];
        });

        // Deduplicate entries (solves switching from winter time to summer time)
        // because there are 2 samples at same UTC date time
        $filtered_response = $this->deduplicate($response);

        // Convert samples from 15 min to 1 hour
        $intensities = $this->convertToHourly($filtered_response, $step);

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
     * @param int   $step : interval in minutes between 2 samples
     * @return array
     */
    protected function convertToHourly(array $records, int $step): array
    {
        $intensities = [];
        $intensity = 0.0;
        $count = 0;
        $previous_record_date = null;

        foreach ($records as $record) {
            $date = DateTime::createFromFormat(DateTimeInterface::ATOM, $record['date_heure']);
            $count++;
            $intensity += $record['taux_co2'];
            $minute = (int) $date->format('i');

            if ($previous_record_date !== null) {
                // Ensure that current date is 15 minutes ahead than previous record date
                $diff = $date->getTimestamp() - $previous_record_date->getTimestamp();
                if ($diff !== $step * 60) {
                    if ($diff == 4500 && $this->switchToWinterTime($date)) {
                        // 4500 = 1h + 15m
                        $filled_date = DateTime::createFromFormat('Y-m-d\TH:i:s', end($intensities)['datetime']);
                        $filled_date->add(new DateInterval('PT1H'));
                        $intensities[] = [
                            'datetime'  => $filled_date->format('Y-m-d\TH:00:00'),
                            'intensity' => (end($intensities)['intensity'] + $record['taux_co2']) / 2,
                            'data_quality' => AbstractTracked::DATA_QUALITY_RAW_REAL_TIME_MEASUREMENT_DOWNSAMPLED,
                        ];
                    } else {
                        // Unexpected gap in the records. What to do with this ?
                        $date_1 = $previous_record_date->format(DateTimeInterface::ATOM);
                        $date_2 = $date->format(DateTimeInterface::ATOM);
                        trigger_error("Inconsistent date time increment: $diff seconds between $date_1 and $date_2", E_USER_WARNING);
                    }
                }
            }

            if ($minute === (60 - $step)) {
                $intensities[] = [
                    'datetime' => $date->format('Y-m-d\TH:00:00'),
                    'intensity' => (float) $intensity / $count,
                    'data_quality' => AbstractTracked::DATA_QUALITY_RAW_REAL_TIME_MEASUREMENT_DOWNSAMPLED,
                ];
                $intensity = 0.0;
                $count = 0;
            }

            $previous_record_date = $date;
        }

        return $intensities;
    }

    /**
     * Detect if the given datetime matches a switching ot winter time (DST)
     *
     * @return bool
     */
    private function switchToWinterTime(DateTime $date): bool
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

    private function formatError(array $response): string
    {
        $message = $message = $response['error_code']
        . ' ' . $response['message'];
        return $message;
    }

    private function prepareTimezone(string $timezone): string
    {
        switch ($timezone) {
            case '+00:00':
                return 'UTC';
        }

        return $timezone;
    }
}
