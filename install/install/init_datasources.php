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

use GlpiPlugin\Carbon\Install;

/** @var DBmysql $DB */
global $DB;

$source_id = Install::getOrCreateSource('RTE', 0);
$zone_id = Install::getOrCreateZone('France', $source_id);

$source_id = Install::getOrCreateSource('ElectricityMap', 0);


$dbUtil = new DbUtils();
$table = $dbUtil->getTableForItemType(GlpiPlugin\Carbon\CarbonIntensity::class);

// Expected columns are Entity;Code; Year; Carbon intensity of electricity - gCO2/kWh
$data_source = dirname(__DIR__) . '/data/carbon_intensity/carbon-intensity-electricity.csv';

// Create data source in DB
$source_id = Install::getOrCreateSource('Ember - Energy Institute', 1);

if ($handle = fopen($data_source, 'r')) {
    $line_number = 0;
    while (($line = fgetcsv($handle, 255, ',', '"', '\\')) !== false) {
        $line_number++;
        if ($line_number === 1 || count($line) < 4) {
            continue; // Skip header or  lines with insufficient data
        }

        $entity = $line[0];
        $code = $line[1];
        $year = (int)$line[2];
        $intensity = (float)$line[3];

        // Skip if the code is empty
        if ($code === '') {
            continue;
        }

        $zone_id = Install::getOrCreateZone($entity, $source_id);
        Install::linkSourceZone($source_id, $zone_id);

        // Insert into the database
        $row_exists = (new DbUtils())->countElementsInTable($table, [
            'date' => "$year-01-01 00:00:00",
            'plugin_carbon_carbonintensitysources_id' => $source_id,
            'plugin_carbon_zones_id' => $zone_id
        ]);
        if (!$row_exists) {
            $success = $DB->insert($table, [
                'date' => "$year-01-01 00:00:00",
                'plugin_carbon_carbonintensitysources_id' => $source_id,
                'plugin_carbon_zones_id' => $zone_id,
                'intensity' => $intensity,
                'data_quality' => 2 // constant GlpiPlugin\Carbon\DataTracking::DATA_QUALITY_ESTIMATED
            ]);
        } else {
            $success = $DB->update($table, [
                'intensity' => $intensity,
                'data_quality' => 2 // constant GlpiPlugin\Carbon\DataTracking::DATA_QUALITY_ESTIMATED
            ], [
                'date' => "$year-01-01 00:00:00",
                'plugin_carbon_carbonintensitysources_id' => $source_id,
                'plugin_carbon_zones_id' => $zone_id
            ]);
        }

        if (!$success) {
            throw new \RuntimeException("Failed to insert data for year $year");
        }
    }
}

$source_id = Install::getOrCreateSource('Hydro Quebec');
$zone_id_quebec = Install::getOrCreateZone('Quebec', $source_id);
Install::linkSourceZone($source_id, $zone_id);

$quebec_carbon_intensity = include(dirname(__DIR__) . '/data/carbon_intensity/quebec.php');
foreach ($quebec_carbon_intensity as $year => $intensity) {
    $row_exists = (new DbUtils())->countElementsInTable($table, [
        'date' => "$year-01-01 00:00:00",
        'plugin_carbon_carbonintensitysources_id' => $source_id,
        'plugin_carbon_zones_id' => $zone_id_quebec,
    ]);
    if (!$row_exists) {
        $success = $DB->insert($table, [
            'date' => "$year-01-01 00:00:00",
            'plugin_carbon_carbonintensitysources_id' => $source_id,
            'plugin_carbon_zones_id' => $zone_id_quebec,
            'intensity' => $intensity,
            'data_quality' => 2 // constant GlpiPlugin\Carbon\DataTracking::DATA_QUALITY_ESTIMATED
        ]);
    } else {
        $success = $DB->update($table, [
            'intensity' => $intensity,
            'data_quality' => 2 // constant GlpiPlugin\Carbon\DataTracking::DATA_QUALITY_ESTIMATED
        ], [
            'date' => "$year-01-01 00:00:00",
            'plugin_carbon_carbonintensitysources_id' => $source_id,
            'plugin_carbon_zones_id' => $zone_id
        ]);
    }
}
