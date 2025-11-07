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
/** @var Migration $migration */

$table = 'glpi_plugin_carbon_sources';
$migration->addField(
    $table,
    'is_carbon_intensity_source',
    'bool',
    [
        'after'     => 'is_fallback',
        'update'    => 1,
        'condition' => "WHERE `name` IN ('RTE', 'ElectricityMap', 'Ember - Energy Institute', 'Hydro Quebec')"
    ]
);
$migration->changeField(
    $table,
    'is_fallback',
    'fallback_level',
    'int'
);

$table = 'glpi_plugin_carbon_locations';
$migration->addField(
    $table,
    'plugin_carbon_sources_zones_id',
    'fkey',
    [
        'after'     => 'boavizta_zone'
    ]
);
$migration->migrationOneTable($table);

// Blacklist assets by type - to avoid computation of impacts
$table = 'glpi_plugin_carbon_computertypes';
$migration->addField(
    $table,
    'is_ignore',
    'bool',
    [
        'after'     => 'category'
    ]
);

$table = 'glpi_plugin_carbon_monitortypes';
$migration->addField(
    $table,
    'is_ignore',
    'bool',
    [
        'after'     => 'power_consumption'
    ]
);

$table = 'glpi_plugin_carbon_networkequipmenttypes';
$migration->addField(
    $table,
    'is_ignore',
    'bool',
    [
        'after'     => 'power_consumption'
    ]
);
