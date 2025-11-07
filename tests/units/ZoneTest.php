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
    public function testGetByAsset()
    {
        // Test with a new Computer object
        $zone = new Zone();
        $output = $zone->getByAsset(new Computer());
        $this->assertFalse($output);

        // Test with a Computer object that has a location without child location data
        $glpi_location = $this->createItem(GlpiLocation::class);
        $computer = $this->createItem(Computer::class, [
            'locations_id' => $glpi_location->getID(),
        ]);
        $zone = new Zone();
        $output = $zone->getByAsset($computer);
        $this->assertFalse($output);

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
        $zone = new Zone();
        $output = $zone->getByAsset($computer);
        $this->assertFalse($output);

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
        $zone = new Zone();
        $output = $zone->getByAsset($computer);
        $this->assertTrue($output);
    }

    public function testHasHistoricalDataSource()
    {
        // Test with an empty Zone object
        $zone = new Zone();
        $this->assertFalse($zone->hasHistoricalDataSource());

        // Test with a Zone object without any relation with a source
        $zone = $this->createItem(Zone::class);
        $this->assertFalse($zone->hasHistoricalDataSource());

        // Test with a zone linked to a source
        $source = $this->createItem(Source::class, [
            'name' => 'foo',
            'is_carbon_intensity_source' => 1,
            'fallback_level' => 0,
        ]);
        $zone = $this->createItem(Zone::class, [
            'name' => 'a zone'
        ]);
        $source_zone = $this->createItem(Source_Zone::class, [
            $source::getForeignKeyField() => $source->getID(),
            $zone::getForeignKeyField() => $zone->getID(),
        ]);
        $this->assertTrue($zone->hasHistoricalDataSource());
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
