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

namespace GlpiPlugin\Carbon;

use CommonDBChild;
use CommonDBTM;
use CommonGLPI;
use Config as GlpiConfig;
use DBmysql;
use DBmysqlIterator;
use DbUtils;
use Geocoder\Geocoder;
use Geocoder\Query\GeocodeQuery;
use Html;
use Location as GlpiLocation;
use MassiveAction;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use GlpiPlugin\Carbon\DataSource\Lca\Boaviztapi\Client as BoaviztapiClient;
use League\ISO3166\ISO3166;

/**
 * Additional data for a location. Extends the Location object from GLPI with aditional fields
 */
class Location extends CommonDBChild
{
    // From CommonDBRelation
    public static $itemtype       = GlpiLocation::class;
    public static $items_id       = 'locations_id';

    public static function getIcon()
    {
        return 'ti ti-map-2';
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (is_a($item, GlpiLocation::class)) {
            return self::createTabEntry(__('Environmental impact', 'carbon'), 0);
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (is_a($item, GlpiLocation::class)) {
            /** @var GlpiLocation $item */
            $location = new self();
            $location->showForLocation($item);
        }
        return true;
    }

    public function prepareInputForAdd($input)
    {
        if (isset($input['plugin_carbon_sources_id']) && isset($input['plugin_carbon_zones_id'])) {
            $source_zone = new Source_Zone();
            $source_zone->getFromDBByCrit([
                'plugin_carbon_sources_id' => $input['plugin_carbon_sources_id'],
                'plugin_carbon_zones_id' => $input['plugin_carbon_zones_id'],
            ]);
            if (!$source_zone->isNewItem()) {
                $input['plugin_carbon_sources_zones_id'] = $source_zone->getID();
            }
        }

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        if (isset($input['plugin_carbon_sources_id']) && isset($input['plugin_carbon_zones_id'])) {
            $source_zone = new Source_Zone();
            $source_zone->getFromDBByCrit([
                'plugin_carbon_sources_id' => $input['plugin_carbon_sources_id'],
                'plugin_carbon_zones_id' => $input['plugin_carbon_zones_id'],
            ]);
            if (!$source_zone->isNewItem()) {
                $input['plugin_carbon_sources_zones_id'] = $source_zone->getID();
            } else {
                $input['plugin_carbon_sources_zones_id'] = 0;
            }
        }

        return $input;
    }

    public function showForLocation(GlpiLocation $item, array $options = [])
    {
        /** @var DBmysql $DB */
        global $DB;

        $this->getFromDBByCrit(['locations_id' => $item->getID()]);
        if ($this->isNewItem()) {
            $this->add(['locations_id' => $item->getID()]);
        }

        $source_zone_table = Source_Zone::getTable();
        $source_table = Source::getTable();
        $iterator = $DB->request([
            'SELECT' => [
                Source_Zone::getTableField('plugin_carbon_sources_id') . ' AS sources_id',
                Source_Zone::getTableField('plugin_carbon_zones_id') . ' AS zones_id',
            ],
            'FROM' => $source_table,
            'LEFT JOIN' => [
                $source_zone_table => [
                    'FKEY' => [
                        $source_zone_table => 'plugin_carbon_sources_id',
                        $source_table => 'id',
                    ]
                ]
            ],
            'WHERE' => [
                'is_carbon_intensity_source' => 1,
                Source_Zone::getTableField('id') => $this->fields['plugin_carbon_sources_zones_id'],
            ]
        ]);
        $row = $iterator->current();
        $source_id = $row['sources_id'] ?? 0;
        $zone_id = $row['zones_id'] ?? 0;
        if ($source_id === 0) {
            $zone_id = 0;
        }

        TemplateRenderer::getInstance()->display('@carbon/location.html.twig', [
            'item' => $this,
            'params' => [
                'candel' => false,
            ],
            'source_id' => $source_id,
            'zone_id'   => $zone_id,
            'zone_condition' => Zone::getRestrictBySourceCondition($source_id),
        ]);

        return true;
    }

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        switch ($ma->getAction()) {
            case 'MassUpdateBoaviztaZone':
                echo '<div>';
                echo __('Boavizta zone', 'carbon') . '&nbsp;';
                BoaviztapiClient::dropdownBoaviztaZone('_boavizta_zone');
                echo '</div>';
                echo '<br /><br />' . Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                return true;
        }

        return parent::showMassiveActionsSubForm($ma);
    }

