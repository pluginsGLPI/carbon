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
use GlpiPlugin\Carbon\Location;
use GlpiPlugin\Carbon\Source;
use GlpiPlugin\Carbon\Source_Zone;
use GlpiPlugin\Carbon\Zone;
use Location as GlpiLocation;

class ZoneTest extends DbTestCase
{
    public function testGetByLocation()
    {
        // Test with a new Location object
        $output = Zone::getByLocation(new GlpiLocation());
        $this->assertNull($output);

        // Test with a core location without plugin location child item
        $location = $this->createItem(GlpiLocation::class);
        $output = Zone::getByLocation($location);
        $this->assertNull($output);

        // Test with a core location which has a location child without source_zone
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = $this->createItem(Location::class, [
            'locations_id' => $location->getID()
        ]);
        $output = Zone::getByLocation($glpi_location);
        $this->assertNull($output);

        // Test with a location which is associated to a source_zone
        $zone = $this->createItem(Zone::class);
        $source = $this->createItem(Source::class);
        $source_zone = $this->createItem(Source_Zone::class, [
            $zone::getForeignKeyField() => $zone->getID(),
            $source::getForeignKeyField() => $source->getID(),
        ]);
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = $this->createItem(Location::class, [
            'locations_id' => $location->getID(),
            'plugin_carbon_sources_zones_id' => $source_zone->getID(),
        ]);
    }

    public function testGetByAsset()
    {
        // Test with a new Computer object
        $output = Zone::getByAsset(new Computer());
        $this->assertNull($output);

        // Test with a Computer object that has a location without child location data
        $glpi_location = $this->createItem(GlpiLocation::class);
        $computer = $this->createItem(Computer::class, [
            'locations_id' => $glpi_location->getID(),
        ]);
        $output = Zone::getByAsset($computer);
        $this->assertNull($output);

        // Test with a Computer object that has a location with child location data
        $zone = $this->createItem(Zone::class);
        $source = $this->createItem(Source::class);
        $source_zone = $this->createItem(Source_Zone::class, [
            $zone::getForeignKeyField() => $zone->getID(),
            $source::getForeignKeyField() => $source->getID(),
        ]);
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = $this->createItem(Location::class, [
            'locations_id' => $glpi_location->getID(),
        ]);
        $computer = $this->createItem(Computer::class, [
            'locations_id' => $glpi_location->getID(),
        ]);
        $output = Zone::getByAsset($computer);
        $this->assertEquals(null, $output);

        // Test with a Computer object that has a location matching a source_zone
        $zone = $this->createItem(Zone::class);
        $source = $this->createItem(Source::class);
        $source_zone = $this->createItem(Source_Zone::class, [
            $zone::getForeignKeyField() => $zone->getID(),
            $source::getForeignKeyField() => $source->getID(),
        ]);
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = $this->createItem(Location::class, [
            'locations_id' => $glpi_location->getID(),
            'plugin_carbon_sources_zones_id' => $source_zone->getID(),
        ]);
        $computer = $this->createItem(Computer::class, [
            'locations_id' => $glpi_location->getID(),
        ]);
        $output = Zone::getByAsset($computer);
        $this->assertEquals($zone->getID(), $output->getID());
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
        $glpi_location = $this->createItem(GlpiLocation::class, [
            'country' => 'foo'
        ]);
        $item->update(['id' => $item->getID(), 'locations_id' => $glpi_location->getID()]);
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
        $glpi_location = $this->createItem(GlpiLocation::class, [
            'state' => 'bar'
        ]);
        $item->update(['id' => $item->getID(), 'locations_id' => $glpi_location->getID()]);
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
        $glpi_location = $this->createItem(GlpiLocation::class, [
            'country' => 'fooo',
            'state' => 'baz'
        ]);
        $item->update(['id' => $item->getID(), 'locations_id' => $glpi_location->getID()]);
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
        $glpi_location = $this->createItem(GlpiLocation::class, [
            'country' => 'foo',
            'state' => 'baz'
        ]);
        $item->update(['id' => $item->getID(), 'locations_id' => $glpi_location->getID()]);
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
