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

use Computer;
use GlpiPlugin\Carbon\Zone;
use Location;

class ZoneTest extends DbTestCase
{
    public function testGetByLocation()
    {
        // Test with a new Location object
        $output = Zone::getByLocation(new Location());
        $this->assertNull($output);

        // Test with a Location object that has no country or state
        $location = $this->getItem(Location::class);
        $output = Zone::getByLocation($location);
        $this->assertNull($output);

        // Test with a Location object that has a country
        $location = $this->getItem(Location::class, [
            'country' => 'foo'
        ]);
        $output = Zone::getByLocation($location);
        $this->assertNull($output);

        // Test with a Location object that has a country and a zoone exists for this location
        $zone = $this->getItem(Zone::class, [
            'name' => 'foo'
        ]);
        $output = Zone::getByLocation($location);
        $this->assertEquals($output->getID(), $zone->getID());

        // Test with a Location object that has a state
        $location = $this->getItem(Location::class, [
            'state' => 'bar'
        ]);
        $output = Zone::getByLocation($location);
        $this->assertNull($output);

        // Test with a Location object that has a state and a zone exists for this location
        $zone = $this->getItem(Zone::class, [
            'name' => 'bar'
        ]);
        $output = Zone::getByLocation($location);
        $this->assertEquals($output->getID(), $zone->getID());

        // Test with a Location object that has both country and state
        $location = $this->getItem(Location::class, [
            'country' => 'fooo',
            'state' => 'baz'
        ]);
        $output = Zone::getByLocation($location);
        $this->assertNull($output);
        // Test with a Location object that has both country and state and a zone exists for this location
        $zone = $this->getItem(Zone::class, [
            'name' => 'baz'
        ]);
        $output = Zone::getByLocation($location);
        $this->assertEquals($output->getID(), $zone->getID());
    }

    public function testGetByAsset()
    {
        // Test with a new Computer object
        $output = Zone::getByAsset(new Computer());
        $this->assertNull($output);

        // Test with a Computer object that has a location with country and no matching zone
        $location = $this->getItem(Location::class, [
            'country' => 'foo'
        ]);
        $computer = $this->getItem(Computer::class, [
            'locations_id' => $location->getID(),
        ]);
        $output = Zone::getByAsset($computer);
        $this->assertNull($output);

        // Test with a Computer object that has a location with country and a matching zone
        $zone = $this->getItem(Zone::class, [
            'name' => 'foo'
        ]);
        $output = Zone::getByAsset($computer);
        $this->assertEquals($output->getID(), $zone->getID());

        // Test with a Computer object that has a location with state and no matching zone
        $location = $this->getItem(Location::class, [
            'state' => 'bar'
        ]);
        $computer = $this->getItem(Computer::class, [
            'locations_id' => $location->getID(),
        ]);
        $output = Zone::getByAsset($computer);
        $this->assertNull($output);

        // Test with a Computer object that has a location with state and a matching zone
        $zone = $this->getItem(Zone::class, [
            'name' => 'bar'
        ]);
        $output = Zone::getByAsset($computer);
        $this->assertEquals($output->getID(), $zone->getID());

        // Test with a Computer object that has a location with both country and state and no matching zone
        $location = $this->getItem(Location::class, [
            'country' => 'fooo',
            'state' => 'baz'
        ]);
        $computer = $this->getItem(Computer::class, [
            'locations_id' => $location->getID(),
        ]);
        $output = Zone::getByAsset($computer);
        $this->assertNull($output);

        // Test with a Computer object that has a location with both country and state and a matching zone
        $zone = $this->getItem(Zone::class, [
            'name' => 'baz'
        ]);
        $output = Zone::getByAsset($computer);
        $this->assertEquals($output->getID(), $zone->getID());
    }
}
