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

/** @var DBmysql $DB */

$dbUtil = new DbUtils();
$source_table = $dbUtil->getTableForItemType(GlpiPlugin\Carbon\CarbonIntensitySource::class);
$zone_table = $dbUtil->getTableForItemType(GlpiPlugin\Carbon\Zone::class);
$carbon_intensity_table = $dbUtil->getTableForItemType(GlpiPlugin\Carbon\CarbonIntensity::class);

// World data
$source_name = 'Ember - Energy Institute';
$zone_name = 'World';
$success = $DB->insert($source_table, [
    'name' => $source_name
]);
if (!$success) {
    throw new RuntimeException("Failed to insert new carbon intensity source: $source_name");
}
$source_id = $DB->insertId();

$success = $DB->insert($zone_table, [
    'name' => $zone_name
]);
if (!$success) {
    throw new RuntimeException("Failed to insert new carbon intensity zone: $zone_name");
}
$zone_id_world = $DB->insertId();

$world_carbon_intensity = include(dirname(__DIR__, 2) . '/data/carbon_intensity/world.php');
$dbUtil = new DbUtils();
foreach ($world_carbon_intensity as $year => $intensity) {
    $DB->insert($carbon_intensity_table, [
        'date' => "$year-01-01 00:00:00",
        'plugin_carbon_carbonintensitysources_id' => $source_id,
        'plugin_carbon_zones_id' => $zone_id_world,
        'intensity' => $intensity,
        'data_quality' => 2 // constant GlpiPlugin\Carbon\DataTracking::DATA_QUALITY_ESTIMATED
    ]);
}
// Quebec Data
$source_name = 'Hydro Quebec';
$zone_name = 'Quebec';
$success = $DB->insert($source_table, [
    'name' => $source_name
]);
if (!$success) {
    throw new RuntimeException("Failed to insert new carbon intensity source: $source_name");
}
$source_id = $DB->insertId();

$success = $DB->insert($zone_table, [
    'name' => $zone_name
]);
if (!$success) {
    throw new RuntimeException("Failed to insert new carbon intensity zone: $zone_name");
}
$zone_id_quebec = $DB->insertId();

$quebec_carbon_intensity = include(dirname(__DIR__, 2) . '/data/carbon_intensity/quebec.php');
foreach ($quebec_carbon_intensity as $year => $intensity) {
    $success = $DB->insert($carbon_intensity_table, [
        'date' => "$year-01-01 00:00:00",
        'plugin_carbon_carbonintensitysources_id' => $source_id,
        'plugin_carbon_zones_id' => $zone_id_quebec,
        'intensity' => $intensity,
        'data_quality' => 2 // constant GlpiPlugin\Carbon\DataTracking::DATA_QUALITY_ESTIMATED
    ]);
}
