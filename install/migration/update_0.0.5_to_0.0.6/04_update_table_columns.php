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
