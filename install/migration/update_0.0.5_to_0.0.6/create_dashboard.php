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

$dashboard = new Dashboard();
$dashboard_key = 'plugin_carbon_board';
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
$cards = [
    'plugin_carbon_report_totalcarbonemission_ytd' => [
        'x'            => 0,
        'y'            => 0,
        'width'        => 6,
        'height'       => 3,
        'color'        => '#BBDA50',
        'widgettype'   => 'totalcarbonemission_ytd',
        'use_gradient' => '0',
        'limit'        => '7',
        'point_labels' => '0',
    ],
    'plugin_carbon_report_totalcarbonemission_two_last_months' => [
        'x'            => 6,
        'y'            => 0,
        'width'        => 6,
        'height'       => 3,
        'color'        => '#145161',
        'widgettype'   => 'totalcarbonemission_two_last_months',
        'use_gradient' => '0',
        'limit'        => '7',
        'point_labels' => '0',
    ],
    'plugin_carbon_report_unhandled_computers_ratio' => [
        'x'            => 12,
        'y'            => 0,
        'width'        => 6,
        'height'       => 3,
        'color'        => '#f3f6f4',
        'widgettype'   => 'unhandledComputersRatio',
        'use_gradient' => '0',
        'limit'        => '7',
        'point_labels' => '0',
    ],
    'plugin_carbon_report_information_video'   => [
        'x'            => 18,
        'y'            => 0,
        'width'        => 6,
        'height'       => 3,
        'color'        => '#f3f6f4',
        'widgettype'   => 'information_video',
        'use_gradient' => '0',
        'limit'        => '7',
        'point_labels' => '0',
    ],
    'plugin_carbon_report_methodology_information'   => [
        'x'            => 12,
        'y'            => 3,
        'width'        => 12,
        'height'       => 6,
        'color'        => '#f3f6f4',
        'widgettype'   => 'methodology_information',
        'use_gradient' => '0',
        'limit'        => '7',
        'point_labels' => '0',
    ],
    'plugin_carbon_biggest_gwp_per_model'   => [
        'x'            => 0,
        'y'            => 3,
        'width'        => 12,
        'height'       => 6,
        'color'        => '#f3f6f4',
        'widgettype'   => 'most_gwp_impacting_computer_models',
        'use_gradient' => '0',
        'limit'        => '7',
        'point_labels' => '0',
    ],
    'plugin_carbon_report_usage_carbon_emissions_graph'   => [
        'x'            => 0,
        'y'            => 9,
        'width'        => 24,
        'height'       => 7,
        'color'        => '#f3f6f4',
        'widgettype'   => 'usage_gwp_monthly',
        'use_gradient' => '0',
        'limit'        => '7',
        'point_labels' => '0',
    ],
];
foreach ($cards as $key => $options) {
    $item = new DashboardItem();
    $x = $options['x'];
    $y = $options['y'];
    $w = $options['width'];
    $h = $options['height'];
    unset($options['x'], $options['y'], $options['width'], $options['height']);
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
        'card_options' => $options,
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
