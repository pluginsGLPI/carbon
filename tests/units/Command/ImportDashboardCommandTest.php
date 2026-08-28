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

namespace GlpiPlugin\Carbon\Dashboard\Tests;

use DBmysql;
use Glpi\Dashboard\Dashboard as GlpiDashboard;
use Glpi\Dashboard\Item;
use GlpiPlugin\Carbon\Command\ImportDashboardCommand;
use GlpiPlugin\Carbon\Dashboard\Dashboard;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

#[CoversClass(ImportDashboardCommand::class)]
class ImportDashboardCommandTest extends DbTestCase
{
    public function test_import_dashboard_command()
    {
        /**
         * @var DBmysql $DB
         */
        global $DB;

        $initial_dashboard = new GlpiDashboard(Dashboard::REPORTING_DASHBOARD_KEY);
        $initial_dashboard->load();
        $this->assertFalse($initial_dashboard->isNewItem());

        $item_table = getTableForItemType(Item::class);
        $dashboard_table = getTableForItemType(GlpiDashboard::class);
        $result = $DB->request([
            'SELECT' => [
                $item_table => 'id',
            ],
            'FROM'   => $item_table,
            'INNER JOIN' => [
                $dashboard_table => [
                    'ON' => [
                        $dashboard_table => 'id',
                        $item_table      => 'dashboards_dashboards_id',
                    ],
                ],
            ],
            'WHERE' => [
                GlpiDashboard::getTableField('key') => Dashboard::REPORTING_DASHBOARD_KEY,
            ],
        ]);

        // Traverse all items of the dashboard and arbitrary alter some of them
        foreach ($result as $row) {
            $item = Item::getById($row['id']);
            if ($item === false) {
                $this->fail("Failed to get dashboard item with id {$row['id']}");
            }
            switch ($row['id'] % 3) {
                case 0:
                    // Drop the card options
                    $item->update($item->fields + ['card_options' => '']);
                    break;
                case 1:
                    // Move the item to a different position and size, no matter there is collisions
                    $item->update($item->fields + ['x' => '128', 'y' => '128', 'width' => '1', 'height' => '1']);
                    break;
                case 2:
                    // Do nothing
                    break;
            }
        }

        // Now import the dashboard again, it should reset all items to their default state
        $command = new ImportDashboardCommand();
        $command->run(new ArrayInput([]), new NullOutput());

        // Initial dashboard has been deleted, the new one should have a different ID
        $new_dashboard = new GlpiDashboard(Dashboard::REPORTING_DASHBOARD_KEY);
        $new_dashboard->load();
        $this->assertFalse($initial_dashboard->isNewItem());
        $this->assertNotEquals($initial_dashboard->fields['id'], $new_dashboard->fields['id']);

        // Compare the items against the JSON definition of the dashboard, they should be identical
        $dashboard_json_path = __DIR__ . '/../../../install/data/report_dashboard.json';
        $dashboard_json = json_decode(file_get_contents($dashboard_json_path), true);
        $result = $DB->request([
            'SELECT' => [
                $item_table => 'id',
            ],
            'FROM'   => $item_table,
            'INNER JOIN' => [
                $dashboard_table => [
                    'ON' => [
                        $dashboard_table => 'id',
                        $item_table      => 'dashboards_dashboards_id',
                    ],
                ],
            ],
            'WHERE' => [
                GlpiDashboard::getTableField('key') => Dashboard::REPORTING_DASHBOARD_KEY,
            ],
        ]);
        foreach ($result as $row) {
            $item = Item::getById($row['id']);
            if ($item === false) {
                $this->fail("Failed to get dashboard item with id {$row['id']}");
            }
            $item_fields = array_filter($item->fields, function ($key) {
                return !in_array($key, ['id', 'dashboards_dashboards_id']);
            }, ARRAY_FILTER_USE_KEY);
            $item_fields['card_options'] = json_decode($item_fields['card_options'], true);
            $found = false;
            foreach ($dashboard_json as $json_item) {
                if ($json_item['card_id'] === $item_fields['card_id']) {
                    $found = true;
                    $this->assertEqualsCanonicalizing($json_item, $item_fields);
                    break;
                }
            }
            if (!$found) {
                $this->fail("Dashboard item with card_id {$item_fields['card_id']} not found in JSON definition");
            }
        }

        // Assert counts of items match
        $this->assertEquals(count($dashboard_json), count($result));
    }
}
