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

use Config;
use DBmysql;
use Dropdown;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\CarbonIntensitySource_Zone;
use GlpiPlugin\Carbon\Zone;

class Boaviztapi
{
    private RestApiClientInterface $client;

    private string $base_url;
    private static string $source_name = 'Boaviztapi';

    public function __construct(RestApiClientInterface $client, string $url = '')
    {
        $this->client = $client;
        if (!empty($url)) {
            $this->base_url = $url;
        } else {
            $url = Config::getConfigurationValue('plugin:carbon', 'boaviztapi_base_url');
            if (!is_string($url) || strlen($url) === 0) {
                throw new \RuntimeException('Invalid Boaviztapi base URL');
            }
            $this->base_url = $url;
        }
    }

    public function getSourceName(): string
    {
        return self::$source_name;
    }

    public function post(string $endpoint, array $options = []): array
    {
        $options['headers'] = [
            'Accept'       => 'application/json',
        ];
        $response = $this->client->request('POST', $this->base_url . '/v1/' . $endpoint, $options);
        if (!$response) {
            return [];
        }

        return $response;
    }

    public function get(string $endpoint, array $options = []): array
    {
        $options['headers'] = [
            'Accept'       => 'application/json',
        ];
        $response = $this->client->request('GET', $this->base_url . '/v1/' . $endpoint, $options);
        if (!$response) {
            return [];
        }

        return is_array($response) ? $response : [$response];
    }

    /**
     * Create Boavizeta source if it does not exists
     *
     * @return boolean
     */
    public function createSource(): bool
    {
        $source_name = $this->getSourceName();
        // create a source in CarbonIntensitySource
        $source = new CarbonIntensitySource();
        $source->getFromDBByCrit([
            'name' => $source_name,
        ]);
        if ($source->isNewItem()) {
            $id = $source->add([
                'name' => $source_name,
            ]);
            return !$source->isNewID($id);
        }

        return true;
    }

    /**
     * Get zones from Boaviztapi
     * countries or world regions woth a 3 letters code
     *
     * @return array
     */
    public function getZones(): array
    {
        $response = $this->get('utils/country_code');
        ksort($response);
        $response = array_flip($response);
        return $response;
    }

    /**
     * Save zones into database
     *
     * @param array $zones
     * @return void
     */
    public function saveZones(array $zones): void
    {
        $source = new CarbonIntensitySource();
        $source->getFromDBByCrit([
            'name' => $this->getSourceName(),
        ]);
        if ($source->isNewItem()) {
            return;
        }

        $source_id = $source->getID();
        $zone = new Zone();
        foreach ($zones as $code => $name) {
            $zone_id = $zone->getFromDBByCrit([
                'name' => $name,
            ]);
            if ($zone_id === false) {
                $zone_id = $zone->add([
                    'name' => $name,
                ]);
                if ($zone_id === false) {
                    // Failed to add the zone
                    continue;
                }
            } else {
                $zone_id = $zone->getID();
            }
            $source_zone = new CarbonIntensitySource_Zone();
            $source_zone_id = $source_zone->getFromDBByCrit([
                'plugin_carbon_carbonintensitysources_id' => $source_id,
                'plugin_carbon_zones_id' => $zone_id,
            ]);
            if ($source_zone_id === false) {
                $source_zone_id = $source_zone->add([
                    'plugin_carbon_carbonintensitysources_id' => $source_id,
                    'plugin_carbon_zones_id' => $zone_id,
                ]);
                if ($source_zone_id === false) {
                    continue;
                }
            } else {
                $source_zone_id = $source_zone->getID();
            }
            $source_zone->update([
                'id'   => $source_zone_id,
                'code' => $code,
            ]);
        }
    }

    /**
     * Show a dropdown of zones handleed by Boaviztapi
     */
    public static function dropdownBoaviztaZone(string $name, array $options)
    {
        /** @var DBmysql $DB */
        global $DB;

        $zone_table = Zone::getTable();
        $source_table = CarbonIntensitySource::getTable();
        $source_zone_table = CarbonIntensitySource_Zone::getTable();
        $result = $DB->request([
            'SELECT' => [
                Zone::getTableField('name'),
                CarbonIntensitySource_Zone::getTableField('code'),
            ],
            'FROM'   => Zone::getTable(),
            'INNER JOIN' => [
                $source_zone_table => [
                    'FKEY' => [
                        $zone_table => 'id',
                        $source_zone_table => 'plugin_carbon_zones_id',
                    ],
                ],
                $source_table => [
                    'FKEY' => [
                        $source_zone_table => 'plugin_carbon_carbonintensitysources_id',
                        CarbonIntensitySource::getTable() => 'id',
                    ],
                ],
            ],
            'WHERE' => [
                CarbonIntensitySource::getTableField('name') => self::$source_name,
            ],
            'ORDER'  => Zone::getTableField('name'),
        ]);

        $zones = [];
        foreach ($result as $row) {
            $zones[$row['code']] = $row['name'];
        }

        return Dropdown::showFromArray($name, $zones, $options);
    }
}
