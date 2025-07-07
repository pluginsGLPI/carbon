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
use DateTimeInterface;
use DateTimeZone;
use DateTimeImmutable;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\Zone;
use GlpiPlugin\Carbon\CarbonIntensitySource_Zone;
use Config as GlpiConfig;
use GLPIKey;
use GlpiPlugin\Carbon\DataSource\RestApiClientInterface;
use GlpiPlugin\Carbon\DataTracking\AbstractTracked;
use GlpiPlugin\Carbon\Toolbox;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Query carbon intensity data from Electricity map
 *
 * API documentation:
 * @see https://static.electricitymaps.com/api/docs/index.html
 */
class ElectricityMapClient extends AbstractClient
{
    const HISTORY_URL = '/carbon-intensity/history';
    const PAST_URL    = '/carbon-intensity/past-range';
    const ZONES_URL   = '/zones'; // Do not send API token

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
        $source = $this->getOrCreateSource();
        if ($source === null) {
            return -1;
        }
        $source_id = $source->getID();

        try {
            $zones = $this->queryZones();
        } catch (\RuntimeException $e) {
            return 0;
        }

        $count = 0;
        $failed = false;
        foreach ($zones as $zone_key => $zone_spec) {
            $zone_input = [
                'name' => $zone_spec['zoneName'],
            ];
            $zone = new Zone();
            if ($zone->getFromDbByCrit($zone_input) === false) {
                if ($this->enableHistorical($zone_spec['zoneName'])) {
                    $zone_input['plugin_carbon_carbonintensitysources_id_historical'] = $source_id;
                }
                if ($zone->add($zone_input) === false) {
                    $failed = true;
                    continue;
                }
            }
            $source_zone = new CarbonIntensitySource_Zone();
            $source_zone->add([
                CarbonIntensitySource::getForeignKeyField() => $source_id,
                Zone::getForeignKeyField() => $zone->getID(),
                'code' => $zone_key,
                'is_download_enabled' => Toolbox::isLocationExistForZone($zone->fields['name']),
            ]);
            $count++;
        }

        if ($failed) {
            $count = -$count;
        } else {
            $this->setZoneSetupComplete();
        }

        return $count;
    }

    /**
     * Enable historical for this source depending in the zone to configure
     *
     * @return boolean
     */
    protected function enableHistorical($zone_name): bool
    {
        if (in_array($zone_name, ['France'])) {
            // Prefer an other source for France
            return false;
        }

        return true;
    }

    private function getToken(): string
    {
        $glpi_key = new GLPIKey();
        $value = GlpiConfig::getConfigurationValue('plugin:carbon', 'electricitymap_api_key');
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
    public function fetchDay(DateTimeImmutable $day, string $zone): array
    {
        $source_zone = new CarbonIntensitySource_Zone();
        $zone_code = $source_zone->getFromDbBySourceAndZone($this->getSourceName(), $zone);

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
            'query' => $params
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
        $last_known_date = $carbon_intensity->getLastKnownDate($zone, $this->getSourceName());
        $intensities = array_filter($intensities, function ($intensity) use ($last_known_date) {
            $intensity_date = DateTime::createFromFormat('Y-m-d\TH:i:s', $intensity['datetime']);
            return $intensity_date > $last_known_date;
        });

        return [
            'source' => $this->getSourceName(),
            $zone => $intensities,
        ];
    }

    public function fetchRange(DateTimeImmutable $start, DateTimeImmutable $stop, string $zone): array
    {

        // TODO: get zones from GLPI locations
        $params = [
            'zone' => $zone,
        ];

        $this->step = 60;

        $response = $this->client->request('GET', $this->base_url . self::PAST_URL, ['query' => $params]);
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

        return $$response['history'];
    }

    protected function formatOutput(array $response, int $step): array
    {
        $intensities = [];
        $timezone = new DateTimeZone('UTC');
        foreach ($response['history'] as $record) {
            $datetime = DateTime::createFromFormat('Y-m-d\TH:i:s+', $record['datetime'], $timezone);
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

        return [
            'source' => $this->getSourceName(),
            $response['zone'] => $intensities,
        ];
    }

    /**
     * Try ti determine the data quality of record
     *
     * @param array $record
     * @return integer
     */
    protected function getDataQuality(array $record): int
    {
        $data_quality = 0;
        if (!$record['isEstimated']) {
            $data_quality = AbstractTracked::DATA_QUALITY_RAW_REAL_TIME_MEASUREMENT;
        }

        return $data_quality;
    }

    public function incrementalDownload(string $zone, DateTimeImmutable $start_date, CarbonIntensity $intensity, int $limit = 0): int
    {
        $count = 0;
        $saved = 0;
        try {
            $data = $this->fetchDay(new DateTimeImmutable(), $zone);
        } catch (AbortException $e) {
            throw $e;
        }
        $saved = $intensity->save($zone, $this->getSourceName(), $data[$zone]);
        $count += abs($saved);
        if ($limit > 0 && $count >= $limit) {
            return $saved > 0 ? $count : -$count;
        }

        return $saved > 0 ? $count : -$count;
    }

    public function fullDownload(string $zone, DateTimeImmutable $start_date, DateTimeImmutable $stop_date, CarbonIntensity $intensity, int $limit = 0, ?ProgressBar $progress = null): int
    {
        // TODO : implement progress bar
        // Disable full download because we miss documentation for PAST_URL endpoint
        $start_date = new DateTime('24 hours ago');
        $start_date->setTime((int) $start_date->format('H'), 0, 0);
        $start_date = DateTimeImmutable::createFromMutable($start_date);
        return $this->incrementalDownload($zone, $start_date, $intensity, $limit);
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
