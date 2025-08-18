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

namespace GlpiPlugin\Carbon\Tests;

use Config;
use Geocoder\Collection;
use Geocoder\Geocoder;
use Geocoder\Model\AddressCollection;
use Geocoder\Model\AdminLevel;
use Geocoder\Model\AdminLevelCollection;
use Geocoder\Model\Country;
use Geocoder\Provider\Nominatim\Model\NominatimAddress;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\CarbonIntensitySource_Zone;
use GlpiPlugin\Carbon\Zone;
use Location as GlpiLocation;
use GlpiPlugin\Carbon\Location;

class LocationTest extends DbTestCase
{
    /**
     * @covers GlpiPlugin\Carbon\Location::onGlpiLocationAdd
     * @covers GlpiPlugin\Carbon\Location::setBoaviztaZone
     * @covers plugin_carbon_locationAdd
     *
     * @return void
     */
    public function testOnGlpiLocationAdd()
    {
        // Test a location with a predefined zone
        $glpi_location = $this->getItem(GlpiLocation::class);
        $location = new Location();
        $location->getFromDBByCrit([
            'locations_id' => $glpi_location->getID(),
        ]);
        $this->assertTrue($location->isNewItem());

        $glpi_location = $this->getItem(GlpiLocation::class, [
            '_boavizta_zone' => 'FRA',
        ]);
        $location = new Location();
        $location->getFromDBByCrit([
            'locations_id' => $glpi_location->getID(),
        ]);
        $this->assertFalse($location->isNewItem());
        $this->assertEquals('FRA', $location->fields['boavizta_zone']);

        // Test the geocoding feature
        $geocoder = $this->getMockBuilder(Geocoder::class)
            ->getMock();
        $geocoder->method('geocodeQuery')->willReturn(
            new AddressCollection([
                new NominatimAddress(
                    'fake',
                    new AdminLevelCollection(),
                    null,
                    null,
                    null,
                    null,
                    null,
                    'Hà Nội',
                    null,
                    new Country('Vietnam', 'VN'),
                    null
                )
            ])
        );
        $instance = new Location();
        $glpi_location = $this->getItem(GlpiLocation::class, [
            'country' => 'Vietnam',
            'town'    => 'Hanoi',
        ]);
        Config::setConfigurationValues('plugin:carbon', ['geocoding_enabled' => 1]);
        $instance->onGlpiLocationAdd($glpi_location, $geocoder);
        $this->assertEquals('VNM', $instance->fields['boavizta_zone']);
    }

    /**
     * @covers GlpiPlugin\Carbon\Location::onGlpiLocationPreUpdate
     * @covers GlpiPlugin\Carbon\Location::setBoaviztaZone
     * @covers plugin_carbon_locationPreUpdate
     *
     * @return void
     */
    public function testOnGlpiLocationPreUpdate()
    {
        // Test the callback is actually called, and the zone is saved
        $glpi_location = $this->getItem(GlpiLocation::class);
        $location = new Location();
        $location->getFromDBByCrit([
            'locations_id' => $glpi_location->getID(),
        ]);
        $this->assertTrue($location->isNewItem());
        // Update the location
        $this->updateItem($glpi_location, [
            '_boavizta_zone' => 'FRA',
        ]);
        $location = new Location();
        $location->getFromDBByCrit([
            'locations_id' => $glpi_location->getID(),
        ]);
        $this->assertFalse($location->isNewItem());
        $this->assertEquals('FRA', $location->fields['boavizta_zone']);

        // Test the geocoding feature
        $geocoder = $this->getMockBuilder(Geocoder::class)
            ->getMock();
        $geocoder->method('geocodeQuery')->willReturn(
            new AddressCollection([
                new NominatimAddress(
                    'fake',
                    new AdminLevelCollection(),
                    null,
                    null,
                    null,
                    null,
                    null,
                    'Hà Nội',
                    null,
                    new Country('Vietnam', 'VN'),
                    null
                )
            ])
        );
        $glpi_location = $this->getItem(GlpiLocation::class);
        $glpi_location->input = [
            'country' => 'Vietnam',
            'town'    => 'Hanoi',
        ];
        $instance = new Location();
        Config::setConfigurationValues('plugin:carbon', ['geocoding_enabled' => 1]);
        $instance->onGlpiLocationPreUpdate($glpi_location, $geocoder);
        $this->assertEquals('VNM', $instance->fields['boavizta_zone']);
    }

