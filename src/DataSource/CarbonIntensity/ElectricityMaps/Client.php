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

namespace GlpiPlugin\Carbon\DataSource\CarbonIntensity\ElectricityMaps;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use DBmysql;
use GLPIKey;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\DataSource\CarbonIntensity\AbortException;
use GlpiPlugin\Carbon\DataSource\CarbonIntensity\AbstractClient;
use GlpiPlugin\Carbon\DataSource\RestApiClientInterface;
use GlpiPlugin\Carbon\DataTracking\AbstractTracked;
use GlpiPlugin\Carbon\Source;
use GlpiPlugin\Carbon\Source_Zone;
use GlpiPlugin\Carbon\Toolbox;
use GlpiPlugin\Carbon\Zone;
use RuntimeException;
use Safe\Exceptions\FilesystemException;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Query carbon intensity data from Electricity map
 *
 * API documentation:
 * @see https://static.electricitymaps.com/api/docs/index.html
 */
class Client extends AbstractClient
{
    public const HISTORY_URL = '/carbon-intensity/history';
    public const PAST_URL    = '/carbon-intensity/past-range';
    public const ZONES_URL   = '/zones'; // Do not send API token

    private RestApiClientInterface $client;

    private string $base_url;

    public function __construct(RestApiClientInterface $client, string $url = '')
    {
        $this->client = $client;

        $this->base_url = 'https://api.electricitymap.org/v3';
        if (!empty($url)) {
            $this->base_url = $url;
        }
    }

    public function getSourceName(): string
    {
        return 'ElectricityMap';
    }

    public function getDataInterval(): string
    {
        return 'P60M';
    }

    public function getMaxIncrementalAge(): DateTimeImmutable
    {
        $recent_limit = new DateTime('1 day ago');
        $recent_limit->setTime(0, 0, 0);

        return DateTimeImmutable::createFromMutable($recent_limit);
    }

