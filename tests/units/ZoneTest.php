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
use GlpiPlugin\Carbon\Source;
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
        $location = $this->createItem(Location::class);
        $output = Zone::getByLocation($location);
        $this->assertNull($output);

        // Test with a Location object that has a country
        $location = $this->createItem(Location::class, [
            'country' => 'foo'
        ]);
        $output = Zone::getByLocation($location);
        $this->assertNull($output);

        // Test with a Location object that has a country and a zoone exists for this location
        $zone = $this->createItem(Zone::class, [
            'name' => 'foo'
        ]);
        $output = Zone::getByLocation($location);
        $this->assertEquals($output->getID(), $zone->getID());

        // Test with a Location object that has a state
        $location = $this->createItem(Location::class, [
            'state' => 'bar'
        ]);
        $output = Zone::getByLocation($location);
        $this->assertNull($output);

        // Test with a Location object that has a state and a zone exists for this location
        $zone = $this->createItem(Zone::class, [
            'name' => 'bar'
        ]);
        $output = Zone::getByLocation($location);
        $this->assertEquals($output->getID(), $zone->getID());

        // Test with a Location object that has both country and state
        $location = $this->createItem(Location::class, [
            'country' => 'fooo',
            'state' => 'baz'
        ]);
        $output = Zone::getByLocation($location);
        $this->assertNull($output);
        // Test with a Location object that has both country and state and a zone exists for this location
        $zone = $this->createItem(Zone::class, [
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
        $location = $this->createItem(Location::class, [
            'country' => 'foo'
        ]);
        $computer = $this->createItem(Computer::class, [
            'locations_id' => $location->getID(),
        ]);
        $output = Zone::getByAsset($computer);
        $this->assertNull($output);

        // Test with a Computer object that has a location with country and a matching zone
        $zone = $this->createItem(Zone::class, [
            'name' => 'foo'
        ]);
        $output = Zone::getByAsset($computer);
        $this->assertEquals($output->getID(), $zone->getID());

        // Test with a Computer object that has a location with state and no matching zone
        $location = $this->createItem(Location::class, [
            'state' => 'bar'
        ]);
        $computer = $this->createItem(Computer::class, [
            'locations_id' => $location->getID(),
        ]);
        $output = Zone::getByAsset($computer);
        $this->assertNull($output);

        // Test with a Computer object that has a location with state and a matching zone
        $zone = $this->createItem(Zone::class, [
            'name' => 'bar'
        ]);
        $output = Zone::getByAsset($computer);
        $this->assertEquals($output->getID(), $zone->getID());

        // Test with a Computer object that has a location with both country and state and no matching zone
        $location = $this->createItem(Location::class, [
            'country' => 'fooo',
            'state' => 'baz'
        ]);
        $computer = $this->createItem(Computer::class, [
            'locations_id' => $location->getID(),
        ]);
        $output = Zone::getByAsset($computer);
        $this->assertNull($output);

        // Test with a Computer object that has a location with both country and state and a matching zone
        $zone = $this->createItem(Zone::class, [
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

        // Test with a Zone object without the field
        $zone = $this->createItem(Zone::class);
        unset($zone->fields['plugin_carbon_sources_id_historical']);
        $this->assertFalse($zone->hasHistoricalData());

        // Test with a Zone object that has no historical data
        /** @var Zone $zone */
        $zone = $this->createItem(Zone::class);
        $this->assertFalse($zone->hasHistoricalData());

        $source = $this->createItem(Source::class, [
            'name' => 'foo'
        ]);
        $zone->update(array_merge($zone->fields, [
            'plugin_carbon_sources_id_historical' => $source->getID(),
        ]));
        $this->assertTrue($zone->hasHistoricalData());
    }

    public function testGetByItem()
    {
        // Test with a new Computer object
        $item = new Computer();
        $zone = new Zone();
        $this->assertFalse($zone->getByItem($item));

        // Test with a Computer object that has no location
        $item = $this->createItem(Computer::class);
        $zone = new Zone();
        $this->assertFalse($zone->getByItem($item));

        // Test with a Computer object that has a location with country and no matching zone
        $location = $this->createItem(Location::class, [
            'country' => 'foo'
        ]);
        $item->update(['id' => $item->getID(), 'locations_id' => $location->getID()]);
        $zone = new Zone();
        $this->assertFalse($zone->getByItem($item));

        // Test with a Computer object that has a location with country and a matching zone
        $expected_zone = $this->createItem(Zone::class, [
            'name' => 'foo'
        ]);
        $zone = new Zone();
        $this->assertTrue($zone->getByItem($item));
        $this->assertEquals($zone->getID(), $expected_zone->getID());

        // Test with a Computer object that has a location with state and no matching zone
        $location = $this->createItem(Location::class, [
            'state' => 'bar'
        ]);
        $item->update(['id' => $item->getID(), 'locations_id' => $location->getID()]);
        $zone = new Zone();
        $this->assertFalse($zone->getByItem($item));

        // Test with a Computer object that has a location with state and a matching zone
        $expected_zone = $this->createItem(Zone::class, [
            'name' => 'bar'
        ]);
        $zone = new Zone();
        $this->assertTrue($zone->getByItem($item));
        $this->assertEquals($zone->getID(), $expected_zone->getID());

        // Test with a Computer object that has a location with both country and state and no matching zone
        $location = $this->createItem(Location::class, [
            'country' => 'fooo',
            'state' => 'baz'
        ]);
        $item->update(['id' => $item->getID(), 'locations_id' => $location->getID()]);
        $zone = new Zone();
        $this->assertFalse($zone->getByItem($item));

        // Test with a Computer object that has a location with both country and state and a matching zone for state
        $expected_zone = $this->createItem(Zone::class, [
            'name' => 'baz'
        ]);
        $zone = new Zone();
        $this->assertTrue($zone->getByItem($item));
        $this->assertEquals($zone->getID(), $expected_zone->getID());

        // Test with a Computer object that has a location with both country and state and a matching zone for state and country
        $location = $this->createItem(Location::class, [
            'country' => 'foo',
            'state' => 'baz'
        ]);
        $item->update(['id' => $item->getID(), 'locations_id' => $location->getID()]);
        $zone = new Zone();
        $this->assertTrue($zone->getByItem($item));
        $this->assertEquals($zone->getID(), $expected_zone->getID());
        $expected_zone = new Zone();
        $expected_zone->getFromDBByCrit(['name' => 'foo']); // Reuse zone created earlier
        $zone = new Zone();
        $this->assertTrue($zone->getByItem($item, null, true));
        $this->assertEquals($zone->getID(), $expected_zone->getID());
    }
}