    /**
     * @covers GlpiPlugin\Carbon\Location::onGlpiLocationPrePurge
     * @covers plugin_carbon_locationPrePurge
     *
     * @return void
     */
    public function testOnGlpiLocationPrePurge()
    {
        // Test the location object is deleted when core Location is deleted
        $glpi_location = $this->getItem(GlpiLocation::class, [
            'country' => 'France',
            '_boavizta_zone' => 'FRA',
        ]);
        $location = new Location();
        $location->getFromDBByCrit([
            'locations_id' => $glpi_location->getID()
        ]);
        $this->assertFalse($location->isNewItem());
        $glpi_location->delete($glpi_location->fields);
        $location = new Location();
        $location->getFromDBByCrit([
            'locations_id' => $glpi_location->getID()
        ]);
        $this->assertTrue($location->isNewItem());
    }

    /**
     * @covers GlpiPlugin\Carbon\Location::getIncompleteLocations
     *
     * @return void
     */
    public function testgetIncompleteLocations()
    {
        $iterator = Location::getIncompleteLocations();
        $output = $iterator->count();
        $this->assertEquals(0, $output);

        $glpi_location = $this->getItem(GlpiLocation::class);
        $iterator = Location::getIncompleteLocations();
        $output = $iterator->count();
        $this->assertEquals(1, $output);

        $glpi_location = $this->getItem(GlpiLocation::class, [
            '_boavizta_zone' => 'FRA',
        ]);
        $iterator = Location::getIncompleteLocations();
        $output = $iterator->count();
        $this->assertEquals(1, $output);
    }

    /**
     * @covers GlpiPlugin\Carbon\Location::getCountryCode
     *
     * @return void
     */
    public function testGetCountryCode()
    {
        $geocoder_collection = new AddressCollection([
            new NominatimAddress(
                '',
                new AdminLevelCollection([
                    new AdminLevel(1, 'Île-de-France', 'IDF'),
                    new AdminLevel(2, 'Paris', '75'),
                ]),
                null,
                null,
                null,
                null,
                '75000',
                'Paris',
                null,
                new Country(
                    'France',
                    'FR'
                ),
            ),
        ]);
        $geocoder = $this->createStub(Geocoder::class);
        $geocoder->method('geocodeQuery')->willReturn($geocoder_collection);

        $glpi_location = $this->getItem(GlpiLocation::class, [
            'name' => 'Paris',
            'town'    => 'Paris',
            'country' => 'France'
        ]);
        $instance = new Location();
        $output = $instance->getCountryCode(
            $glpi_location,
            $geocoder
        );
        $this->assertEquals('FRA', $output);
    }

    /**
     * @covers GlpiPlugin\Carbon\Location::enableCarbonIntensityDownload
     *
     * @return void
     */
    public function testEnableCarbonIntensityDownload()
    {
        // Test when the location is an empty object
        $instance = new Location();
        $glpi_location = new GlpiLocation();
        $result = $this->callPrivateMethod($instance, 'enableCarbonIntensityDownload', $glpi_location);
        $this->assertFalse($result);

        // Test when the location does not matches a zone
        $glpi_location = $this->getItem(GlpiLocation::class, [
            'country' => 'Non existent country'
        ]);
        $result = $this->callPrivateMethod($instance, 'enableCarbonIntensityDownload', $glpi_location);
        $this->assertFalse($result);

        // Test when the zone does not matchs a source
        $source = $this->getItem(CarbonIntensitySource::class, [
            'name' => 'bar',
        ]);
        $zone = $this->getItem(Zone::class, [
            'name' => 'foo',
            'plugin_carbon_carbonintensitysources_id_historical' => 0,
        ]);
        $source_zone = $this->getItem(CarbonIntensitySource_Zone::class, [
            $zone::getForeignKeyField() => $zone->getID(),
            $source::getForeignKeyField() => $source->getID(),
            'is_download_enabled' => 0,
        ]);
        $glpi_location = $this->getItem(GlpiLocation::class, [
            'country' => 'bar'
        ]);
        $result = $this->callPrivateMethod($instance, 'enableCarbonIntensityDownload', $glpi_location);
        $this->assertFalse($result);

        // Test when the zone matches a source and download switches to enabled
        $source = $this->getItem(CarbonIntensitySource::class, [
            'name' => 'baz',
        ]);
        $zone = $this->getItem(Zone::class, [
            'name' => 'baz',
            'plugin_carbon_carbonintensitysources_id_historical' => $source->getID(),
        ]);
        $source_zone = $this->getItem(CarbonIntensitySource_Zone::class, [
            $zone::getForeignKeyField() => $zone->getID(),
            $source::getForeignKeyField() => $source->getID(),
            'is_download_enabled' => 0,
        ]);
        $glpi_location = $this->getItem(GlpiLocation::class, [
            'country' => 'baz'
        ]);
        $result = $this->callPrivateMethod($instance, 'enableCarbonIntensityDownload', $glpi_location);
        $this->assertTrue($result);
    }
}
