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

namespace GlpiPlugin\Carbon\DataSource\CarbonIntensity\Rte;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use DBmysql;
use GlpiPlugin\Carbon\DataSource\CarbonIntensity\AbstractClient;
use GlpiPlugin\Carbon\DataSource\RestApiClientInterface;
use GlpiPlugin\Carbon\Source;
use GlpiPlugin\Carbon\Source_Zone;
use GlpiPlugin\Carbon\Zone;
use GlpiPlugin\Carbon\DataTracking\AbstractTracked;
use GlpiPlugin\Carbon\Toolbox;

/**
 * Query carbon intensity data from Réseau de Transport d'Électricité (RTE)
 * @see https://odre.opendatasoft.com/explore/dataset/eco2mix-national-tr/api/?disjunctive.nature
 *
 * API documentation
 * @see https://help.opendatasoft.com/apis/ods-explore-v2/explore_v2.1.html
 *
 * About DST for this data source
 * GLPI must be set up with timezones enabled
 * If this requirement is not met, then dates here DST change occurs will cause problems
 * Searching for gaps will find gaps that the algorithm will try to fill, but fail repeatidly.
 *
 * Queries to the provider uses dates intervals in the form [start, stop]. Those boundaries
 * are expresses with timezone +00:00 (aka Z or UTC). An extra timezone parameter is given
 * in the query and let the server respond with datetimes shifted to this timezone.
 */
class Client extends AbstractClient
{
    const EXPORT_URL_REALTIME      = '/eco2mix-national-tr/exports/json';
    const EXPORT_URL_CONSOLIDATED  = '/eco2mix-national-cons-def/exports/json';

    const DATASET_REALTIME = 0;
    const DATASET_CONSOLIDATED = 1;

    private RestApiClientInterface $client;

    private string $base_url;

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
        $source = new source();
        $source->getOrCreate([], [
            'name' => $this->getSourceName(),
        ]);
        if ($source->isNewItem()) {
            return -1;
        }
        $source_id = $source->getID();

        $zone = new Zone();
        $zone->getOrCreate([], [
            'name' => 'France'
        ]);
        if ($zone->isNewItem()) {
            return -1;
        }
        $zone_id = $zone->getID();

        $source_zone = new Source_Zone();
        $source_zone->getOrCreate([
            'code' => '',
            'is_download_enabled' => Toolbox::isLocationExistForZone($zone->fields['name'])
        ], [
            Source::getForeignKeyField() => $source_id,
            Zone::getForeignKeyField() => $zone_id
        ]);
        if ($source_zone->isNewItem()) {
            return -1;
        }
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
        $stop = $day->add(new DateInterval('P1D'));

        $format = DateTime::ATOM;
        $from = $day->format($format);
        $to = $stop->format($format);

        $timezone = new DateTimeZone('Europe/Paris');
        $params = [
            'select' => 'date_heure,taux_co2',
            'where' => "date_heure IN [date'$from' TO date'$to'[ AND taux_co2 is not null",
            'order_by' => 'date_heure asc',
            'timezone' => $timezone->getName(),
        ];

        $url = $this->base_url . self::EXPORT_URL_REALTIME;
        $response = $this->client->request('GET', $url, ['timeout' => 8, 'query' => $params]);
        if (!$response) {
            return [];
        }
        if (isset($response['error_code'])) {
            trigger_error($this->formatError($response));
            return [];
        }

        $this->step = $this->detectStep($response);

        // Drop data with no carbon intensity (may be returned by the provider)
        $response = array_filter($response, function ($item) {
            return $item['taux_co2'] != 0;
        });

        // Drop last rows until we reach
        $safety_count = 0;
        while (($last_item = end($response)) !== false) {
            $time = DateTime::createFromFormat(DateTimeInterface::ATOM, $last_item['date_heure']);
            if ($time->format('i') === '45') {
                // We expect 15 minutes steps
                break;
            }
            array_pop($response);
            $safety_count++;
            if ($safety_count > 3) {
                break;
            }
        }

