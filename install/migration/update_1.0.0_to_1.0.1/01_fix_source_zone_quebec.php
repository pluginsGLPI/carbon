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

use Glpi\DBAL\QuerySubQuery;
use GlpiPlugin\Carbon\CarbonEmission;

/** @var DBmysql $DB */
global $DB;

$db_utils = new DbUtils();

// Update and fix the bad relation
$source_table = 'glpi_plugin_carbon_carbonintensitysources';
$zone_table = 'glpi_plugin_carbon_zones';
$source_zone_table = 'glpi_plugin_carbon_carbonintensitysources_zones';
$source_iterator = $DB->request([
    'SELECT' => 'id',
    'FROM' => $source_table,
    'WHERE' => ['name' => 'Hydro Quebec']
]);
$zone_iterator = $DB->request([
    'SELECT' => 'id',
    'FROM' => $zone_table,
    'WHERE' => ['name' => 'Quebec']
]);
if ($source_iterator->count() && $zone_iterator->count()) {
    $DB->update($source_zone_table, [
        'plugin_carbon_zones_id' => $zone_iterator->current()['id'],
    ], [
        'plugin_carbon_carbonintensitysources_id' => $source_iterator->current()['id'],
    ]);
}

// delete carbon intensity results for computers in Quebec
$itemtypes = [
    Computer::class,
    Monitor::class,
    NetworkEquipment::class
];

$carbon_emission_table = 'glpi_plugin_carbon_carbonemissions';
$location_table = $db_utils->getTableForItemType(Location::class);
foreach ($itemtypes as $itemtype) {
    $item_table = $db_utils->getTableForItemType($itemtype);
    $request = [
        'SELECT' => $item_table . '.id',
        'FROM' => $item_table,
        'INNER JOIN' => [
            $location_table => [
                'FKEY' => [
                    $location_table => 'id',
                    $item_table     => 'locations_id'
                ]
            ]
        ],
        'WHERE' => [
            Location::getTableField('state') => 'Quebec',
        ]
    ];
    $subquery = new QuerySubQuery($request);
    $DB->delete(
        $carbon_emission_table,
        [
            'itemtype' => $itemtype,
            'items_id' => $subquery
        ]
    );
}
