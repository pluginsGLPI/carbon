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

use Computer;
use GlpiPlugin\Carbon\CarbonIntensitySource;
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

    public function testHasHistoricalData()
    {
        // Test with an empty Zone object
        $zone = new Zone();
        $this->assertFalse($zone->hasHistoricalData());

        // Test with a Zone object that has no historical data
        /** @var Zone $zone */
        $zone = $this->getItem(Zone::class);
        $this->assertFalse($zone->hasHistoricalData());

        $source = $this->getItem(CarbonIntensitySource::class, [
            'name' => 'foo'
        ]);
        $zone->update(array_merge($zone->fields, [
            'plugin_carbon_carbonintensitysources_id_historical' => $source->getID(),
        ]));
        $this->assertTrue($zone->hasHistoricalData());
    }
}
