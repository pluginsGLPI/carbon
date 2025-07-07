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

namespace GlpiPlugin\Carbon\DataSource\CarbonIntensity;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use DBmysql;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\CarbonIntensitySource_Zone;
use GlpiPlugin\Carbon\DataSource\RestApiClientInterface;
use GlpiPlugin\Carbon\Zone;
use GlpiPlugin\Carbon\DataTracking\AbstractTracked;
use GlpiPlugin\Carbon\Toolbox;

/**
 * Query carbon intensity data from Réseau de Transport d'Électricité (RTE)
 * @see https://odre.opendatasoft.com/explore/dataset/eco2mix-national-tr/api/?disjunctive.nature
 *
 * API documentation
 * @see https://help.opendatasoft.com/apis/ods-explore-v2/explore_v2.1.html
 */
class RteClient extends AbstractClient
{
    const RECORDS_URL =         '/eco2mix-national-tr/records';
    const EXPORT_URL_REALTIME      = '/eco2mix-national-tr/exports/json';
    const EXPORT_URL_CONSOLIDATED  = '/eco2mix-national-cons-def/exports/json';

    private RestApiClientInterface $client;

    private string $base_url;

    /** @var bool Use consolidated dataset (true) or realtime dataset (false) */
    private bool $use_consolidated = false;

    public function __construct(RestApiClientInterface $client, string $url = '')
    {
        $this->client = $client;

        $this->base_url = 'https://odre.opendatasoft.com/api/explore/v2.1/catalog/datasets';
        if (!empty($url)) {
            $this->base_url = $url;
        }
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

    public function getSupportedZones(): array
    {
        return [
            null => 'France'
        ];
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
        /** @var DBmysql $DB */
        global $DB;

        $start = DateTime::createFromImmutable($day);
        $stop = clone $start;
        $stop->setTime(23, 59, 59);

        $format = DateTime::ATOM;
        $timezone = $DB->guessTimezone();
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

        $response = $this->client->request('GET', $this->base_url . self::RECORDS_URL, ['timeout' => 8, 'query' => $params]);
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
        /** @var DBmysql $DB */
        global $DB;

        $format = DateTime::ATOM;
        $from = $start->format($format);
        $to = $stop->format($format);

        $timezone = $DB->guessTimezone();
        $interval = $stop->diff($start);
        $where = "date_heure IN [date'$from' TO date'$to'";
        if ($interval->y === 0 && $interval->m === 0 && $interval->d === 0 && $interval->h === 1) {
            $where .= ']';
        } else {
            $where .= '[';
        }
        $params = [
            'select' => 'taux_co2,date_heure',
            'where' => $where,
            'order_by' => 'date_heure asc',
            'timezone' => $timezone,
        ];
        // convert to 15 minutes interval
        if ($this->use_consolidated) {
            $this->step = 60;
            $url = $this->base_url . self::EXPORT_URL_CONSOLIDATED;
        } else {
            $this->step = 15;
            $url = $this->base_url . self::EXPORT_URL_REALTIME;
        }
        $expected_samples_count = (int) ($interval->days * 24)
            + (int) ($interval->h)
            + (int) ($interval->i / 60);

        $alt_response = [];
        $response = $this->client->request('GET', $url, ['timeout' => 8, 'query' => $params]);
        if ($response) {
            // Adjust step as consolidated dataset may return data for each 15 minutes !
            $this->step = $this->detectStep($response);
            $expected_samples_count *= (60 / $this->step);
        }

        // Tolerate DST switching issues with 15 minutes samples (4 missing samples or too many samples)
        if (!$response || abs(count($response) - $expected_samples_count) > 4) {
            // Retry with realtime dataset
            if (!$this->use_consolidated) {
                $this->use_consolidated = true;
                $alt_response = $this->fetchRange($start, $stop, $zone);

                if (!isset($alt_response['error_code']) && count($alt_response) > count($response)) {
                    // Use the alternative response if more samples than the original response
                    $response = $alt_response;
                }
            }
        }

        if (!$response) {
            trigger_error('No response from RTE API for ' . $zone, E_USER_WARNING);
            return [];
        }
        if (count($response) === 0) {
            trigger_error('Empty response from RTE API for ' . $zone, E_USER_WARNING);
            return [];
        }
        if (abs(count($response) - $expected_samples_count) > 4) {
            trigger_error('Not enough samples from RTE API for ' . $zone . ' (expected: ' . $expected_samples_count . ', got: ' . count($response) . ')', E_USER_WARNING);
        }
        if (isset($response['error_code'])) {
            trigger_error($this->formatError($response));
        }

        return $response;
    }

    protected function formatOutput(array $response, int $step): array
    {
        /** @var DBMysql $DB */
        global $DB;

        // array sort records, just in case
        usort($response, function ($a, $b) {
            return $a['date_heure'] <=> $b['date_heure'];
        });

        $this->step = $this->detectStep($response);
        // Deduplicate entries (solves switching from winter time to summer time)
        // because there are 2 samples at same date time, during 1 hour
        // Even if we use UTC timezone.
        $filtered_response = $this->deduplicate($response);

        // Convert string dates into datetime objects, restoring timezone as type Continent/City instead of offset
        // This is needed to detect later the switching to winter time
        $timezone = new DateTimeZone($DB->guessTimezone());
        foreach ($filtered_response as &$record) {
            $record['date_heure'] = DateTime::createFromFormat('Y-m-d\TH:i:s??????', $record['date_heure'], $timezone);
        }

        // Convert samples from 15 min to 1 hour
        if ($this->step < 60) {
            $intensities = $this->convertToHourly($filtered_response, $this->step);
        } else {
            $intensities = [];
            foreach ($filtered_response as $record) {
                $intensities[] = [
                    'datetime' => $record['date_heure']->format('Y-m-d\TH:00:00'),
                    'intensity' => (float) $record['taux_co2'],
                    'data_quality' => AbstractTracked::DATA_QUALITY_RAW_REAL_TIME_MEASUREMENT,
                ];
            }
        }

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

    protected function detectStep(array $records): ?int
    {
        if (count($records) < 2) {
            return 60;
        }

        $sample_1 = DateTime::createFromFormat(DateTime::ATOM, $records[0]['date_heure']);
        $sample_2 = DateTime::createFromFormat(DateTime::ATOM, $records[1]['date_heure']);
        $diff = $sample_1->diff($sample_2);

        if ($diff->h === 1) {
            return 60; // 1 hour step
        } else {
            return $diff->i; // Return the minutes step
        }
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
            $date = $record['date_heure'];
            $count++;
            $intensity += $record['taux_co2'];
            $minute = (int) $date->format('i');

            if ($previous_record_date !== null) {
                // Ensure that current date is $step minutes ahead than previous record date
                $diff = $date->getTimestamp() - $previous_record_date->getTimestamp();
                if ($diff !== $step * 60) {
                    if ($diff == 4500 && $this->switchToWinterTime($previous_record_date, $date)) {
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
    private function switchToWinterTime(DateTime $previous, DateTime $date): bool
    {
        $first_dst = $previous->format('I');
        $second_dst = $date->format('I');
        return $first_dst === '1' && $second_dst === '0';
    }

    private function formatError(array $response): string
    {
        $message = $message = $response['error_code']
        . ' ' . $response['message'];
        return $message;
    }
}
