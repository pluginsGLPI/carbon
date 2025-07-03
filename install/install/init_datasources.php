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

/** @var DBmysql $DB */
global $DB;

// Create RTE and Electricity map data sources in DB
if (!$DB->runFile(__DIR__ . '/../mysql/plugin_carbon_initial.sql')) {
    throw new \RuntimeException('Error creating data sources in DB');
}

$world_carbon_intensity = include(dirname(__DIR__) . '/data/carbon_intensity/world.php');

$dbUtil = new DbUtils();
$table = $dbUtil->getTableForItemType(GlpiPlugin\Carbon\CarbonIntensity::class);

// Those IDs are set in plugin_carbo_mysql_initial.sql
$source_id = 1;
$zone_id_world = 1;
foreach ($world_carbon_intensity as $year => $intensity) {
    $success = $DB->insert($table, [
        'date' => "$year-01-01 00:00:00",
        'plugin_carbon_carbonintensitysources_id' => $source_id,
        'plugin_carbon_zones_id' => $zone_id_world,
        'intensity' => $intensity,
        'data_quality' => 2 // constant GlpiPlugin\Carbon\DataTracking::DATA_QUALITY_ESTIMATED
    ]);
}

$source_id = 4;
$zone_id_quebec = 3;
$quebec_carbon_intensity = include(dirname(__DIR__) . '/data/carbon_intensity/quebec.php');
foreach ($quebec_carbon_intensity as $year => $intensity) {
    $success = $DB->insert($table, [
        'date' => "$year-01-01 00:00:00",
        'plugin_carbon_carbonintensitysources_id' => $source_id,
        'plugin_carbon_zones_id' => $zone_id_quebec,
        'intensity' => $intensity,
        'data_quality' => 2 // constant GlpiPlugin\Carbon\DataTracking::DATA_QUALITY_ESTIMATED
    ]);
}
