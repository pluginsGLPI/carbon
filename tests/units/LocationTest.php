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
use DateTime;
use Geocoder\Geocoder;
use Geocoder\Model\AddressCollection;
use Geocoder\Model\AdminLevel;
use Geocoder\Model\AdminLevelCollection;
use Geocoder\Model\Country;
use Geocoder\Provider\Nominatim\Model\NominatimAddress;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\Location;
use GlpiPlugin\Carbon\Source;
use GlpiPlugin\Carbon\Source_Zone;
use GlpiPlugin\Carbon\Zone;
use Location as GlpiLocation;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Location::class)]
class LocationTest extends DbTestCase
{
    /**
     * #CoversMethod GlpiPlugin\Carbon\Location::onGlpiLocationAdd
     * #CoversMethod GlpiPlugin\Carbon\Location::setBoaviztaZone
     * #CoversMethod plugin_carbon_locationAdd
     *
     * @return void
     */
    public function testOnGlpiLocationAdd()
    {
        // Test a location with a predefined zone
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = new Location();
        $location->getFromDBByCrit([
            'locations_id' => $glpi_location->getID(),
        ]);
        $this->assertTrue($location->isNewItem());

        $glpi_location = $this->createItem(GlpiLocation::class, [
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
                ),
            ])
        );
        $instance = new Location();
        $glpi_location = $this->createItem(GlpiLocation::class, [
            'country' => 'Vietnam',
            'town'    => 'Hanoi',
        ]);
        Config::setConfigurationValues('plugin:carbon', ['geocoding_enabled' => 1]);
        $instance->onGlpiLocationAdd($glpi_location, $geocoder);
        $this->assertEquals('VNM', $instance->fields['boavizta_zone']);
    }

    /**
     * #CoversMethod GlpiPlugin\Carbon\Location::onGlpiLocationPreUpdate
     * #CoversMethod GlpiPlugin\Carbon\Location::setBoaviztaZone
     * #CoversMethod plugin_carbon_locationPreUpdate
     *
     * @return void
     */
    public function testOnGlpiLocationPreUpdate()
    {
        // Test the callback is actually called, and the zone is saved
        $glpi_location = $this->createItem(GlpiLocation::class);
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
                ),
            ])
        );
        $glpi_location = $this->createItem(GlpiLocation::class);
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
     * #CoversMethod GlpiPlugin\Carbon\Location::onGlpiLocationPrePurge
     * #CoversMethod plugin_carbon_locationPrePurge
     *
     * @return void
     */
    public function testOnGlpiLocationPrePurge()
    {
        // Test the location object is deleted when core Location is deleted
        $glpi_location = $this->createItem(GlpiLocation::class, [
            'country' => 'France',
            '_boavizta_zone' => 'FRA',
        ]);
        $location = new Location();
        $location->getFromDBByCrit([
            'locations_id' => $glpi_location->getID(),
        ]);
        $this->assertFalse($location->isNewItem());
        $glpi_location->delete($glpi_location->fields);
        $location = new Location();
        $location->getFromDBByCrit([
            'locations_id' => $glpi_location->getID(),
        ]);
        $this->assertTrue($location->isNewItem());
    }

    /**
     * #CoversMethod GlpiPlugin\Carbon\Location::getIncompleteLocations
     *
     * @return void
     */
    public function testGetIncompleteLocations()
    {
        $iterator = Location::getIncompleteLocations();
        $output = $iterator->count();
        $this->assertEquals(0, $output);

        $glpi_location = $this->createItem(GlpiLocation::class);
        $iterator = Location::getIncompleteLocations();
        $output = $iterator->count();
        $this->assertEquals(1, $output);

        $glpi_location = $this->createItem(GlpiLocation::class, [
            '_boavizta_zone' => 'FRA',
        ]);
        $iterator = Location::getIncompleteLocations();
        $output = $iterator->count();
        $this->assertEquals(1, $output);
    }

    /**
     * #CoversMethod GlpiPlugin\Carbon\Location::getCountryCode
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

        $glpi_location = $this->createItem(GlpiLocation::class, [
            'name' => 'Paris',
            'town'    => 'Paris',
            'country' => 'France',
        ]);
        $instance = new Location();
        $output = $instance->getCountryCode(
            $glpi_location,
            $geocoder
        );
        $this->assertEquals('FRA', $output);
    }

    /**
     * #CoversMethod GlpiPlugin\Carbon\Location::enableCarbonIntensityDownload
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
        $glpi_location = $this->createItem(GlpiLocation::class, [
            'country' => 'Non existent country',
        ]);
        $result = $this->callPrivateMethod($instance, 'enableCarbonIntensityDownload', $glpi_location);
        $this->assertFalse($result);

        // Test when the zone does not matchs a source
        $source = $this->createItem(Source::class, [
            'name' => 'bar',
        ]);
        $zone = $this->createItem(Zone::class, [
            'name' => 'foo',
        ]);
        $source_zone = $this->createItem(Source_Zone::class, [
            $zone::getForeignKeyField() => $zone->getID(),
            $source::getForeignKeyField() => $source->getID(),
            'is_download_enabled' => 0,
        ]);
        $glpi_location = $this->createItem(GlpiLocation::class, [
            'country' => 'bar',
        ]);
        $result = $this->callPrivateMethod($instance, 'enableCarbonIntensityDownload', $glpi_location);
        $this->assertFalse($result);

        // Test when the zone matches a source and download switches to enabled
        $source = $this->createItem(Source::class);
        $zone = $this->createItem(Zone::class);
        $source_zone = $this->createItem(Source_Zone::class, [
            $zone::getForeignKeyField() => $zone->getID(),
            $source::getForeignKeyField() => $source->getID(),
            'is_download_enabled' => 0,
        ]);
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = $this->createItem(Location::class, [
            $glpi_location::getForeignKeyField() => $glpi_location->getID(),
            $source_zone::getForeignKeyField() => $source_zone->getID(),
        ]);
        $result = $this->callPrivateMethod($instance, 'enableCarbonIntensityDownload', $glpi_location);
        $this->assertTrue($result);
    }

    public function testIsCarbonIntensityDownloadEnabled()
    {
        // Test with an enmpty core location object
        $glpi_location = new GlpiLocation();
        $location = new Location();
        $result = $location->isCarbonIntensityDownloadEnabled($glpi_location);
        $this->assertFalse($result);

        // Test with a core location without additional data with the plugin object
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = new Location();
        $result = $location->isCarbonIntensityDownloadEnabled($glpi_location);
        $this->assertFalse($result);

        // Test with a core location with a country
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = new Location();
        $result = $location->isCarbonIntensityDownloadEnabled($glpi_location);
        $this->assertFalse($result);

        // Test with a core location with a country and the relation zource / zone exists, download disabled
        $source = $this->createItem(Source::class);
        $zone = $this->createItem(Zone::class);
        $source_zone = $this->createItem(Source_Zone::class, [
            'plugin_carbon_sources_id' => $source->getID(),
            'plugin_carbon_zones_id' => $zone->getID(),
            'is_download_enabled' => 0,
        ]);
        $location = new Location();
        $result = $location->isCarbonIntensityDownloadEnabled($glpi_location);
        $this->assertFalse($result);

        // Test with a core location with a relation zource / zone, download enabled
        $source = $this->createItem(Source::class);
        $zone = $this->createItem(Zone::class);
        $source_zone = $this->createItem(Source_Zone::class, [
            'plugin_carbon_sources_id' => $source->getID(),
            'plugin_carbon_zones_id' => $zone->getID(),
            'is_download_enabled' => 1,
        ]);
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = $this->createItem(Location::class, [
            'locations_id' => $glpi_location->getID(),
            $source_zone::getForeignKeyField() => $source_zone->getID(),
        ]);
        $result = $location->isCarbonIntensityDownloadEnabled($glpi_location);
        $this->assertTrue($result);
    }

    public function testHasFallbackCarbonIntensityData()
    {
        // Test with an empty core location
        $glpi_location = new GlpiLocation();
        $location = new Location();
        $result = $location->hasFallbackCarbonIntensityData($glpi_location);
        $this->assertFalse($result);

        // Test with a core location
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = new Location();
        $result = $location->hasFallbackCarbonIntensityData($glpi_location);
        $this->assertFalse($result);

        // Test with a core location with a relation to a non-fallback source
        $source =  $this->createItem(Source::class, [
            'name' => 'a source',
            'is_carbon_intensity_source' => 1,
            'fallback_level' => 0,
        ]);
        $zone =  $this->createItem(Zone::class, ['name' => 'a zone']);
        $source_zone = $this->createItem(Source_Zone::class, [
            Source::getForeignKeyField() => $source->getID(),
            Zone::getForeignKeyField() => $zone->getID(),
        ]);
        $carbon_intensity = $this->createItem(CarbonIntensity::class, [
            'date' => (new DateTime())->setTime(0, 0)->format('Y-m-d H:i:s'),
            Source::getForeignKeyField() => $source->getID(),
            Zone::getForeignKeyField() => $zone->getID(),
        ]);
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = $this->createItem(Location::class, [
            GlpiLocation::getForeignKeyField() => $glpi_location->getID(),
            Source_Zone::getForeignKeyField() => $source_zone->getID(),
        ]);
        $result = $location->hasFallbackCarbonIntensityData($glpi_location);
        $this->assertFalse($result);

        // Test with a core location with a relation to a non fallback source, that source having an other fallback source
        $source =  $this->createItem(Source::class, [
            'name' => 'a source 2',
            'is_carbon_intensity_source' => 1,
            'fallback_level' => 0,
        ]);
        $zone =  $this->createItem(Zone::class, ['name' => 'a zone 2']);
        $source_zone = $this->createItem(Source_Zone::class, [
            Source::getForeignKeyField() => $source->getID(),
            Zone::getForeignKeyField() => $zone->getID(),
        ]);
        $carbon_intensity = $this->createItem(CarbonIntensity::class, [
            'date' => (new DateTime())->setTime(0, 0)->format('Y-m-d H:i:s'),
            Source::getForeignKeyField() => $source->getID(),
            Zone::getForeignKeyField() => $zone->getID(),
        ]);
        $fallback_source = $this->createItem(Source::class, [
            'name' => 'fallback source 2',
            'is_carbon_intensity_source' => 1,
            'fallback_level' => 1,
        ]);
        $fallback_source_zone = $this->createItem(Source_Zone::class, [
            Source::getForeignKeyField() => $fallback_source->getID(),
            Zone::getForeignKeyField() => $zone->getID(),
        ]);
        $fallback_carbon_intensity = $this->createItem(CarbonIntensity::class, [
            'date' => (new DateTime())->setTime(0, 0)->format('Y-m-d H:i:s'),
            Source::getForeignKeyField() => $fallback_source->getID(),
            Zone::getForeignKeyField() =>   $zone->getID(),
        ]);
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = $this->createItem(Location::class, [
            GlpiLocation::getForeignKeyField() => $glpi_location->getID(),
            $source_zone::getForeignKeyField() => $source_zone->getID(),
        ]);
        $result = $location->hasFallbackCarbonIntensityData($glpi_location);
        $this->assertTrue($result);

        // Test a core location with a relation to a fallback source
        $source =  $this->createItem(Source::class, [
            'name' => 'a source 3',
            'is_carbon_intensity_source' => 1,
            'fallback_level' => 1,
        ]);
        $zone =  $this->createItem(Zone::class, ['name' => 'a zone 3']);
        $source_zone = $this->createItem(Source_Zone::class, [
            Source::getForeignKeyField() => $source->getID(),
            Zone::getForeignKeyField() => $zone->getID(),
        ]);
        $carbon_intensity = $this->createItem(CarbonIntensity::class, [
            'date' => (new DateTime())->setTime(0, 0)->format('Y-m-d H:i:s'),
            Source::getForeignKeyField() => $source->getID(),
            Zone::getForeignKeyField() => $zone->getID(),
        ]);
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = $this->createItem(Location::class, [
            GlpiLocation::getForeignKeyField() => $glpi_location->getID(),
            Source_Zone::getForeignKeyField() => $source_zone->getID(),
        ]);
        $result = $location->hasFallbackCarbonIntensityData($glpi_location);
        $this->assertTrue($result);

        // Test with a core location with a relation to a fallback source, that source having an other fallback source
        $source =  $this->createItem(Source::class, [
            'name' => 'a source 4',
            'is_carbon_intensity_source' => 1,
        ]);
        $zone =  $this->createItem(Zone::class, ['name' => 'a zone 4']);
        $source_zone = $this->createItem(Source_Zone::class, [
            Source::getForeignKeyField() => $source->getID(),
            Zone::getForeignKeyField() => $zone->getID(),
        ]);
        $carbon_intensity = $this->createItem(CarbonIntensity::class, [
            'date' => (new DateTime())->setTime(0, 0)->format('Y-m-d H:i:s'),
            Source::getForeignKeyField() => $source->getID(),
            Zone::getForeignKeyField() => $zone->getID(),
        ]);
        $fallback_source = $this->createItem(Source::class, [
            'name' => 'fallback source 4',
            'is_carbon_intensity_source' => 1,
            'fallback_level' => 2,
        ]);
        $fallback_source_zone = $this->createItem(Source_Zone::class, [
            Source::getForeignKeyField() => $fallback_source->getID(),
            Zone::getForeignKeyField() => $zone->getID(),
        ]);
        $fallback_carbon_intensity = $this->createItem(CarbonIntensity::class, [
            'date' => (new DateTime())->setTime(0, 0)->format('Y-m-d H:i:s'),
            Source::getForeignKeyField() => $fallback_source->getID(),
            Zone::getForeignKeyField() =>   $zone->getID(),
        ]);
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = $this->createItem(Location::class, [
            GlpiLocation::getForeignKeyField() => $glpi_location->getID(),
            $source_zone::getForeignKeyField() => $source_zone->getID(),
        ]);
        $result = $location->hasFallbackCarbonIntensityData($glpi_location);
        $this->assertTrue($result);
    }

    public function testGetSourceZoneId()
    {
        // Test an unitialized location
        $location = new Location();
        $result = $location->getSourceZoneId();
        $this->assertEquals(0, $result);

        // Test a location without a relation to a source and a zone
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = $this->createItem(Location::class, ['locations_id' => $glpi_location->getID()]);
        $result = $location->getSourceZoneId();
        $this->assertEquals(0, $result);

        // Test a location associated to a source_zone
        $source = $this->createItem(Source::class);
        $zone = $this->createItem(Zone::class);
        $source_zone = $this->createItem(Source_Zone::class, [
            Source::getForeignKeyField() => $source->getID(),
            Zone::getForeignKeyField() => $zone->getID(),
        ]);
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = $this->createItem(Location::class, [
            'locations_id' => $glpi_location->getID(),
            'plugin_carbon_sources_zones_id' => $source_zone->getID(),
        ]);
        $result = $location->getSourceZoneId();
        $this->assertEquals($source_zone->getID(), $result);

        // Test a source_zone associated to the parent of the location
        $glpi_location_ancestor = $this->createItem(GlpiLocation::class);
        $glpi_location = $this->createItem(GlpiLocation::class, [
            'locations_id' => $glpi_location_ancestor->getID(),
        ]);
        $location = $this->createItem(Location::class, [
            'locations_id' => $glpi_location_ancestor->getID(),
            'plugin_carbon_sources_zones_id' => $source_zone->getID(),
        ]);
        $result = $location->getSourceZoneId();
        $this->assertEquals($source_zone->getID(), $result);

        // Test a source_zone associated to the gand-parent of the location
        $glpi_location_grand_ancestor = $this->createItem(GlpiLocation::class);
        $glpi_location_ancestor = $this->createItem(GlpiLocation::class, [
            'locations_id' => $glpi_location_grand_ancestor->getID(),
        ]);
        $glpi_location = $this->createItem(GlpiLocation::class, [
            'locations_id' => $glpi_location_ancestor->getID(),
        ]);
        $location_grand_ancestor = $this->createItem(Location::class, [
            'locations_id' => $glpi_location_grand_ancestor->getID(),
            'plugin_carbon_sources_zones_id' => $source_zone->getID(),
        ]);
        $location = $this->createItem(Location::class, [
            'locations_id' => $glpi_location->getID(),
        ]);
        $result = $location->getSourceZoneId();
        $this->assertEquals($source_zone->getID(), $result);
    }

    public function getZoneCodeTest()
    {
        // Test an asset without a location
        $glpi_computer = $this->createItem(GlpiComputer::class);
        $result = Location::getZoneCode($glpi_computer);
        $this->assertNull($result);

        // Test an asset with a location without a plugin location extra data
        $glpi_location = $this->createItem(GlpiLocation::class);
        $glpi_computer = $this->createItem(GlpiComputer::class, [
            $glpi_location->getForeignKeyField() => $glpi_location->getID(),
        ]);
        $result = Location::getZoneCode($glpi_computer);
        $this->assertNull($result);

        // Test an asset with a location without a boavizta zone set
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = $this->createItem(Location::class, [
            $glpi_location->getForeignKeyField() => $glpi_location->getID(),
        ]);
        $glpi_computer = $this->createItem(GlpiComputer::class, [
            $glpi_location->getForeignKeyField() => $glpi_location->getID(),
        ]);
        $result = Location::getZoneCode($glpi_computer);
        $this->assertNull($result);

        // Test an asset with a location fully specified
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = $this->createItem(Location::class, [
            $glpi_location->getForeignKeyField() => $glpi_location->getID(),
            'boavizta_zone' => 'FRA',
        ]);
        $glpi_computer = $this->createItem(GlpiComputer::class, [
            $glpi_location->getForeignKeyField() => $glpi_location->getID(),
        ]);
        $result = Location::getZoneCode($glpi_computer);
        $this->assertEquals('FRA', $result);
    }
}
