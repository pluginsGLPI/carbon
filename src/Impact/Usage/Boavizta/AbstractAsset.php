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
use DbUtils;
use GlpiPlugin\Carbon\DataSource\Lca\Boaviztapi\Client;
use GlpiPlugin\Carbon\DataTracking\TrackedFloat;
use GlpiPlugin\Carbon\Impact\Type;
use GlpiPlugin\Carbon\Impact\Usage\AbstractUsageImpact;
use GlpiPlugin\Carbon\Location;
use GlpiPlugin\Carbon\UsageImpact;
use Infocom;

abstract class AbstractAsset extends AbstractUsageImpact implements AssetInterface
{
    protected static string $itemtype = '';
    protected static string $type_itemtype  = '';
    protected static string $model_itemtype = '';

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
     * Get the query string specifying the impact criterias for the HTTP request
     *
     * @return string
     */
    protected function getCriteriasQueryString(): string
    {
        $impact_criteria = array_keys($this->client->getCriteriaUnits());
        return 'criteria=' . implode('&criteria=', $impact_criteria);
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

    // /**
    //  * Read the response to find the impacts provided by Boaviztapi
    //  *
    //  * @return array
    //  */
    // protected function parseResponse(array $response): array
    // {
    //     $impacts = [];
    //     $types = Type::getImpactTypes();
    //     foreach ($response['impacts'] as $type => $impact) {
    //         if (!in_array($type, $types)) {
    //             trigger_error(sprintf('Unsupported impact type %s in class %s', $type, __CLASS__));
    //             continue;
    //         }
    //         $impact_id = Type::getImpactId($type);
    //         if ($impact_id === false) {
    //             continue;
    //         }
    //         $impacts[$impact_id] = $this->parseCriteria($type, $response['impacts'][$type]);

    //     }

    //     return $impacts;
    // }

    // protected function parseCriteria(string $name, array $impact): ?TrackedFloat
    // {
    //     if ($impact['embedded'] === 'not implemented') {
    //         return null;
    //     }

    //     $unit_multiplier = $this->client->getCriteriaUnits()[$name];
    //     $value = new TrackedFloat(
    //         $impact['embedded']['value'] * $unit_multiplier,
    //         null,
    //         TrackedFloat::DATA_QUALITY_ESTIMATED
    //     );

    //     return $value;
    // }

    public function getEvaluableQuery(string $itemtype, array $crit = [], bool $entity_restrict = true): array
    {
        $item_table = getTableForItemType($itemtype);
        $glpi_asset_type_itemtype = $itemtype . "Type";
        $glpi_asset_model_itemtype = $itemtype . "Model";
        $glpi_assettype_table = getTableForItemType($glpi_asset_type_itemtype);
        $glpi_assetmodel_table = getTableForItemType($glpi_asset_model_itemtype);
        $glpi_assets_types_fk = getForeignKeyFieldForItemType($glpi_asset_type_itemtype);
        $glpi_assets_models_fk = getForeignKeyFieldForItemType($glpi_asset_model_itemtype);
        $asset_type_itemtype = 'GlpiPlugin\\Carbon\\' . $glpi_asset_type_itemtype;
        $assettype_table = getTableForItemType($asset_type_itemtype);
        $location_table = Location::getTable();
        $infocom_table = Infocom::getTable();
        $usage_impact_table = UsageImpact::getTable();

        $request = [
            'SELECT' => [
                $itemtype::getTableField('id'),
            ],
            'FROM' => $item_table,
            'INNER JOIN' => [
                $location_table => [
                    'FKEY'   => [
                        $item_table  => 'locations_id',
                        $location_table => 'locations_id',
                    ],
                ],
            ],
            'LEFT JOIN' => [
                $usage_impact_table => [
                    'FKEY' => [
                        $usage_impact_table => 'items_id',
                        $item_table            => 'id',
                        ['AND'
                            => [
                                UsageImpact::getTableField('itemtype') => $itemtype,
                            ],
                        ],
                    ],
                ],
                $glpi_assettype_table => [
                    'FKEY' => [
                        $glpi_assettype_table => 'id',
                        $item_table => $glpi_assets_types_fk,
                    ],
                ],
                $assettype_table => [
                    'FKEY'   => [
                        $assettype_table  => $glpi_assets_types_fk,
                        $glpi_assettype_table => 'id',
                        [
                            'AND' => [
                                'NOT' => [$glpi_assettype_table . '.id' => null],
                            ],
                        ],
                    ],
                ],
                $glpi_assetmodel_table => [
                    'FKEY' => [
                        $glpi_assetmodel_table => 'id',
                        $item_table => $glpi_assets_models_fk,
                    ],
                ],
                $infocom_table => [
                    'FKEY' => [
                        $infocom_table => 'items_id',
                        $item_table => 'id',
                        ['AND' => [Infocom::getTableField('itemtype') => $itemtype]],
                    ],
                ],
            ],
            'WHERE' => [
                'AND' => [
                    $itemtype::getTableField('is_deleted') => 0,
                    $itemtype::getTableField('is_template') => 0,
                    ['NOT' => [Location::getTableField('boavizta_zone') => '']],
                    ['NOT' => [Location::getTableField('boavizta_zone') => null]],
                    [
                        'OR' => [
                            $asset_type_itemtype::getTableField('power_consumption') => ['>', 0],
                            $glpi_asset_model_itemtype::getTableField('power_consumption') => ['>', 0],
                        ],
                    ], [
                        'OR' => [
                            ['NOT' => [Infocom::getTableField('use_date') => null]],
                            ['NOT' => [Infocom::getTableField('delivery_date') => null]],
                            ['NOT' => [Infocom::getTableField('buy_date') => null]],
                            // ['NOT' => [Infocom::getTableField('date_creation') => null]],
                            // ['NOT' => [Infocom::getTableField('date_mod') => null]],
                        ],
                    ],
                ],
            ] + $crit,
        ];

        if ($entity_restrict) {
            $entity_restrict = (new DbUtils())->getEntitiesRestrictCriteria($item_table, '', '', 'auto');
            $request['WHERE'] += $entity_restrict;
        }

        return $request;
    }
}
