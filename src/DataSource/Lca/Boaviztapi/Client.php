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

namespace GlpiPlugin\Carbon\DataSource\Lca\Boaviztapi;

use DBmysql;
use Dropdown;
use GlpiPlugin\Carbon\Config as CarbonConfig;
use GlpiPlugin\Carbon\DataSource\Lca\AbstractClient;
use GlpiPlugin\Carbon\DataSource\RestApiClientInterface;
use GlpiPlugin\Carbon\DataTracking\TrackedFloat;
use GlpiPlugin\Carbon\Impact\Type;
use GlpiPlugin\Carbon\Source;
use GlpiPlugin\Carbon\Source_Zone;
use GlpiPlugin\Carbon\Zone;

class Client extends AbstractClient
{
    private RestApiClientInterface $client;

    private string $base_url;
    private static string $source_name = 'Boaviztapi';

    /** @var array Supported impact criterias and the multiplier unit of the value returned by Boaviztapi */
    protected array $criteria_units = [
        'gwp'    => 1000,       // Kg
        'adp'    => 1000,       // Kg
        'pe'     => 1000000,    // MJ
        'gwppb'  => 1000,       // Kg
        'gwppf'  => 1000,       // Kg
        'gwpplu' => 1000,       // Kg
        'ir'     => 1000,       // Kg
        'lu'     => 1,          // (no unit)
        'odp'    => 1000,       // Kg
        'pm'     => 1,          // (no unit)
        'pocp'   => 1000,       // Kg
        'wu'     => 1,          // M^3
        'mips'   => 1000,       // Kg
        'adpe'   => 1000,       // Kg
        'adpf'   => 1000000,    // MJ
        'ap'     => 1,          // mol
        'ctue'   => 1,          // CTUe
        // 'ctuh_c' => 1,          // CTUh   request fails when this criteria is added, not a URL encoding issue
        // 'ctuh_nc' => 1,         // CTUh   request fails when this criteria is added, not a URL encoding issue
        'epf'    => 1000,       // Kg
        'epm'    => 1000,       // Kg
        'ept'    => 1,          // mol
    ];

    public function __construct(RestApiClientInterface $client, string $url = '')
    {
        $this->client = $client;
        if (!empty($url)) {
            $this->base_url = $url;
        } else {
            $url = CarbonConfig::getPluginConfigurationValue('boaviztapi_base_url');
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
     * @return bool
     */
    public function createSource(): bool
    {
        // create a source in Source
        $source = new Source();
        $source->getOrCreate([], ['name' => $this->getSourceName()]);

        return (!$source->isNewItem());
    }

    /**
     * Get version of Boaviztapi
     *
     * @return string
     */
    public function queryVersion(): string
    {
        $response = $this->get('utils/version');
        if (!isset($response[0]) || !is_string($response[0])) {
            trigger_error(sprintf(
                'Invalid response from Boavizta API: %s',
                json_encode($response[0] ?? '')
            ), E_USER_WARNING);
            return '';
        }
        return $response[0];
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

    public function getCriteriaUnits(): array
    {
        return $this->criteria_units;
    }

    /**
     * Save zones into database
     *
     * @param array $zones
     * @return void
     */
    public function saveZones(array $zones): void
    {
        $source = new Source();
        $source->getOrCreate([], ['name' => $this->getSourceName()]);
        if ($source->isNewItem()) {
            return;
        }

        foreach ($zones as $code => $name) {
            $zone = new Zone();
            $zone->getOrCreate([], ['name' => $name]);
            if ($zone->isNewItem()) {
                continue;
            }
            $source_zone = new Source_Zone();
            $source_zone->getOrCreate([
                'code' => $code,
            ], [
                'plugin_carbon_sources_id' => $source->getID(),
                'plugin_carbon_zones_id' => $zone->getID(),
            ]);
        }
    }

    public static function getZones()
    {
        /** @var DBmysql $DB */
        global $DB;

        $zone_table = Zone::getTable();
        $source_table = Source::getTable();
        $source_zone_table = Source_Zone::getTable();
        $result = $DB->request([
            'SELECT' => [
                Zone::getTableField('name'),
                Source_Zone::getTableField('code'),
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
                        $source_zone_table => 'plugin_carbon_sources_id',
                        Source::getTable() => 'id',
                    ],
                ],
            ],
            'WHERE' => [
                Source::getTableField('name') => self::$source_name,
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
     * Read the response to find the impacts provided by Boaviztapi
     *
     * @param array $response
     * @param string $scope (must be either embedded or use)
     * @return array
     */
    public function parseResponse(array $response, string $scope): array
    {
        $impacts = [];
        $types = Type::getImpactTypes();
        foreach ($response['impacts'] as $type => $impact) {
            if (!in_array($type, $types)) {
                trigger_error(sprintf('Unsupported impact type %s in class %s', $type, __CLASS__));
                continue;
            }
            $impact_id = Type::getImpactId($type);
            if ($impact_id === false) {
                continue;
            }
            $impacts[$impact_id] = $this->parseCriteria($type, $response['impacts'][$type][$scope]);
        }

        return $impacts;
    }

    protected function parseCriteria(string $name, $impact): ?TrackedFloat
    {
        if ($impact === 'not implemented') {
            return null;
        }

        /** @var array $impact */
        $unit_multiplier = $this->getCriteriaUnits()[$name];
        $value = new TrackedFloat(
            $impact['value'] * $unit_multiplier,
            null,
            TrackedFloat::DATA_QUALITY_ESTIMATED
        );

        return $value;
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
