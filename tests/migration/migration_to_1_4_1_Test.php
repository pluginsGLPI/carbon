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
use GlpiPlugin\Carbon\Install;
use Migration;

use function Safe\json_decode;
use function Safe\json_encode;

class migration_to_1_4_1_Test extends CommonTestCase
{
    public function testUpdateHandledRatioCard()
    {
        /** @var DBmysql $DB */
        global $DB;

        require_once(__DIR__ . '/../../install/Install.php');

        $DB->insert('glpi_dashboards_dashboards', [
            'key'  => 'plugin_carbon_migration_test',
            'name' => 'plugin_carbon_migration_test',
        ]);
        $dashboard_id = $DB->insertId();
        $cards = [
            'radar_card' => [
                'card_id' => 'plugin_carbon_assets_completeness_ratio',
                'widgettype' => 'apex_radar',
            ],
            'number_card' => [
                'card_id' => 'plugin_carbon_assets_completeness_ratio',
                'widgettype' => 'multipleNumber',
            ],
            'other_card' => [
                'card_id' => 'plugin_carbon_assets_completeness',
                'widgettype' => 'apex_radar',
            ],
        ];
        $ids = [];
        foreach ($cards as $key => $card) {
            $DB->insert('glpi_dashboards_items', [
                'dashboards_dashboards_id' => $dashboard_id,
                'gridstack_id' => $card['card_id'] . '_' . $key,
                'card_id'      => $card['card_id'],
                'card_options' => json_encode(['color' => '#FAFAFA', 'widgettype' => $card['widgettype']]),
            ]);
            $ids[$key] = $DB->insertId();
        }

        $install = new Install(new Migration('1.4.1'));
        $migrations = $install->getMigrationsToDo('1.4.0');
        $install->upgradeOneVersion(key($migrations), current($migrations));

        $expected = [
            'radar_card'  => 'radar',
            'number_card' => 'multipleNumber',
            'other_card'  => 'apex_radar',
        ];
        foreach ($expected as $key => $widgettype) {
            $row = $DB->request([
                'FROM'  => 'glpi_dashboards_items',
                'WHERE' => ['id' => $ids[$key]],
            ])->current();
            $card_options = json_decode($row['card_options'], true);
            $this->assertSame($widgettype, $card_options['widgettype']);
            $this->assertSame('#FAFAFA', $card_options['color']);
        }
    }
}