    public static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids)
    {
        switch ($ma->getAction()) {
            case 'MassUpdateBoaviztaZone':
                foreach ($ids as $id) {
                    if ($item->getFromDB($id) && self::updateBoaviztaZone($item, $ma->POST['_boavizta_zone'])) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                    }
                }
                return;
        }
    }

    /**
     * Update the location with the Boavizta zone
     *
     * @param CommonDBTM $item Computer to update
     * @param string $zone pwoer consumption to set
     * @return bool
     */
    public static function updateBoaviztaZone(CommonDBTM $item, string $zone): bool
    {
        $location = new self();
        $core_location_id = $item->getID();
        $location->getFromDBByCrit([
            'locations_id' => $core_location_id,
        ]);
        if ($location->isNewItem()) {
            $id = $location->add([
                'locations_id' => $core_location_id,
                'boavizta_zone'    => $zone,
            ]);
            return !$location->isNewId($id);
        } else {
            return $location->update([
                'id'            => $location->getID(),
                'boavizta_zone' => $zone,
            ]);
        }
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        switch ($field) {
            case 'boavizta_zone':
                $categories = BoaviztapiClient::getZones();
                return $categories[$values['boavizta_zone']] ?? '';
        }

        return '';
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        $options['values'] = $values;
        return BoaviztapiClient::dropdownBoaviztaZone($name, $options);
    }

    /**
     * callback when a GLPI location is added
     *
     * @param CommonDBTM $item
     * @param Geocoder $geocoder
     * @return void
     */
    public function onGlpiLocationAdd(CommonDBTM $item, Geocoder $geocoder)
    {
        $enabled = GlpiConfig::getConfigurationValue('plugin:carbon', 'geocoding_enabled');
        if (!empty($enabled)) {
            if (!isset($item->input['_boavizta_zone']) || $item->input['_boavizta_zone'] == '' || $item->input['_boavizta_zone'] == '0') {
                try {
                    $item->input['_boavizta_zone'] = $this->getCountryCode($item, $geocoder);
                } catch (\Geocoder\Exception\QuotaExceeded $e) {
                    trigger_error($e->getMessage(), E_USER_WARNING);
                }
            }
        }
        $this->setBoaviztaZone($item);
    }

    /**
     * callback when a GLPI location is updated
     *
     * @param CommonDBTM $item
     * @return void
     */
    public function onGlpiLocationUpdate(CommonDBTM $item)
    {
        $this->enableCarbonIntensityDownload($item);
    }

    /**
     * callback when a GLPI location is updated
     *
     * @param CommonDBTM $item
     * @param Geocoder $geocoder
     * @return void
     */
    public function onGlpiLocationPreUpdate(CommonDBTM $item, Geocoder $geocoder)
    {
        $enabled = GlpiConfig::getConfigurationValue('plugin:carbon', 'geocoding_enabled');
        if (!empty($enabled)) {
            if (!isset($item->input['_boavizta_zone']) ||  $item->input['_boavizta_zone'] == '0') {
                try {
                    $item->input['_boavizta_zone'] = $this->getCountryCode($item, $geocoder);
                } catch (\Geocoder\Exception\QuotaExceeded $e) {
                    trigger_error($e->getMessage(), E_USER_WARNING);
                }
            }
        }
        $this->setBoaviztaZone($item);
    }

    /**
     * Enable download of carbon intensity data for a location
     *
     * @param CommonDBTM $item Location
     * @return bool true if a zone download has been enabled
     */
    protected function enableCarbonIntensityDownload(CommonDBTM $item): bool
    {
        $source_zone = new Source_Zone();
        /** @var GlpiLocation $item */
        $source_zone->getFromDbByItem($item);
        if ($source_zone->isNewItem()) {
            return false;
        }
        return $source_zone->toggleZone(true);
    }

    /**
     * Tells if the carbon intensity download is enabled
     *
     * @param CommonDBTM $item
     * @return boolean
     */
    public function isCarbonIntensityDownloadEnabled(CommonDBTM $item): bool
    {
        $source_zone = new Source_Zone();
        /** @var GlpiLocation $item */
        if (!$source_zone->getFromDbByItem($item)) {
            return false;
        }
        return $source_zone->fields['is_download_enabled'] === 1;
    }

    /**
     * Get request to find carbon intensity sources
     *
     * @param array $crit Criterias
     * @return array
     */
    public static function getCarbonIntensityDataSourceRequest(array $crit = []): array
    {
        $carbon_intensity_table = CarbonIntensity::getTable();
        $source_zone_table = Source_Zone::getTable();
        $source_table = Source::getTable();
        $location_table = Location::getTable();
        $source_zone_fk = Source_Zone::getForeignKeyField();
        $source_fk = Source::getForeignKeyField();
        $zone_fk = Zone::getForeignKeyField();
        $request = [
            'COUNT' => 'count',
            'FROM' => $location_table,
            'INNER JOIN' => [
                $source_zone_table => [
                    'ON' => [
                        $source_zone_table => 'id',
                        $location_table => $source_zone_fk
                    ]
                ],
                $source_table => [
                    'ON' => [
                        $source_table => 'id',
                        $source_zone_table => $source_fk,
                        [
                            'AND' => [
                                Source::getTableField('is_carbon_intensity_source') => 1,
                            ]
                        ]
                    ]
                ],
            ],
            'LEFT JOIN' => [
                $source_zone_table . ' AS alternate_sources_zones' => [
                    'ON' => [
                        $source_zone_table => 'plugin_carbon_zones_id',
                        'alternate_sources_zones' => 'plugin_carbon_zones_id',
                    ]
                ],
                $source_table . ' AS alternate_sources' => [
                    'ON' => [
                        'alternate_sources' => 'id',
                        'alternate_sources_zones' => $source_fk,
                        [
                            'AND' => [
                                'alternate_sources.is_carbon_intensity_source' => 1,
                                'alternate_sources.fallback_level' => ['>', new QueryExpression(Source::getTableField('fallback_level'))],
                            ]
                        ]
                    ]
                ],
                $carbon_intensity_table => [
                    'ON' => [
                        $carbon_intensity_table => $zone_fk,
                        $source_zone_table => $zone_fk,
                        [
                            'AND' => [
                                'OR' => [
                                    [CarbonIntensity::getTableField($source_fk) => new QueryExpression(Source_Zone::getTableField($source_fk))],
                                    [CarbonIntensity::getTableField($source_fk) => new QueryExpression('alternate_sources_zones.' . $source_fk)],
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'WHERE' => $crit
        ];

        return $request;
    }

    /**
     * Tells if a location has fallback carbon intensity data
     *
     * @param CommonDBTM $item
     * @return boolean
     */
    public function hasFallbackCarbonIntensityData(CommonDBTM $item): bool
    {
        /** @var DBmysql $DB */
        global $DB;

        if ($item->getType() === GlpiLocation::class) {
            $location_id = $item->getID();
        } else {
            $location_id = $item->fields['locations_id'];
        }

        $request = self::getCarbonIntensityDataSourceRequest([
            Location::getTableField('locations_id') => $location_id,
            'OR' => [
                // Primary source is a fallback or alternate source is a fallback
                'NOT' => ['alternate_sources.id' => null],
                Source::getTableField('fallback_level') => ['>', 0]
            ],
        ]);

        $result = $DB->request($request);
        return ($result->current()['count'] > 0);
    }

    /**
     * Associate a zone for a location (added or updated), for Boavizta
     *
     * @param CommonDBTM $item
     * @return bool true if a zone has been set
     */
    protected function setBoaviztaZone(CommonDBTM $item): bool
    {
        if (!isset($item->input['_boavizta_zone'])) {
            return false;
        }

        $this->getFromDBByCrit([
            'locations_id' => $item->getID(),
        ]);

        if ($this->isNewItem()) {
            return false !== $this->add([
                'locations_id' => $item->getID(),
                'boavizta_zone' => $item->input['_boavizta_zone'],
            ]);
        }

        return $this->update([
            'id'            => $this->getID(),
            'boavizta_zone' => $item->input['_boavizta_zone'],
        ]);
    }

    public function onGlpiLocationPrePurge(CommonDBTM $item): bool
    {
        $this->getFromDBByCrit([
            'locations_id' => $item->getID(),
        ]);

        if ($this->isNewItem()) {
            return true;
        }

        return $this->delete($this->fields, true);
    }

    /**
     * Find the country code of a location based on its address, town, state and country
     *
     * @param CommonDBTM $item
     * @param Geocoder $geocoder
     * @return string
     *
     * @throws \Geocoder\Exception\Exception
     */
    public function getCountryCode(CommonDBTM $item, Geocoder $geocoder): string
    {
        $location_elements = [
            // $item->input['address'] ?? $item->fields['address'],
            $item->input['town'] ?? $item->fields['town'],
            $item->input['state'] ?? $item->fields['state'],
            $item->input['country'] ?? $item->fields['country'],
        ];
        $location_elements = array_filter($location_elements);
        if ($location_elements === []) {
            return '';
        }
        $location_string = implode(', ', $location_elements);
        try {
            $result = $geocoder->geocodeQuery(GeocodeQuery::create($location_string));
        } catch (\Geocoder\Exception\QuotaExceeded $e) {
            throw $e;
        } catch (\Geocoder\Exception\Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return '';
        }
        if ($result->isEmpty()) {
            return '';
        }
        $location = $result->get(0);
        $alpha2_code = $location->getCountry()->getCode();
        $data = (new ISO3166())->alpha2($alpha2_code);
        $alpha3_code = $data['alpha3'];

        return $alpha3_code;
    }

    /**
     * Get an iterator for incomplete locations
     *
     * @param array $where aditional request parameters (WHERE, LIMIT, etc.)
     * @return DBmysqlIterator
     */
    public static function getIncompleteLocations(array $where = []): DBmysqlIterator
    {
        /** @var DBmysql $DB */
        global $DB;

        $where = array_diff_key($where, [
            'FROM' => '',
            'SELECT' => '',
            'COUNT' => '',
            'GROUPBY' => '',
        ]);

        // SQL request to get unsolved locations, using the query builder of GLPI
        $glpi_location_table = GlpiLocation::getTable();
        $location_table = self::getTable();
        $request = [
            'SELECT' => [
                $glpi_location_table  => 'id',
                $location_table => 'boavizta_zone',
            ],
            'FROM' => $glpi_location_table,
            'LEFT JOIN' => [
                $location_table => [
                    'ON' => [
                        $location_table => 'locations_id',
                        $glpi_location_table => 'id',
                    ]
                ]
            ],
            'WHERE' => [
                [
                    'OR' => [
                        ['boavizta_zone' => null],
                        ['boavizta_zone' => ''],
                    ]
                ]
            ]
        ];
        $request = array_merge_recursive($request, $where);
        $result = $DB->request($request);

        return $result;
    }

    public function getSourceZoneId(): int
    {
        /** @var DBmysql $DB */
        global $DB;

        if ($this->isNewItem()) {
            return 0;
        }

        if (!Source_Zone::isNewID($this->fields['plugin_carbon_sources_zones_id'])) {
            return $this->fields['plugin_carbon_sources_zones_id'];
        }

        $location_table = self::getTable();
        $glpi_location_table = GlpiLocation::getTable();
        $ancestors = (new DbUtils())->getAncestorsOf($glpi_location_table, $this->fields['locations_id']);
        if (count($ancestors) === 0) {
            return 0;
        }

        $ancestors = array_values($ancestors); // Drop keys
        $request = [
            'SELECT' => self::getTableField('plugin_carbon_sources_zones_id'),
            'FROM' => $glpi_location_table,
            'INNER JOIN' => [
                $location_table => [
                    'FKEY' => [
                        $glpi_location_table => 'id',
                        $location_table => 'locations_id',
                    ]
                ]
            ],
            'WHERE' => [
                GlpiLocation::getTableField('id') => $ancestors,
                self::getTableField('plugin_carbon_sources_zones_id') => ['>', 0]
            ],
            'ORDER' => 'level DESC',
            'LIMIT' => '1'
        ];
        $iterator = $DB->request($request);
        if ($iterator->count() === 0) {
            return 0;
        }

        return $iterator->current()['plugin_carbon_sources_zones_id'];
    }
}
