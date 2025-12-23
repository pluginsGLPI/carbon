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
use GlpiPlugin\Carbon\Tests\DbTestCase;
use GlpiPlugin\Carbon\Zone;
use Location as GlpiLocation;
use PHPUnit\Framework\Attributes\CoversClass;
use wapmorgan\UnifiedArchive\Drivers\Zip;

#[CoversClass(Source_Zone::class)]
class Source_ZoneTest extends DbTestCase
{
    /**
     * #CoversMethod GlpiPlugin\Carbon\Source_Zone::showForSource
     */
    public function testShowForSource()
    {
        $source = $this->createItem(Source::class, [
            'name' => 'foo'
        ]);

        $zone = $this->createItem(Zone::class, [
            'name' => 'bar'
        ]);

        $instance = $this->createItem(Source_Zone::class, [
            $source::getForeignKeyField() => $source->getID(),
            $zone::getForeignKeyField() => $zone->getID(),
        ]);

        $this->logout();
        ob_start();
        $result = $instance->showForSource($source);
        $output = ob_get_clean();
        $this->assertEquals('', $output);

        $this->login('glpi', 'glpi');
        ob_start();
        $result = $instance->showForSource($source);
        $output = ob_get_clean();
        $this->assertNotEmpty($output);
    }

    /**
     * #CoversMethod GlpiPlugin\Carbon\Source_Zone::showForZone
     */
    public function testShowForZone()
    {
        $source = $this->createItem(Source::class, [
            'name' => 'foo'
        ]);

        $zone = $this->createItem(Zone::class, [
            'name' => 'bar'
        ]);

        $instance = $this->createItem(Source_Zone::class, [
            $source::getForeignKeyField() => $source->getID(),
            $zone::getForeignKeyField() => $zone->getID(),
        ]);

        $this->logout();
        ob_start();
        $result = $instance->showForZone($zone);
        $output = ob_get_clean();
        $this->assertEquals('', $output);

        $this->login('glpi', 'glpi');
        ob_start();
        $result = $instance->showForZone($zone);
        $output = ob_get_clean();
        $this->assertNotEmpty($output);
    }

    public function testFromDbByItem()
    {
        // Test a location having a source_zone relation
        $zone = $this->createItem(Zone::class);
        $source = $this->createItem(Source::class);
        $source_zone = $this->createItem(Source_Zone::class, [
            $zone::getForeignKeyField() => $zone->getID(),
            $source::getForeignKeyField() => $source->getID(),
        ]);
        $glpi_location = $this->createItem(GlpiLocation::class);
        $this->createItem(Location::class, [
            'locations_id' => $glpi_location->getID(),
            'plugin_carbon_sources_zones_id' => $source_zone->getID(),
        ]);
        $source_zone = new Source_Zone();
        $output = $source_zone->getFromDbByItem($glpi_location);
        $this->assertTrue($output);

        // Test a location without source_zone
        $glpi_location = $this->createItem(GlpiLocation::class);
        $this->createItem(Location::class, [
            'locations_id' => $glpi_location->getID(),
        ]);
        $source_zone = new Source_Zone();
        $output = $source_zone->getFromDbByItem($glpi_location);
        $this->assertFalse($output);

        // Test a location without additional plugin data
        $glpi_location = $this->createItem(GlpiLocation::class);
        $source_zone = new Source_Zone();
        $output = $source_zone->getFromDbByItem($glpi_location);
        $this->assertFalse($output);

        // Test an unitialized asset
        $computer = new Computer();
        $source_zone = new Source_Zone();
        $output = $source_zone->getFromDbByItem($glpi_location);
        $this->assertFalse($output);

        // Test an asset
        $computer = $this->createItem(Computer::class);
        $source_zone = new Source_Zone();
        $output = $source_zone->getFromDbByItem($glpi_location);
        $this->assertFalse($output);

        // Test an asset with a location
        $glpi_location = $this->createItem(GlpiLocation::class);
        $computer = $this->createItem(Computer::class, [
            'locations_id' => $glpi_location->getID(),
        ]);
        $source_zone = new Source_Zone();
        $output = $source_zone->getFromDbByItem($glpi_location);
        $this->assertFalse($output);

        // Test an asset with plugin location data
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = $this->createItem(Location::class, [
            'locations_id' => $glpi_location->getID(),
        ]);
        $computer = $this->createItem(Computer::class, [
            'locations_id' => $glpi_location->getID(),
        ]);
        $source_zone = new Source_Zone();
        $output = $source_zone->getFromDbByItem($glpi_location);
        $this->assertFalse($output);

        // Test an asset with plugin location data linked to a source_zone
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
        $source_zone = new Source_Zone();
        $output = $source_zone->getFromDbByItem($glpi_location);
        $this->assertTrue($output);
    }

