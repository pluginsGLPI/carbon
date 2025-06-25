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
        $glpi_location->update([
            'id' => $glpi_location->getID(),
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
