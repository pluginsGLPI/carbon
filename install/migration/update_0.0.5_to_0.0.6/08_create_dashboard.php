<?php

/**
 * -------------------------------------------------------------------------
 * carbon plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2024 Teclib' and contributors.
 * @license   MIT https://opensource.org/licenses/mit-license.php
 * @link      https://github.com/pluginsGLPI/carbon
 * -------------------------------------------------------------------------
 */

use Glpi\Dashboard\Dashboard;
use Glpi\Dashboard\Item as DashboardItem;
use Glpi\Dashboard\Right as DashboardRight;
use GlpiPlugin\Carbon\Report;
use Ramsey\Uuid\Uuid;

/** @var DBmysql $DB */
global $DB;

/** @var array $args arguments passed to the command line*/

$dashboard = new Dashboard();
$dashboard_key = 'plugin_carbon_board';

if (($args['reset-report-dashboard'] ?? false)) {
    $dashboard->deleteByCriteria([
        'key' => $dashboard_key
    ]);
}

/** @phpstan-ignore argument.type */
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
    $item->getFromDBByCrit([
        'dashboards_dashboards_id' => $dashboard->fields['id'],
        'card_id' => $key,
    ]);
    if (!$item->isNewItem()) {
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
    'FROM' => Profile::getTable(),
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
    $dashboard_right->add([
        'dashboards_dashboards_id' => $dashboard->fields['id'],
        'itemtype'                 => Profile::class,
        'items_id'                 => $profile['id'],
    ]);
}
