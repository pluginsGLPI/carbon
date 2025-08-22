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

namespace GlpiPlugin\Carbon\Impact\Usage\Boavizta;

use CommonDBTM;
use DateTime;
use DbUtils;
use GlpiPlugin\Carbon\DataSource\Boaviztapi;
use GlpiPlugin\Carbon\DataTracking\TrackedFloat;
use GlpiPlugin\Carbon\Impact\Usage\AbstractUsageImpact;
use GlpiPlugin\Carbon\Impact\Type;
use GlpiPlugin\Carbon\Location;
use GlpiPlugin\Carbon\Zone;
use Location as GlpiLocation;
use Glpi\DBAL\QueryExpression;

abstract class AbstractAsset extends AbstractUsageImpact implements AssetInterface
{
    protected static string $itemtype = '';
    protected static string $type_itemtype  = '';
    protected static string $model_itemtype = '';

    /** @var string $engine Name of the calculation engine */
    protected string $engine = 'Boavizta';

    /** @var string $engine_version Version of the calculation engine */
    protected string $engine_version = 'unknown';

    /** @var string Endpoint to query for the itemtype, to be filled in child class */
    protected string $endpoint       = '';

    /** @var array $hardware hardware description for the request */
    protected array $hardware = [];

    /** @var Boaviztapi instance of the HTTP client */
    protected ?Boaviztapi $client = null;

    // abstract public static function getEngine(CommonDBTM $item): EngineInterface;

    /**
     * Analyze the hardware of the asset to prepare the request to the backend
     * @param CommonDBTM $item asset to analyze
     *
     * @return void
     */
    abstract protected function analyzeHardware(CommonDBTM $item);

    /**
     * Get the average power of the asset from the best source available (model or type)
     *
     * @param int $id ID of the asset (itemtype determined from the class)
     * @return null|int average power in Watt
     */
    abstract protected function getAveragePower(int $id): ?int;

    /**
     * Set the REST API client to use for requests
     *
     * @param Boaviztapi $client
     * @return void
     */
    public function setClient(Boaviztapi $client)
    {
        $this->client = $client;
        $this->engine_version = $this->getVersion();
    }

    protected function getVersion(): string
    {
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

        return $response[0];
    }

    protected function query($description): array
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
     * @return array
     */
    protected function parseResponse(array $response): array
    {
        $impacts = [];
        foreach ($response['impacts'] as $type => $impact) {
            if (!in_array($type, Type::getImpactTypes())) {
                trigger_error(sprintf('Unsupported impact type %s in class %s', $type, __CLASS__));
                continue;
            }

            switch ($type) {
                case 'gwp':
                    // Disabled as Carbon calculates itself carbon emissions
                    // $impacts[Type::IMPACT_GWP] = $this->parseGwp($response['impacts']['gwp']);
                    $impacts[Type::IMPACT_GWP] = null;
                    break;
                case 'adp':
                    $impacts[Type::IMPACT_ADP] = $this->parseAdp($response['impacts']['adp']);
                    break;
                case 'pe':
                    $impacts[Type::IMPACT_PE] = $this->parsePe($response['impacts']['pe']);
                    break;
            }
        }

        return $impacts;
    }

    protected function parseGwp(array $impact): ?TrackedFloat
    {
        if ($impact['use'] === 'not implemented') {
            return null;
        }

        $value = new TrackedFloat(
            $impact['use']['value'],
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
        if ($impact['use'] === 'not implemented') {
            return null;
        }

        $value = new TrackedFloat(
            $impact['use']['value'],
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
        if ($impact['use'] === 'not implemented') {
            return null;
        }

        $value = new TrackedFloat(
            $impact['use']['value'],
            null,
            TrackedFloat::DATA_QUALITY_ESTIMATED
        );
        if ($impact['unit'] === 'MJ') {
            $value->setValue($value->getValue() * (1000 ** 2));
        }

        return $value;
    }

    /**
     * Get the zone code the asset belongs to
     * Location's country must match a zone name
     *
     * @param  CommonDBTM $item
     * @param  DateTime $date Date for which the zone must be found
     * @return string|null
     */
    protected function getZoneCode(CommonDBTM $item, ?DateTime $date = null): ?string
    {
        // TODO: use date to find where was the asset at the given date
        if ($date === null) {
            $item_table = (new DbUtils())->getTableForItemType(static::$itemtype);
            $glpi_location_table = GlpiLocation::getTable();
            $location_table = Location::getTable();
            $location = new Location();
            $found = $location->getFromDBByRequest([
                'INNER JOIN' => [
                    $glpi_location_table => [
                        'FKEY' => [
                            $location_table => 'locations_id',
                            $glpi_location_table => 'id',
                        ],
                    ],
                ],
                'WHERE' => [
                    GlpiLocation::getTableField('id') => $item->fields['locations_id'],
                ]
            ]);

            if ($found === false) {
                return null;
            }

            return $location->fields['boavizta_zone'];
        }

        throw new \LogicException('Not implemented yet');
    }
}
