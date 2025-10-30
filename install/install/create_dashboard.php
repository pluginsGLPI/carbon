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

use Glpi\Dashboard\Dashboard;
use Glpi\Dashboard\Item as DashboardItem;
use Glpi\Dashboard\Right as DashboardRight;
use Glpi\DBAL\QueryExpression;
use GlpiPlugin\Carbon\Report;
use Ramsey\Uuid\Uuid;

/** @var DBmysql $DB */
global $DB;

$dashboard = new Dashboard();
$dashboard_key = 'plugin_carbon_board';
if ($dashboard->getFromDB($dashboard_key) === false) {
    // The dashboard already exists, nothing to create
    $dashboard->add([
        'key'     => $dashboard_key,
        'name'    => 'Environmental impact',
        'context' => 'mini_core',
    ]);
    if ($dashboard->isNewItem()) {
        // Failed to create the dashboard
        /** @var Migration $migration  */
        $migration->log('Failed to create the mini dashboard', true);
        return;
    };
}

// add cards
$cards_path = Plugin::getPhpDir('carbon') . '/install/data/report_dashboard.json';
$cards = file_get_contents($cards_path);
$cards = json_decode($cards, true);
foreach ($cards as $key => $card) {
    $item = new DashboardItem();
    $x = $card['x'];
    $y = $card['y'];
    $w = $card['width'];
    $h = $card['height'];
    $rows = $item->find([
        'dashboards_dashboards_id' => $dashboard->fields['id'],
        'card_id' => $key,
    ]);
    if (count($rows) > 0) {
        // The card already exists
        continue;
    }
    $item->addForDashboard($dashboard->fields['id'], [[
        'card_id' => $key,
        'gridstack_id' => $key . '_' . Uuid::uuid4(),
        'x'       => $x,
        'y'       => $y,
        'width'   => $w,
        'height'  => $h,
        'card_options' => $card['card_options'],
    ]
    ]);
}

// Configure rights
$profile_table = Profile::getTable();
$profile_right_table = ProfileRight::getTable();
$rights = DBmysql::quoteName(ProfileRight::getTableField('rights'));
$right_mask = READ + UPDATE;
$rights = "{$rights} AND ({$right_mask})";
$iterator = $DB->request([
    'SELECT' => [
        Profile::getTableField('id'),
    ],
    'FROM' => $profile_table,
    'INNER JOIN' => [
        $profile_right_table => [
            'FKEY' => [
                $profile_table => 'id',
                $profile_right_table => 'profiles_id',
                [
                    'AND' => [ProfileRight::getTableField('name') => [Config::$rightname, Report::$rightname]],
                ]
            ]
        ]
    ],
    'WHERE' => [
        new QueryExpression($rights),
    ]
]);

foreach ($iterator as $profile) {
    $dashboard_right = new DashboardRight();
    $input = [
        'dashboards_dashboards_id' => $dashboard->fields['id'],
        'itemtype'                 => Profile::class,
        'items_id'                 => $profile['id'],
    ];
    if (!$dashboard_right->getFromDBByCrit($input)) {
        $dashboard_right->add($input);
    }
}
