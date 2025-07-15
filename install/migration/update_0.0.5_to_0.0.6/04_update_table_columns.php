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

/** @var Migration $migration */
/** @var DBmysql $DB */

$table = 'glpi_plugin_carbon_computertypes';
$migration->addField($table, 'category', 'integer', ['after' => 'power_consumption']);

$table = 'glpi_plugin_carbon_embodiedimpacts';
$migration->addField($table, 'engine', 'string', ['after' => 'items_id']);
$migration->addField($table, 'engine_version', 'string', ['after' => 'engine']);
$migration->addField($table, 'date_mod', 'timestamp', ['after' => 'engine_version']);

$table = 'glpi_plugin_carbon_carbonemissions';
$migration->addField($table, 'engine', 'string', ['after' => 'items_id']);
$migration->addField($table, 'engine_version', 'string', ['after' => 'engine']);
$migration->addField($table, 'date_mod', 'timestamp', ['after' => 'engine_version']);

$table = 'glpi_plugin_carbon_carbonintensitysources';
$migration->addField($table, 'is_fallback', 'bool', ['after' => 'name']);
$migration->migrationOneTable($table);
