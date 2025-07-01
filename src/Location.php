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
use Config as GlpiConfig;
use DBmysql;
use DBmysqlIterator;
use Geocoder\Geocoder;
use Geocoder\Provider\Nominatim\Nominatim;
use Geocoder\Query\GeocodeQuery;
use Geocoder\StatefulGeocoder;
use GuzzleHttp\Client;
use Html;
use Location as GlpiLocation;
use MassiveAction;
use Glpi\Application\View\TemplateRenderer;
use GLPINetwork;
use GlpiPlugin\Carbon\DataSource\Boaviztapi;
use League\ISO3166\ISO3166;
use Session;

/**
 * Additional data for a location
 */
class Location extends CommonDBChild
{
    // From CommonDBRelation
    public static $itemtype       = GlpiLocation::class;
    public static $items_id       = 'locations_id';

    public function showForm($ID, array $options = [])
    {
        $this->getFromDB($ID);
        TemplateRenderer::getInstance()->display('@carbon/location.html.twig', [
            'item' => $this,
        ]);

        return true;
    }

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        switch ($ma->getAction()) {
            case 'MassUpdateBoaviztaZone':
                echo '<div>';
                echo __('Boavizta zone', 'carbon') . '&nbsp;';
                Boaviztapi::dropdownBoaviztaZone('_boavizta_zone');
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
                $categories = Boaviztapi::getZones();
                return $categories[$values['boavizta_zone']] ?? '';
        }

        return '';
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        $options['values'] = $values;
        return Boaviztapi::dropdownBoaviztaZone($name, $options);
    }

    public static function getGeocoder(): Geocoder
    {
        $locale = substr(Session::getLanguage(), 0, 2);
        $user_agent = GLPINetwork::getGlpiUserAgent();
        $provider = Nominatim::withOpenStreetMapServer(new Client(), $user_agent);
        $geocoder = new StatefulGeocoder($provider, $locale);
        return $geocoder;
    }

    /**
     * callback when a GLPI location is added
     *
     * @param CommonDBTM $item
     * @return void
     */
    public static function onGlpiLocationAdd(CommonDBTM $item)
    {
        self::enableCarbonIntensityDownload($item);
        $enabled = GlpiConfig::getConfigurationValue('plugin:carbon', 'geocoding_enabled');
        if (!empty($enabled)) {
            if (!isset($item->input['_boavizta_zone']) || $item->input['_boavizta_zone'] == '0') {
                try {
                    $geocoder = self::getGeocoder();
                    $item->input['_boavizta_zone'] = self::getCountryCode($item, $geocoder);
                } catch (\Geocoder\Exception\QuotaExceeded $e) {
                    trigger_error($e->getMessage(), E_USER_WARNING);
                }
            }
        }
        self::setBoaviztaZone($item);
    }

    /**
     * callback when a GLPI location is updated
     *
     * @param CommonDBTM $item
     * @return void
     */
    public static function onGlpiLocationUpdate(CommonDBTM $item)
    {
        self::enableCarbonIntensityDownload($item);
    }

    /**
     * callback when a GLPI location is updated
     *
     * @param CommonDBTM $item
     * @return void
     */
    public static function onGlpiLocationPreUpdate(CommonDBTM $item)
    {
        $enabled = GlpiConfig::getConfigurationValue('plugin:carbon', 'geocoding_enabled');
        if (!empty($enabled)) {
            if (!isset($item->input['_boavizta_zone']) ||  $item->input['_boavizta_zone'] == '0') {
                // Boavizta zone already set
                try {
                    $geocoder = self::getGeocoder();
                    $item->input['_boavizta_zone'] = self::getCountryCode($item, $geocoder);
                } catch (\Geocoder\Exception\QuotaExceeded $e) {
                    trigger_error($e->getMessage(), E_USER_WARNING);
                }
            }
        }
        self::setBoaviztaZone($item);
    }

    /**
     * Enable download of carbon intensity data for a location
     *
     * @return bool true if a zone download has been enabled
     */
    protected static function enableCarbonIntensityDownload(CommonDBTM $item): bool
    {
        $input = $item->fields;
        if (!in_array('country', array_keys($input))) {
            return false;
        }
        $zone = Zone::getByLocation($item);
        if ($zone === null) {
            return false;
        }
        $source_zone = new CarbonIntensitySource_Zone();
        $source_zone->getFromDBByCrit([
            $zone->getForeignKeyField() => $zone->fields['id'],
            CarbonIntensitySource::getForeignKeyField() => $zone->fields['plugin_carbon_carbonintensitysources_id_historical'],
        ]);
        if ($source_zone->isNewItem()) {
            return false;
        }
        return $source_zone->toggleZone(true);
    }

    /**
     * Associate a zone for a location (added or updated), for Boavizta
     *
     * @param CommonDBTM $item
     * @return bool true if a zone has been set
     */
    protected static function setBoaviztaZone(CommonDBTM $item): bool
    {
        if (!isset($item->input['_boavizta_zone'])) {
            return false;
        }

        $location = new self();
        $location->getFromDBByCrit([
            'locations_id' => $item->getID(),
        ]);

        if ($location->isNewItem()) {
            return false !== $location->add([
                'locations_id' => $item->getID(),
                'boavizta_zone' => $item->input['_boavizta_zone'],
            ]);
        }

        return $location->update([
            'id'            => $location->getID(),
            'boavizta_zone' => $item->input['_boavizta_zone'],
        ]);
    }

    public static function onGlpiLocationPrePurge(CommonDBTM $item): bool
    {
        $location = new self();
        $location->getFromDBByCrit([
            'locations_id' => $item->getID(),
        ]);

        if ($location->isNewItem()) {
            return true;
        }

        return $location->delete($location->fields, true);
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
    public static function getCountryCode(CommonDBTM $item, Geocoder $geocoder): string
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
        /** @var  DBmysql $DB */
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
}
