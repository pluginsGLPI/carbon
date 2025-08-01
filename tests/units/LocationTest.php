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

use Geocoder\Collection;
use Geocoder\Geocoder;
use Geocoder\Model\AddressCollection;
use Geocoder\Model\AdminLevel;
use Geocoder\Model\AdminLevelCollection;
use Geocoder\Model\Country;
use Geocoder\Provider\Nominatim\Model\NominatimAddress;
use GlpiPlugin\Carbon\Zone;
use Location as GlpiLocation;
use GlpiPlugin\Carbon\Location;

class LocationTest extends DbTestCase
{
    public function testOnGlpiLocationAdd()
    {
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
    }

    public function testOnGlpiLocationPreUpdate()
    {
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
    }

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

    public function testGetcountryCode()
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
        $output = Location::getCountryCode(
            $glpi_location,
            $geocoder
        );
        $this->assertEquals('FRA', $output);
    }
}
