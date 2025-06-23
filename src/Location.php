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

    public static function onGlpiLocationUpdate(CommonDBTM $item)
    {
        self::enableCarbonIntensityDownload($item);
    }

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
        if (empty($location_elements)) {
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
