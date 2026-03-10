<?php

/**
 * -------------------------------------------------------------------------
 * Carbon plugin for GLPI
 *
 * @copyright Copyright (C) 2024-2025 Teclib' and contributors.
 * @copyright Copyright (C) 2024 by the carbon plugin team.
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

namespace GlpiPlugin\Carbon\Impact\Embodied\Boavizta;

use GlpiPlugin\Carbon\DataSource\Lca\Boaviztapi\Client;
use GlpiPlugin\Carbon\DataTracking\TrackedFloat;
use GlpiPlugin\Carbon\Impact\Embodied\AbstractEmbodiedImpact;
use GlpiPlugin\Carbon\Impact\Type;

abstract class AbstractAsset extends AbstractEmbodiedImpact implements AssetInterface
{
    /** @var string $engine Name of the calculation engine */
    protected string $engine = 'Boavizta';

    /** @var string $engine_version Version of the calculation engine */
    // protected static string $engine_version = 'unknown';

    /** @var string Endpoint to query for the itemtype, to be filled in child class */
    protected string $endpoint       = '';

    /** @var array $hardware hardware description for the request */
    protected array $hardware = [];

    /** @var Client instance of the HTTP client */
    protected ?Client $client = null;

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
        'wu'     => 1000,       // M^3
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

    // abstract public static function getEngine(CommonDBTM $item): EngineInterface;

    /**
     * Analyze the hardware of the asset to prepare the request to the backend
     *
     * @return void
     */
    abstract protected function analyzeHardware();

    /**
     * Set the REST API client to use for requests
     *
     * @param Client $client
     * @return void
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    protected function getVersion(): string
    {
        if (self::$engine_version !== 'unknown') {
            return self::$engine_version;
        }

        try {
            $response = $this->client->get('utils/version');
        } catch (\RuntimeException $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            throw $e;
        }
        if (!isset($response[0]) || !is_string($response[0])) {
            trigger_error(sprintf(
                'Invalid response from Boavizta API: %s',
                json_encode($response[0] ?? '')
            ), E_USER_WARNING);
            throw new \RuntimeException('Invalid response from Boavizta API');
        }
        self::$engine_version = $response[0];
        return self::$engine_version;
    }

    /**
     * Get the quety string specifying the impact criterias for the HTTP request
     *
     * @return string
     */
    protected function getCriteriasQueryString(): string
    {
        return 'criteria=' . implode('&criteria=', array_keys($this->criteria_units));
    }

    /**
     * Send a HTTP query
     *
     * @param array $description
     * @return array
     */
    protected function query(array $description): array
    {
        try {
            $response = $this->client->post($this->endpoint, [
                'json' => $description,
            ]);
        } catch (\RuntimeException $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            throw $e;
        }

        return $response;
    }

    /**
     * Read the response to find the impacts provided by Boaviztapi
     *
     * @param array $response
     * @return array
     */
    protected function parseResponse(array $response): array
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
            $impacts[$impact_id] = $this->parseCriteria($type, $response['impacts'][$type]);
        }

        return $impacts;
    }

    protected function parseCriteria(string $name, array $impact): ?TrackedFloat
    {
        if ($impact['embedded'] === 'not implemented') {
            return null;
        }

        $unit_multiplier = $this->criteria_units[$name];
        $value = new TrackedFloat(
            $impact['embedded']['value'] * $unit_multiplier,
            null,
            TrackedFloat::DATA_QUALITY_ESTIMATED
        );

        return $value;
    }

    protected function parseGwp(array $impact): ?TrackedFloat
    {
        if ($impact['embedded'] === 'not implemented') {
            return null;
        }

        $value = new TrackedFloat(
            $impact['embedded']['value'],
            null,
            TrackedFloat::DATA_QUALITY_ESTIMATED
        );
        if ($impact['unit'] === 'kgCO2eq') {
            $value->setValue($value->getValue() * 1000);
        }

        return $value;
    }

    protected function parseAdp(array $impact): ?TrackedFloat
    {
        if ($impact['embedded'] === 'not implemented') {
            return null;
        }

        $value = new TrackedFloat(
            $impact['embedded']['value'],
            null,
            TrackedFloat::DATA_QUALITY_ESTIMATED
        );
        if ($impact['unit'] === 'kgSbeq') {
            $value->setValue($value->getValue() * 1000);
        }

        return $value;
    }

    protected function parsePe(array $impact): ?TrackedFloat
    {
        if ($impact['embedded'] === 'not implemented') {
            return null;
        }

        $value = new TrackedFloat(
            $impact['embedded']['value'],
            null,
            TrackedFloat::DATA_QUALITY_ESTIMATED
        );
        if ($impact['unit'] === 'MJ') {
            $value->setValue($value->getValue() * (1000 ** 2));
        }

        return $value;
    }
}