    public function getHardStartDate(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, '2021-01-01T00:00:00+00:00');
    }

    public function createZones(): int
    {
        $source = new Source();
        $source->getOrCreate([], [
            ['name' => $this->getSourceName()],
        ]);
        if ($source->isNewItem()) {
            return -1;
        }
        $source_id = $source->getID();

        try {
            $zones = $this->queryZones();
        } catch (RuntimeException $e) {
            return -1;
        }

        $count = 0;
        $failed = false;
        foreach ($zones as $zone_key => $zone_spec) {
            $zone = new Zone();
            $zone->getOrCreate([], [
                'name' => $zone_spec['zoneName'],
            ]);
            if ($zone->isNewItem()) {
                $failed = true;
                continue;
            }
            $source_zone = new Source_Zone();
            $source_zone->getOrCreate([
                'code' => $zone_key,
                'is_download_enabled' => Toolbox::isLocationExistForZone($zone->fields['name']),
            ], [
                Source::getForeignKeyField() => $source_id,
                Zone::getForeignKeyField() => $zone->getID(),
            ]);
            if ($source_zone->isNewItem()) {
                $failed = true;
                continue;
            }
            $count++;
        }

        if ($failed) {
            $count = -$count;
        } else {
            $this->setZoneSetupComplete();
        }

        return $count;
    }

    private function getToken(): string
    {
        $glpi_key = new GLPIKey();
        $value = Config::getConfigurationValue('electricitymap_api_key');
        return $glpi_key->decrypt($value);
    }

    /**
     * Get zones from the provider
     *
     * @return array
     */
    protected function queryZones(): array
    {
        $response = $this->client->request('GET', $this->base_url . self::ZONES_URL, []);
        if (!$response) {
            return [];
        }

        if (isset($response['error'])) {
            // An error ocured
            trigger_error('' . $response['error'], E_USER_WARNING);
            if ($response['error'] === 'Invalid auth-token') {
                throw new AbortException('Invalid auth-token');
            }
            return [];
        }

        return $response;
    }

    public function getSupportedZones(): array
    {
        $zones = [];
        $raw_zones = $this->queryZones();
        foreach ($raw_zones as $id => $zone) {
            $zones[$id] = $zone['zoneName'];
        }

        return $zones;
    }

    /**
     * Fetch carbon intensities from Opendata Réseaux-Énergies using export dataset.
     *
     * See https://odre.opendatasoft.com/explore/dataset/eco2mix-national-tr/api/?disjunctive.nature
     *
     * The method fetches the intensities for the date range specified in argument.
     */
    public function fetchDay(DateTimeImmutable $day, Source_Zone $source_zone): array
    {
        $zone_code = $source_zone->fields['code'];

        if ($zone_code === null) {
            throw new AbortException('Invalid zone');
        }

        $params = [
            'zone' => $zone_code,
        ];

        $options = [
            'headers' => [
                'auth-token' => $this->getToken(),
            ],
            'query' => $params,
        ];

        $response = $this->client->request('GET', $this->base_url . self::HISTORY_URL, $options);
        if (!$response) {
            return [];
        }

        if (isset($response['status']) && $response['status'] === 'error' || isset($response['error'])) {
            // An error ocured
            if ($response['message'] === 'Invalid auth-token') {
                throw new AbortException('Invalid auth-token');
            }
            if (preg_match("#^Zone '[^']*' does not exist.$#", $response['message']) !== false) {
                throw new AbortException($response['message']);
            }
            return [];
        }

        $intensities = [];
        $timezone = new DateTimeZone('UTC');
        foreach ($response['history'] as $record) {
            $datetime = DateTime::createFromFormat('Y-m-d\TH:i:s+', $record['datetime'], $timezone);
            if (!$datetime instanceof DateTimeInterface) {
                continue;
            }
            $data_quality = $this->getDataQuality($record);
            $intensities[] = [
                'datetime' => $datetime->format('Y-m-d\TH:i:s'),
                'intensity' => $record['carbonIntensity'],
                'data_quality' => $data_quality,
            ];
        }

        // Filter out already existing entries
        $carbon_intensity = new CarbonIntensity();
        $last_known_date = $carbon_intensity->getLastKnownDate($source_zone);
        $intensities = array_filter($intensities, function ($intensity) use ($last_known_date) {
            $intensity_date = DateTime::createFromFormat('Y-m-d\TH:i:s', $intensity['datetime']);
            return $intensity_date > $last_known_date;
        });

        $zone = Zone::getById($source_zone->fields[getForeignKeyFieldForItemType(Zone::class)]);
        $zone_name = $zone->fields['name'];
        return [
            'source' => $this->getSourceName(),
            $zone_name => $intensities,
        ];
    }

    public function fetchRange(DateTimeImmutable $start, DateTimeImmutable $stop, Source_Zone $source_zone): array
    {
        $this->step = 60;
        $is_free_plan = Config::getConfigurationValue('electricitymap_fake_data') ?? 0;
        $dataset = $is_free_plan ? 'fake' : 'real';
        $zone = new Zone();
        $zone->getFromDBByCrit(['id' => $source_zone->fields[Zone::getForeignKeyField()]]);
        $base_path = GLPI_PLUGIN_DOC_DIR . '/carbon/carbon_intensity/' . $this->getSourceName() . '/' . $dataset . '/' . $zone->fields['name'];
        $cache_file = $this->getCacheFilename(
            $base_path,
            $start,
            $stop,
            $start->getTimezone()
        );
        // If cached file exists, use it
        if (file_exists($cache_file)) {
            $full_response = json_decode(file_get_contents($cache_file), true);
            return $full_response;
        } else {
            $cache_dir = dirname($cache_file);
            if (!is_dir($cache_dir)) {
                try {
                    mkdir($cache_dir, 0o755, true);
                } catch (FilesystemException $e) {
                    trigger_error($e->getMessage(), E_USER_WARNING);
                }
            }
        }

        // Set timezone to +00:00 and extend range by 12 hours on each side
        $timezone_z = new DateTimeZone('+0000');
        $request_start = $start->setTimezone($timezone_z)->sub(new DateInterval('PT12H'));
        $request_stop = $stop->setTimezone($timezone_z)->add(new DateInterval('PT14H'));

        // Prevent downloading in the future
        $request_stop = min($request_stop, new DateTime('yesterday midnight', $timezone_z));
        if ($request_start > $request_stop) {
            return [];
        }
        $headers = [];

        $this->step = 60;

        $step = new DateInterval('PT240H');
        $full_response = [];
        $current_date = DateTime::createFromImmutable($request_start);
        $glpikey = new GLPIKey();
        $api_key = Config::getConfigurationValue('electricitymap_api_key');
        $api_key = $glpikey->decrypt($api_key);
        $format = 'Y-m-d\+H:ip';
        $headers['auth-token'] = $api_key;
        while ($current_date < $request_stop) {
            $stop = clone $current_date;
            $stop->add($step);
            // For some reason, passing the parameters as a query stringthrough Guzzle
            // Makes the request malformed from the point of view of Electricitymaps
            // Workarounded by building here the query string
            // $params = [
            //     'zone' => $source_zone->fields['code'],
            //     'start' => $current_date->format('Y-m-d\+H:i'),
            //     'end' => $stop->format('Y-m-d\+H:i'),
            // ];
            $url = $this->base_url . self::PAST_URL;
            $url .= '?zone=' . $source_zone->fields['code'];
            $url .= '&start=' . $current_date->format($format);
            $url .= '&end=' . $stop->format($format);
            $url .= '&temporalGranularity=' . 'hourly';
            $url .= '&emissionFactorType=' . 'lifecycle';
            $response = $this->client->request('GET', $url, [
                'timeout' => 4,
                /*'query' => $params,*/
                'headers' => $headers,
            ]);
            if (isset($response['status']) && $response['status'] === 'error') {
                trigger_error('Electricity maps API error: ' . $response['message'], E_USER_ERROR);
            }
            if (isset($response['error'])) {
                trigger_error('Electricity maps API error: ' . $response['error'], E_USER_ERROR);
            }
            if (!$full_response) {
                $full_response = $response;
            } else {
                $full_response['data'] = array_merge($full_response['data'], $response['data']);
            }
            $current_date = min($request_stop, $stop);
        }
        if (!$full_response) {
            return [];
        }

        if (isset($response['error'])) {
            // An error ocured
            trigger_error('' . $response['error'], E_USER_WARNING);
            if ($response['error'] === 'Invalid auth-token') {
                throw new AbortException('Invalid auth-token');
            }
            return [];
        }

        $downloaded_year_month = $start->format('Y-m');
        if (count($full_response) > 0 && $downloaded_year_month < date('Y-m')) {
            $json = json_encode($full_response);
            file_put_contents($cache_file, $json);
        }
        return $full_response['data'];
    }

    protected function formatOutput(array $response, int $step): array
    {
        // Convert string dates into datetime objects,
        // using timezone expressed as type Continent/City instead of offset
        // This is needed to detect later the switching to winter time
        $response = $this->shiftToLocalTimezone($response);
        $intensities = [];
        foreach ($response['data'] as $record) {
            $datetime = $record['datetime'];
            if (!$datetime instanceof DateTimeInterface) {
                var_dump(DateTime::getLastErrors());
                continue;
            }
            $data_quality = $this->getDataQuality($record);
            $intensities[] = [
                'datetime'     => $datetime->format(DateTime::ATOM),
                'intensity'    => $record['carbonIntensity'],
                'data_quality' => $data_quality,
            ];
        }

        return $intensities;
    }

    /**
     * convert dates to the timezone of GLPI
     *
     * @param array $response
     * @return array array of records: ['date_heure' => string, 'taux_co2' => number, 'datetime' => DateTime]
     */
    protected function shiftToLocalTimezone(array $response): array
    {
        /** @var DBmysql $DB */
        global $DB;

        $shifted_response = [];
        $local_timezone = new DateTimeZone($DB->guessTimezone());
        array_walk($response['data'], function ($item, $key) use (&$shifted_response, $local_timezone) {
            $shifted_date_object = DateTime::createFromFormat('Y-m-d\TH:i:s.vp', $item['datetime'])
                ->setTimezone($local_timezone);
            $shifted_date_string = $shifted_date_object->format('Y-m-d H:i:sP');
            if (isset($shifted_response[$shifted_date_string]) && $shifted_response['carbonIntensity'] !== $item['carbonIntensity']) {
                trigger_error("Duplicate record with different carbon intensity detected.");
            }
            $item['datetime'] = $shifted_date_object;
            $shifted_response[$shifted_date_string] = $item;
        });

        return ['zone' => $response['zone'], 'data' => $shifted_response];
    }

    /**
     * Try to determine the data quality of record
     *
     * @param array $record
     * @return int see AbstractTracked::DATA_QUALITY_* constants
     */
    protected function getDataQuality(array $record): int
    {
        $data_quality = $record['isEstimated'] ? AbstractTracked::DATA_QUALITY_ESTIMATED : AbstractTracked::DATA_QUALITY_RAW_REAL_TIME_MEASUREMENT;

        return $data_quality;
    }

    public function incrementalDownload(Source_Zone $source_zone, DateTimeImmutable $start_date, CarbonIntensity $intensity, int $limit = 0): int
    {
        $count = 0;
        $saved = 0;
        try {
            $data = $this->fetchDay(new DateTimeImmutable(), $source_zone);
        } catch (AbortException $e) {
            throw $e;
        }
        $zone = Zone::getById($source_zone->fields[Zone::getForeignKeyField()]);
        $saved = $intensity->save($source_zone, $data[$zone->fields['name']]);
        $count += abs($saved);

        return $saved > 0 ? $count : -$count;
    }

    public function fullDownload(Source_Zone $source_zone, DateTimeImmutable $start_date, DateTimeImmutable $stop_date, CarbonIntensity $intensity, int $limit = 0, ?ProgressBar $progress = null): int
    {
        $use_free_plan = (int) Config::getConfigurationValue('electricitymap_free_plan');
        if ($use_free_plan === 0) {
            return parent::fullDownload($source_zone, $start_date, $stop_date, $intensity, $limit);
        }

        // Disable full download because we miss documentation for PAST_URL endpoint
        $start_date = new DateTime('24 hours ago');
        $start_date->setTime((int) $start_date->format('H'), 0, 0);
        $start_date = DateTimeImmutable::createFromMutable($start_date);
        return $this->incrementalDownload($source_zone, $start_date, $intensity, $limit);
    }

    protected function sliceDateRangeByDay(DateTimeImmutable $start, DateTimeImmutable $stop)
    {
        $real_start = $start;
        $real_stop = $stop->setTime(0, 0, 0);

        $current_date = DateTime::createFromImmutable($real_start);
        while ($current_date <= $real_stop) {
            yield DateTimeImmutable::createFromMutable($current_date);
            $current_date->add(new DateInterval('P1D'));
            $current_date->setTime(0, 0, 0);
        }
    }
}