        return $response;
    }

    /**
     * Fetch range from cached data or online database. Assume that $start is the beginning of a month (Y-m-1 00:00:00)
     * and $stop is the beginning of the next month (Y-m+1-1 00:00:00).
     *
     * @param DateTimeImmutable $start
     * @param DateTimeImmutable $stop
     * @param string $zone
     * @param int $dataset
     * @return array
     */
    public function fetchRange(DateTimeImmutable $start, DateTimeImmutable $stop, string $zone, int $dataset = self::DATASET_REALTIME): array
    {
        // Build realtime and consolidated paths
        $base_path = GLPI_PLUGIN_DOC_DIR . '/carbon/carbon_intensity/' . $this->getSourceName() . '/' . $zone;
        $consolidated_dir = $base_path . '/consolidated';
        $realtime_dir = $base_path . '/realtime';

        // Set timezone to +00:00 and extend range by -12/+14 hours
        $timezone_z = new DateTimeZone('+0000');
        $request_start = $start->setTimezone($timezone_z)->sub(new DateInterval('PT12H'));
        $request_stop = $stop->setTimezone($timezone_z)->add(new DateInterval('PT14H'));
        $format = DateTime::ATOM;
        $from = $request_start->format($format);
        $to = $request_stop->format($format);
        $interval = $request_stop->diff($request_start);
        $expected_samples_count = (int) ($interval->days * 24)
            + (int) ($interval->h)
            + (int) ($interval->i / 60);

        // Choose URL
        switch ($dataset) {
            case self::DATASET_CONSOLIDATED:
                $url = self::EXPORT_URL_CONSOLIDATED;
                $cache_file = $this->getCacheFilename(
                    $consolidated_dir,
                    $start,
                    $stop
                );
                break;
            case self::DATASET_REALTIME:
            default:
                $url = self::EXPORT_URL_REALTIME;
                $cache_file = $this->getCacheFilename(
                    $realtime_dir,
                    $start,
                    $stop
                );
                break;
        }
        $url = $this->base_url . $url;

        // If a cached file exists, use it
        if ($this->use_cache && file_exists($cache_file)) {
            $response = json_decode(file_get_contents($cache_file), true);
            $this->step = $this->detectStep($response);
            return $response;
        }
        @mkdir(dirname($cache_file), 0755, true);

        // Prepare the HTTP request
        $timezone = new DateTimeZone('Europe/Paris'); // Optimal timezone to avoid DST mess in the response
        $where = "date_heure IN [date'$from' TO date'$to'[ AND taux_co2 is not null";
        $params = [
            'select' => 'date_heure,taux_co2',
            'where' => $where,
            'order_by' => 'date_heure asc',
            'timezone' => $timezone->getName()
        ];
        $response = $this->client->request('GET', $url, ['timeout' => 8, 'query' => $params]);
        $this->step = $this->detectStep($response);
        $expected_samples_count *= (60 / $this->step);
        if (($dataset === self::DATASET_REALTIME && abs(count($response) - $expected_samples_count) > 4)) {
            $alt_response = $this->fetchRange($start, $stop, $zone, self::DATASET_CONSOLIDATED);
            if (!isset($alt_response['error_code']) && count($alt_response) > count($response)) {
                // Use the alternative response if more samples than the original response
                $response = $alt_response;
            }
        } else {
            if (count($response) > 0 && $stop->format('Y-m') < date('Y-m')) {
                // Cache only if the month being processed is older than the month of now
                $json = json_encode($response);
                file_put_contents($cache_file, $json);
            }
        }
        return $response;
    }

    protected function getCacheFilename(string $base_dir, DateTimeImmutable $start, DateTimeImmutable $end): string
    {
        $timezone_name = $start->getTimezone()->getName();
        $timezone_name = str_replace('/', '-', $timezone_name);
        return sprintf(
            '%s/%s_%s_%s.json',
            $base_dir,
            $timezone_name,
            $start->format('Y-m-d'),
            $end->format('Y-m-d')
        );
    }

    /**
     * Format the records before saving them in DB
     * It is assumed  that the records are chronologically sorted
     *
     * @param array $response
     * @param integer $step
     * @return array
     */
    protected function formatOutput(array $response, int $step): array
    {
        /** @var DBMysql $DB */
        global $DB;

        $this->step = $this->detectStep($response);
        // Convert string dates into datetime objects,
        // using timezone expressed as type Continent/City instead of offset
        // This is needed to detect later the switching to winter time
        $response = $this->shiftToLocalTimezone($response);

        // Convert samples from to 1 hour
        if ($this->step < 60) {
            $intensities = $this->downsample($response, $this->step);
        } else {
            $intensities = [];
            foreach ($response as $local_datetime => $record) {
                $intensities[] = [
                    'datetime' => $record['date_heure']->format('Y-m-d\TH:00:00??????'),
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

    /**
     * convert dates to the timezone of GLPI
     *
     * @param array $response
     * @return array array of records: ['date_heure' => string, 'taux_co2' => number, 'datetime' => DateTime]
     */
    protected function shiftToLocalTimezone(array $response): array
    {
        /** @var DBMysql $DB */
        global $DB;

        $shifted_response = [];
        $local_timezone = new DateTimeZone($DB->guessTimezone());
        array_walk($response, function ($item, $key) use (&$shifted_response, $local_timezone) {
            $shifted_date_object = DateTime::createFromFormat('Y-m-d\TH:i:sP', $item['date_heure'])
                ->setTimezone($local_timezone);
            $shifted_date_string = $shifted_date_object->format('Y-m-d H:i:sP');
            if (isset($shifted_response[$shifted_date_string]) && $shifted_response['taux_co2'] !== $item['taux_co2']) {
                trigger_error("Duplicate record with different carbon intensity detected.");
            }
            $item['datetime'] = $shifted_date_object;
            $shifted_response[$shifted_date_string] = $item;
        });

        return $shifted_response;
    }

    /**
     * Deduplicates records
     *
     * @param  array $records Records in the format ['date_heure' => DateTime, 'taux_co2' => number]
     * @return array          Array of records where key is the string formatted datetime and value is the carbon intensity
     */
    protected function deduplicate(array $records): array
    {
        $deduplicated = [];
        foreach ($records as $record) {
            $date = $record['date_heure'];
            if (key_exists($date, $deduplicated)) {
                if ($deduplicated[$date]['taux_co2'] != $record['taux_co2']) {
                    // Inconsistency detected. What to do with this record?
                    continue;
                }
                continue;
            }

            $deduplicated[$date] = $record;
        }

        return $deduplicated;
    }

    /**
     * Get the temporal distance between records
     *
     * @param array $records
     * @return integer step in minutes
     */
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
        }
        return $diff->i; // Return the minutes step
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
        if ($step === 60) {
            return $records;
        }

        $intensities = [];
        $intensity = 0.0;
        $count = 0;
        $previous_record_date = null;

        foreach ($records as $record) {
            $date = $record['date_heure'];
            $intensity += $record['taux_co2'];
            if ($record['taux_co2'] === null) {
                continue;
            }
            $count++;
            $minute = (int) $date->format('i');

            if ($previous_record_date !== null) {
                // Ensure that current date is $step minutes ahead than previous record date
                $diff = $date->getTimestamp() - $previous_record_date->getTimestamp();
                if ($diff !== $step * 60) {
                    if ($this->switchToWinterTime(clone $previous_record_date, clone $date)) {
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
                // Finalizing an average of accumulated samples
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
     * Downsample records to a new set of records at the given frequency in minutes.
     * The records may have irregular interval between samples due to filtered out null elements
     *
     * @param  array $records The records to downsample
     * @param  int   $step    The step of output records in minutes
     * @return array          The downsampled records
     */
    protected function downsample(array $records, int $step): array
    {
        $downsampled = [];
        $intensity = 0.0;
        $count = 0;
        foreach ($records as $record) {
            $date = $record['datetime'];
            $intensity += $record['taux_co2'];
            $count++;
            $minute = (int) $date->format('i');

            if ($minute === (60 - $step)) {
                // Finalizing an average of accumulated samples
                $downsampled[] = [
                    'datetime'     => $date->format('Y-m-d\TH:00:00'),
                    'intensity'    => (float) $intensity / $count,
                    'data_quality' => AbstractTracked::DATA_QUALITY_RAW_REAL_TIME_MEASUREMENT_DOWNSAMPLED,
                ];
                $intensity = 0.0;
                $count = 0;
            }
        }

        return $downsampled;
    }

    /**
     * Detect if the given datetime matches a switching ot winter time (DST) for France
     *
     * @return bool
     */
    private function switchToWinterTime(DateTime $previous, DateTime $date): bool
    {
        $timezone_paris = new DateTimeZone('Europe/Paris');
        $previous->setTimezone($timezone_paris);
        $date->setTimezone($timezone_paris);
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