    public function testGetFallbackFromDB()
    {
        // Test finding a fallback source zone from an 'non fallback' source_zone
        $source_boaviztapi = $this->createItem(Source::class, [
            'name' => 'Boaviztapi',
            'fallback_level' => 0,
            'is_carbon_intensity_source' => 0,
        ]);
        $zone_france = $this->getItem(Zone::class, [
            'WHERE' => ['name' => 'France']
        ]);
        $source_zone_boaviztapi_france = $this->createItem(Source_Zone::class, [
            getForeignKeyFieldForItemType(Source::class) => $source_boaviztapi->getID(),
            getForeignKeyFieldForItemType(Zone::class)   => $zone_france->getID(),
        ]);
        $instance = new Source_Zone();
        $expected_source = $this->getItem(Source::class, [
            'WHERE' => [
                'name' => 'Ember - Energy Institute'
            ]
        ]);
        $expected_zone = $this->getItem(Zone::class, [
            'WHERE' => [
                'name' => 'France'
            ]
        ]);
        $success = $instance->getFallbackFromDB($source_zone_boaviztapi_france);
        $this->assertTrue($success);
        $this->assertEquals($expected_source->getID(), $instance->fields[Source::getForeignKeyField()]);
        $this->assertEquals($expected_zone->getID(), $instance->fields[Zone::getForeignKeyField()]);

        // Test finding a fallback source zone when several levels of fallbacks are available
        // RTE is level 0
        // Ember is level 2
        // Alternate source is level 3
        // * Reuses the previous objects *
        $alternate_fallback_source = $this->createItem(Source::class, [
            'name' => 'alternate source',
            'fallback_level' => 3,
        ]);
        $alternate_source_zone = $this->createItem(Source_Zone::class, [
            getForeignKeyFieldForItemType(Source::class) => $alternate_fallback_source->getID(),
            getForeignKeyFieldForItemType(Zone::class)   => $zone_france->getID(),
        ]);
        $input = $this->getItem(Source_Zone::class, [
            'INNER JOIN' => [
                Source::getTable() => [
                    'ON' => [
                        Source::getTable() => 'id',
                        Source_Zone::getTable() => Source::getForeignKeyField()
                    ]
                ]
            ],
            'WHERE' => [
                getForeignKeyFieldForItemType(Zone::class)   => $zone_france->getID(),
                Source::getTableField('name') => 'Ember - Energy Institute',
            ]
        ]);
        $instance = new Source_Zone();
        $success = $instance->getFallbackFromDB($input);
        $this->assertTrue($success);
        $this->assertEquals($alternate_source_zone->getID(), $instance->getID());
        $this->assertEquals($input->fields['plugin_carbon_zones_id'], $instance->fields['plugin_carbon_zones_id']);

        // Test failing to a fallback source zone when it does not exists
        $source = $this->createItem(Source::class);
        $zone = $this->createItem(Zone::class);
        $source_zone = $this->createItem(Source_Zone::class, [
            $source::getForeignKeyField() => $source->getID(),
            $zone::getForeignKeyField() => $zone->getID(),
        ]);
        $instance = new Source_Zone();
        $success = $instance->getFallbackFromDB($source_zone);
        $this->assertFalse($success);
    }

    public function testGetOrCreate()
    {
        // Test we can create a non existing item
        $instance = new Source_Zone();
        $source_fk = getForeignKeyFieldForItemType(Source::class);
        $zone_fk = getForeignKeyFieldForItemType(Zone::class);
        $source = $this->createItem(Source::class);
        $zone = $this->createItem(Zone::class);
        $where = [
            $source_fk => $source->getID(),
            $zone_fk => $zone->getID(),
        ];
        $this->count(0, $instance->find($where));
        $instance->getOrCreate([], $where);
        $this->count(1, $instance->find($where));

        // Test we find an existing instance
        $instance_2 = new Source_Zone();
        $instance_2->getOrCreate([], $where);
        $this->assertSame($instance->getID(), $instance_2->getID());

        // Test we can update an existing item
        $instance_3 = new Source_Zone();
        $instance_3->getOrCreate(['code' => 'FOO'], $where);
        $this->assertEquals('FOO', $instance_3->fields['code']);
    }
}
