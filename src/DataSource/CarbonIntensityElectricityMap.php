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
use DateTimeInterface;
use DateTimeZone;
use DateTimeImmutable;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\CarbonIntensityZone;
use GlpiPlugin\Carbon\CarbonIntensitySource_CarbonIntensityZone;
use Config as GlpiConfig;
use GLPIKey;

class CarbonIntensityElectricityMap extends AbstractCarbonIntensity
{
    const HISTORY_URL = 'https://api.electricitymap.org/v3/carbon-intensity/history';
    const PAST_URL    = 'https://api.electricitymap.org/v3/carbon-intensity/past-range';
    const ZONES_URL    = 'https://api.electricitymap.org/v3/zones'; // Do not send API token

    private RestApiClientInterface $client;

    public function __construct(RestApiClientInterface $client)
    {
        $this->client = $client;
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
            $zones = $this->downloadZones();
        } catch (\RuntimeException $e) {
            return 0;
        }

        $count = 0;
        $failed = false;
        foreach ($zones as $zone_key => $zone_spec) {
            $zone_input = [
                'name' => $zone_spec['zoneName'],
            ];
            if ($this->enableHistorical($zone_spec['zoneName'])) {
                $zone_input['plugin_carbon_carbonintensitysources_id_historical'] = $source_id;
            }
            $zone = new CarbonIntensityZone();
            if ($zone->getFromDbByCrit($zone_input) === false) {
                if ($zone->add($zone_input) === false) {
                    $failed = true;
                    continue;
                }
            }
            $source_zone = new CarbonIntensitySource_CarbonIntensityZone();
            $source_zone->add([
                CarbonIntensitySource::getForeignKeyField() => $source_id,
                CarbonIntensityZone::getForeignKeyField() => $zone->getID(),
                'code' => $zone_key,
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

    protected function downloadZones(): array
    {
        $response = $this->client->request('GET', self::ZONES_URL, []);
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

    /**
     * Fetch carbon intensities from Opendata Réseaux-Énergies using export dataset.
     *
     * See https://odre.opendatasoft.com/explore/dataset/eco2mix-national-tr/api/?disjunctive.nature
     *
     * The method fetches the intensities for the date range specified in argument.
     */
    public function fetchDay(DateTimeImmutable $day, string $zone): array
    {
        $source_zone = new CarbonIntensitySource_CarbonIntensityZone();
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

        $response = $this->client->request('GET', self::HISTORY_URL, $options);
        if (!$response) {
            return [];
        }

        if (isset($response['status']) && $response['status'] === 'error') {
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
        foreach ($response['history'] as $record) {
            $datetime = DateTime::createFromFormat('Y-m-d\TH:i:s+', $record['datetime'], new DateTimeZone('UTC'));
            if (!$datetime instanceof DateTimeInterface) {
                continue;
            }
            $intensities[] = [
                'datetime' => $datetime->format('Y-m-d\TH:i:s'),
                'intensity' => $record['carbonIntensity'],
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

        $response = $this->client->request('GET', self::PAST_URL, ['query' => $params]);
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

        $intensities = [];
        foreach ($response['history'] as $record) {
            $datetime = DateTime::createFromFormat('Y-m-d\TH:i:s+', $record['datetime'], new DateTimeZone('UTC'));
            if (!$datetime instanceof DateTimeInterface) {
                var_dump(DateTime::getLastErrors());
                continue;
            }
            $intensities[] = [
                'datetime' => $datetime->format(DateTime::ATOM),
                'intensity' => $record['carbonIntensity'],
            ];
        }

        return [
            'source' => $this->getSourceName(),
            $response['zone'] => $intensities,
        ];
    }

    public function incrementalDownload(string $zone, DateTimeImmutable $start_date, CarbonIntensity $intensity, int $limit = 0): int
    {
        $end_date = new DateTimeImmutable('now');

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

    public function fullDownload(string $zone, DateTimeImmutable $start_date, DateTimeImmutable $stop_date, CarbonIntensity $intensity, int $limit = 0): int
    {
        // Disable full download because we miss documentation for PAST_URL endpoint
        $start_date = new DateTime('24 hours ago');
        $start_date->setTime($start_date->format('H'), 0, 0);
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
