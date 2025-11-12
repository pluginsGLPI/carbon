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

use Glpi\DBAL\QueryExpression;

/** @var DBmysql $DB */
global $DB;

// Migrate relations based on a country
$glpi_location_table = 'glpi_locations';
$zone_table = 'glpi_plugin_carbon_zones';
$source_zone_table = 'glpi_plugin_carbon_sources_zones';
$iterator = $DB->request([
    'SELECT' => [
        $glpi_location_table . '.id as locations_id',
        $zone_table . '.plugin_carbon_carbonintensitysources_id_historical',
        $zone_table . '.id as plugin_carbon_zones_id',
        $source_zone_table . '.id as plugin_carbon_sources_zones_id',
    ],
    'FROM' => $glpi_location_table,
    'INNER JOIN' => [
        $zone_table => [
            'FKEY' => [
                $zone_table => 'name',
                $glpi_location_table => 'country',
            ]
        ],
        $source_zone_table => [
            'FKEY' => [
                $source_zone_table => 'plugin_carbon_zones_id',
                $zone_table => 'id',
                [
                    'AND' => [
                        new QueryExpression('`' . $source_zone_table . '`.`plugin_carbon_sources_id` = `' . $zone_table . '`.`plugin_carbon_carbonintensitysources_id_historical`'),
                    ]
                ]
            ]
        ]
    ]
]);

$location_table = 'glpi_plugin_carbon_locations';
/** @var Migration $migration */
$migration->migrationOneTable($location_table);
foreach ($iterator as $row) {
    $where = [
        'locations_id' => $row['locations_id'],
    ];
    $params = [
        'plugin_carbon_sources_zones_id' => $row['plugin_carbon_sources_zones_id'],
    ];
    $DB->updateOrInsert($location_table, $params, $where);
}

// Migrate relations based on a state
$glpi_location_table = 'glpi_locations';
$zone_table = 'glpi_plugin_carbon_zones';
$source_zone_table = 'glpi_plugin_carbon_sources_zones';
$iterator = $DB->request([
    'SELECT' => [
        $glpi_location_table . '.id as locations_id',
        $zone_table . '.plugin_carbon_carbonintensitysources_id_historical',
        $zone_table . '.id as plugin_carbon_zones_id',
        $source_zone_table . '.id as plugin_carbon_sources_zones_id',
    ],
    'FROM' => $glpi_location_table,
    'INNER JOIN' => [
        $zone_table => [
            'FKEY' => [
                $zone_table => 'name',
                $glpi_location_table => 'state',
            ]
        ],
        $source_zone_table => [
            'FKEY' => [
                $source_zone_table => 'plugin_carbon_zones_id',
                $zone_table => 'id',
                [
                    'AND' => [
                        new QueryExpression('`' . $source_zone_table . '`.`plugin_carbon_sources_id` = `' . $zone_table . '`.`plugin_carbon_carbonintensitysources_id_historical`'),
                    ]
                ]
            ]
        ]
    ]
]);

$location_table = 'glpi_plugin_carbon_locations';
foreach ($iterator as $row) {
    $where = [
        'locations_id' => $row['locations_id'],
    ];
    $params = [
        'plugin_carbon_sources_zones_id' => $row['plugin_carbon_sources_zones_id'],
    ];
    $DB->updateOrInsert($location_table, $params, $where);
}

$table = 'glpi_plugin_carbon_zones';
/** @var Migration $migration */
$migration->dropField($table, 'plugin_carbon_carbonintensitysources_id_historical');
