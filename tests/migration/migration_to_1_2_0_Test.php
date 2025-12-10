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

use DBmysql;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\Install;
use Location as GlpiLocation;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use GlpiPlugin\Carbon\Uninstall;
use Migration;
use PHPUnit\Framework\Attributes\CoversNothing;
use Plugin;

class migration_to_1_2_0_Test extends DbTestCase
{
    public function setUp(): void
    {
        /** @var DBmysql $DB */
        global $DB;

        parent::setUp();
        require_once(__DIR__ . '/../../install/Uninstall.php');
        require_once(__DIR__ . '/../../install/Install.php');
        $uninstall = new Uninstall();
        $uninstall->uninstall();
        $DB->clearSchemaCache();

        Plugin::load(TEST_PLUGIN_NAME);
        $sql_file = plugin_carbon_getSchemaPath('1.1.1');
        $success = $DB->runFile(realpath($sql_file));
    }

    #[CoversNothing]
    public function testUpdateCountryLocationZoneRelation()
    {
        /** @var DBMysql $DB */
        global $DB;

        $glpi_location = $this->createItem(GlpiLocation::class, [
            'country' => 'France',
        ]);
        $DB->insert('glpi_plugin_carbon_carbonintensitysources', [
            'name' => 'RTE',
            'is_fallback' => 0,
        ]);
        $source_id = $DB->insertId();
        $DB->insert('glpi_plugin_carbon_zones', [
            'name' => 'France',
            'plugin_carbon_carbonintensitysources_id_historical' => $source_id,
        ]);
        $zone_id = $DB->insertId();
        $DB->insert('glpi_plugin_carbon_carbonintensitysources_zones', [
            'plugin_carbon_carbonintensitysources_id' => $source_id,
            'plugin_carbon_zones_id'   => $zone_id,
        ]);
        $source_zone_id = $DB->insertId();

        $install = new Install(new Migration('1.2.0'));
        // $install->upgrade('1.1.1');
        $migrations = $install->getMigrationsToDo('1.1.1');
        reset($migrations);
        $file = key($migrations);
        $data = current($migrations);
        $install->upgradeOneVersion($file, $data);

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

    #[CoversNothing]
    public function testUpdateStateLocationZoneRelation()
    {
        /** @var DBMysql $DB */
        global $DB;

        $glpi_location = $this->createItem(GlpiLocation::class, [
            'state' => 'foo state',
        ]);
        $DB->insert('glpi_plugin_carbon_carbonintensitysources', [
            'name' => 'foo electricity distributor',
            'is_fallback' => 1
        ]);
        $source_id = $DB->insertId();
        $DB->insert('glpi_plugin_carbon_zones', [
            'name' => 'foo state',
            'plugin_carbon_carbonintensitysources_id_historical' => $source_id,
        ]);
        $zone_id = $DB->insertId();
        $DB->insert('glpi_plugin_carbon_carbonintensitysources_zones', [
            'plugin_carbon_carbonintensitysources_id' => $source_id,
            'plugin_carbon_zones_id'   => $zone_id,
        ]);
        $source_zone_id = $DB->insertId();

        $install = new Install(new Migration('1.2.0'));
        $migrations = $install->getMigrationsToDo('1.1.1');
        reset($migrations);
        $file = key($migrations);
        $data = current($migrations);
        $install->upgradeOneVersion($file, $data);

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

    #[CoversNothing]
    public function testTimeformatInUsageProfile()
    {
        global $DB;
        $table = getTableForItemType(ComputerUsageProfile::class);
        $input = [
            'name' => 'usage profile 1',
            'time_start' => '09:00:00',
            'time_stop'  => '17:00:00',
        ];
        $DB->insert($table, $input);
        $usage_profile_1 = $this->getItem(ComputerUsageProfile::class, $input);
        $input = [
            'name' => 'usage profile 2',
            'time_start' => '09:00',
            'time_stop'  => '17:00:00',
        ];
        $DB->insert($table, $input);
        $usage_profile_2 = $this->getItem(ComputerUsageProfile::class, $input);
        $input = [
            'name' => 'usage profile 3',
            'time_start' => '09:00:00',
            'time_stop'  => '17:00',
        ];
        $DB->insert($table, $input);
        $usage_profile_3 = $this->getItem(ComputerUsageProfile::class, $input);
        $input = [
            'name' => 'usage profile 4',
            'time_start' => '09:00',
            'time_stop'  => '17:00',
        ];
        $DB->insert($table, $input);
        $usage_profile_4 = $this->getItem(ComputerUsageProfile::class, $input);

        $install = new Install(new Migration('1.2.0'));
        $migrations = $install->getMigrationsToDo('1.1.1');
        reset($migrations);
        $file = key($migrations);
        $data = current($migrations);
        $install->upgradeOneVersion($file, $data);

        $updated_usage_profile = $this->getItem(ComputerUsageProfile::class, [
            'id' => $usage_profile_1->getID(),
        ]);
        $this->assertSame('09:00', $updated_usage_profile->fields['time_start']);
        $this->assertSame('17:00', $updated_usage_profile->fields['time_stop']);
        $updated_usage_profile = $this->getItem(ComputerUsageProfile::class, [
            'id' => $usage_profile_2->getID(),
        ]);
        $this->assertSame('09:00', $updated_usage_profile->fields['time_start']);
        $this->assertSame('17:00', $updated_usage_profile->fields['time_stop']);
        $updated_usage_profile = $this->getItem(ComputerUsageProfile::class, [
            'id' => $usage_profile_3->getID(),
        ]);
        $this->assertSame('09:00', $updated_usage_profile->fields['time_start']);
        $this->assertSame('17:00', $updated_usage_profile->fields['time_stop']);
        $updated_usage_profile = $this->getItem(ComputerUsageProfile::class, [
            'id' => $usage_profile_4->getID(),
        ]);
        $this->assertSame('09:00', $updated_usage_profile->fields['time_start']);
        $this->assertSame('17:00', $updated_usage_profile->fields['time_stop']);
    }
}
