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

use DBmysql;
use Location as GlpiLocation;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use GlpiPlugin\Carbon\Uninstall;

class migration_to_1_2_0_Test extends DbTestCase
{
    public static function setUpBeforeClass(): void
    {
        global $DB;

        parent::setUpBeforeClass();
        $plugin = new Plugin();
        $plugin->getFromDBbyDir('carbon');
        if ($plugin->fields['state'] !== Plugin::ANEW && $plugin->fields['state'] !== Plugin::NOTINSTALLED) {
            require_once(__DIR__ . '/../../install/Uninstall.php');
            $uninstall = new Uninstall();
            $uninstall->uninstall();
        }

        require_once(__DIR__ . '/../../setup.php');
        $sql_file = plugin_carbon_getSchemaPath('1.2.0');
        $success = $DB->runFile(realpath($sql_file));
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        require_once(__DIR__ . '/../../install/Uninstall.php');
        $uninstall = new Uninstall();
        $uninstall->uninstall();
    }

    public function testUpdateCountryLocationZoneRelation()
    {
        /** @var DBMysql $DB */
        global $DB;
        return;

        $glpi_location = $this->createItem(GlpiLocation::class, [
            'country' => 'France',
        ]);
        $DB->insert('glpi_plugin_carbon_zones', [
            'name' => 'France',
        ]);
        $zone_id = $DB->insertId();
        $DB->insert('glpi_plugin_carbon_sources', [
            'name' => 'RTE',
            'fallback_level' => 0,
            'is_carbon_intensity_source' => 1,
        ]);
        $source_id = $DB->insertId();
        $DB->insert('glpi_plugin_carbon_sources_zones', [
            'plugin_carbon_sources_id' => $source_id,
            'plugin_carbon_zones_id'   => $zone_id,
        ]);
        $source_zone_id = $DB->insertId();
        $migration_file = __DIR__ . '/../../install/migration/update_1.1.0_to_1.2.0/04_update_location_zone_relation.php';
        $migration_file = realpath($migration_file);
        require($migration_file);

        $result = $DB->request([
            'SELECT' => '*',
            'FROM' => 'glpi_plugin_carbon_locations',
            'WHERE' => [
                'locations_id' => $glpi_location->getID(),
            ],
        ]);
        $this->assertEquals(1, $result->count());
        $expected = [
            'id' => $result->current()['id'],
            'locations_id' => $glpi_location->getID(),
            'boavizta_zone' => null,
            'plugin_carbon_sources_zones_id' => $source_zone_id,
        ];
        $this->assertEquals($expected, $result->current());
    }

    public function testUpdateStateLocationZoneRelation()
    {
        /** @var DBMysql $DB */
        global $DB;

        $glpi_location = $this->createItem(GlpiLocation::class, [
            'state' => 'foo state',
        ]);
        $DB->insert('glpi_plugin_carbon_zones', [
            'name' => 'foo state',
        ]);
        $zone_id = $DB->insertId();
        $DB->insert('glpi_plugin_carbon_sources', [
            'name' => 'foo electricity distributor',
            'fallback_level' => 1,
            'is_carbon_intensity_source' => 1,
        ]);
        $source_id = $DB->insertId();
        $DB->insert('glpi_plugin_carbon_sources_zones', [
            'plugin_carbon_sources_id' => $source_id,
            'plugin_carbon_zones_id'   => $zone_id,
        ]);
        $source_zone_id = $DB->insertId();
        $migration_file = __DIR__ . '/../../install/migration/update_1.1.1_to_1.2.0/04_update_location_zone_relation.php';
        $migration_file = realpath($migration_file);
        require($migration_file);

        $result = $DB->request([
            'SELECT' => '*',
            'FROM' => 'glpi_plugin_carbon_locations',
            'WHERE' => [
                'locations_id' => $glpi_location->getID(),
            ],
        ]);
        $this->assertEquals(1, $result->count());
        $expected = [
            'id' => $result->current()['id'],
            'locations_id' => $glpi_location->getID(),
            'boavizta_zone' => null,
            'plugin_carbon_sources_zones_id' => $source_zone_id,
        ];
        $this->assertEquals($expected, $result->current());
    }
}
