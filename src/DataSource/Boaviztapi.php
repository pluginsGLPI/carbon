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
            if (!is_string($url) || $url === '') {
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
    public function queryZones(): array
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

    public static function getZones()
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

        return $zones;
    }

    /**
     * Show a dropdown of zones handleed by Boaviztapi
     *
     * @param string $name name of the input
     * @param array $options see Dropdown::showFromArray for details
     */
    public static function dropdownBoaviztaZone(string $name, array $options = [])
    {

        return Dropdown::showFromArray($name, self::getZones(), $options);
    }
}
