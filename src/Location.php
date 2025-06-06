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
use Geocoder\Provider\Nominatim\Nominatim;
use Geocoder\Query\GeocodeQuery;
use Geocoder\StatefulGeocoder;
use Html;
use Location as GlpiLocation;
use MassiveAction;
use Glpi\Application\View\TemplateRenderer;
use GLPINetwork;
use GlpiPlugin\Carbon\DataSource\Boaviztapi;
use GuzzleHttp\Client;
use League\ISO3166\ISO3166;

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

    // public static function showMassiveActionsSubForm(MassiveAction $ma)
    // {
    //     switch ($ma->getAction()) {
    //         case 'MassUpdateBoaviztaZone':
    //             echo '<div>';
    //             echo __('Boavizta zone', 'carbon') . '&nbsp;';
    //             Boaviztapi::dropdownBoaviztaZone('_boavizta_zone');
    //             echo '</div>';
    //             echo '<br /><br />' . Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
    //             return true;
    //     }

    //     return parent::showMassiveActionsSubForm($ma);
    // }

    // public static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids)
    // {
    //     switch ($ma->getAction()) {
    //         case 'MassUpdateBoaviztaZone':
    //             foreach ($ids as $id) {
    //                 if ($item->getFromDB($id) && self::updateBoaviztaZone($item, $ma->POST['_boavizta_zone'])) {
    //                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
    //                 } else {
    //                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
    //                 }
    //             }
    //             return;
    //     }
    // }

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

    // public static function getSpecificValueToDisplay($field, $values, array $options = [])
    // {
    //     switch ($field) {
    //         case 'boavizta_zone':
    //             $categories = Boaviztapi::getZones();
    //             return $categories[$values['boavizta_zone']] ?? '';
    //     }

    //     return '';
    // }

    // public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    // {
    //     $options['values'] = $values;
    //     return Boaviztapi::dropdownBoaviztaZone($name, $options);
    // }

    public static function onGlpiLocationAdd(CommonDBTM $item)
    {
        self::enableCarbonIntensityDownload($item);
        $item->fields['_boavizta_zone'] = self::getCountryCode($item);
        self::setBoaviztaZone($item);
    }

    public static function onGlpiLocationUpdate(CommonDBTM $item)
    {
        self::enableCarbonIntensityDownload($item);
    }

    public static function onGlpiLocationPreUpdate(CommonDBTM $item)
    {
        $item->input['_boavizta_zone'] = self::getCountryCode($item);
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

    protected static function getCountryCode(CommonDBTM $item): string
    {
        $http_client = new Client();
        $user_agent = GLPINetwork::getGlpiUserAgent();
        $provider = Nominatim::withOpenStreetMapServer($http_client, $user_agent);
        $geocoder = new StatefulGeocoder($provider, 'fr');
        $location_elements = [
            $item->input['address'] ?? $item->fields['address'],
            $item->input['town'] ?? $item->fields['town'],
            $item->input['state'] ?? $item->fields['state'],
            $item->input['country'] ?? $item->fields['country'],
        ];
        $location_elements = array_filter($location_elements);
        if (empty($location_elements)) {
            return '';
        }
        $location_string = implode(', ', $location_elements);
        $result = $geocoder->geocodeQuery(GeocodeQuery::create($location_string));
        if ($result->isEmpty()) {
            return '';
        }
        $location = $result->get(0);
        $alpha2_code = $location->getCountry()->getCode();
        $data = (new ISO3166())->alpha2($alpha2_code);
        $alpha3_code = $data['alpha3'];

        return $alpha3_code;
    }
}
