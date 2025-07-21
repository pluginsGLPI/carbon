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

use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\Zone;
use GlpiPlugin\Carbon\CarbonIntensity;

/** @var DBmysql $DB */

$dbUtil = new DbUtils();
$source_table = $dbUtil->getTableForItemType(CarbonIntensitySource::class);
$zone_table = $dbUtil->getTableForItemType(Zone::class);
$carbon_intensity_table = $dbUtil->getTableForItemType(CarbonIntensity::class);

$add_source = function ($name, $is_fallback = 0) use ($DB): int {
    $db_utils = new DbUtils();
    $source_table = $db_utils->getTableForItemType(CarbonIntensitySource::class);
    $result = $DB->request([
        'SELECT' => 'id',
        'FROM'   => $source_table,
        'WHERE'  => [
            'name' => $name,
        ],
    ]);
    if ($result->numrows() > 0) {
        // Source already exists, no need to insert
        return $result->current()['id'];
    }
    $result = $DB->insert($source_table, [
        'name'       => $name,
        'is_fallback' => $is_fallback,
    ]);
    if (!$result) {
        throw new RuntimeException("Failed to insert new carbon intensity source: $name");
    }
    $source_id = $DB->insertId();
    return $source_id;
};

$add_zone = function ($name, $source_historical = 0) use ($DB): int {
    $db_utils = new DbUtils();
    $source_table = $db_utils->getTableForItemType(Zone::class);
    $result = $DB->request([
        'SELECT' => 'id',
        'FROM'   => $source_table,
        'WHERE'  => [
            'name' => $name,
        ],
    ]);
    if ($result->numrows() > 0) {
        // Source already exists, no need to insert
        return $result->current()['id'];
    }
    $result = $DB->insert($source_table, [
        'name'                                               => $name,
        'plugin_carbon_carbonintensitysources_id_historical' => $source_historical,
    ]);
    if (!$result) {
        throw new RuntimeException("Failed to insert new carbon intensity source: $name");
    }
    $source_id = $DB->insertId();
    return $source_id;
};

$add_intensity = function ($source_id, $zone_id, $date, $intensity, $quality) use ($DB): int {
    $db_utils = new DbUtils();
    $carbon_intensity_table = $db_utils->getTableForItemType(CarbonIntensity::class);
    $result = $DB->request([
        'SELECT' => 'id',
        'FROM'   => $carbon_intensity_table,
        'WHERE'  => [
            'date'                                    => $date,
            'plugin_carbon_carbonintensitysources_id' => $source_id,
            'plugin_carbon_zones_id'                  => $zone_id,
        ],
    ]);
    if ($result->numrows() > 0) {
        // Intensity already exists, no need to insert
        return $result->current()['id'];
    }

    $result = $DB->insert($carbon_intensity_table, [
        'date' => $date,
        'plugin_carbon_carbonintensitysources_id' => $source_id,
        'plugin_carbon_zones_id' => $zone_id,
        'intensity' => $intensity,
        'data_quality' => $quality // constant GlpiPlugin\Carbon\DataTracking::DATA_QUALITY_*
    ]);
    if (!$result) {
        throw new RuntimeException("Failed to insert new carbon intensity: $date");
    }
    return $DB->insertId();
};

// World data
$source_name = 'Ember - Energy Institute';
$zone_name = 'World';
$source_id = $add_source($source_name, 1);
$zone_id_world = $add_zone($zone_name);

$world_carbon_intensity = include(dirname(__DIR__, 2) . '/data/carbon_intensity/world.php');
$dbUtil = new DbUtils();
foreach ($world_carbon_intensity as $year => $intensity) {
    $id = $add_intensity($source_id, $zone_id_world, "$year-01-01 00:00:00", $intensity, 2);
}
// Quebec Data
$source_name = 'Hydro Quebec';
$zone_name = 'Quebec';
$source_id = $add_source($source_name, 1);
$zone_id_quebec = $add_zone($zone_name);
$quebec_carbon_intensity = include(dirname(__DIR__, 2) . '/data/carbon_intensity/quebec.php');
foreach ($quebec_carbon_intensity as $year => $intensity) {
    $id = $add_intensity($source_id, $zone_id_quebec, "$year-01-01 00:00:00", $intensity, 2);
}
