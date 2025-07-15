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

// Rename enbedded impacts table
// Move data to new table
$old_table = 'glpi_plugin_carbon_embeddedimpacts';
$new_table = 'glpi_plugin_carbon_embodiedimpacts';
/** @var Migration $migration */
$migration->renameTable($old_table, $new_table);

$old_itemtype = '\\GlpiPlugin\\Carbon\\EmbeddedImpact';
$new_itemtype = '\\GlpiPlugin\\Carbon\\EmbodiedImpact';

// Update display preferences
$DB->update(DisplayPreference::getTable(), [
    'itemtype' => $new_itemtype
], [
    'itemtype' => $old_itemtype
]);

// Rename carbon intensity zones table

// Move data to new table
$old_table = 'glpi_plugin_carbon_carbonintensityzones';
$new_table = 'glpi_plugin_carbon_zones';
/** @var Migration $migration */
$migration->renameTable($old_table, $new_table);

$old_itemtype = '\\GlpiPlugin\\Carbon\\CarbonIntensityZone';
$new_itemtype = '\\GlpiPlugin\\Carbon\\Zone';

// Update display preferences
/** @var DBmysql $DB */
$DB->update(DisplayPreference::getTable(), [
    'itemtype' => $new_itemtype
], [
    'itemtype' => $old_itemtype
]);
$table = 'glpi_plugin_carbon_carbonintensities';
$migration->dropKey($table, 'unicity');
$migration->changeField($table, 'plugin_carbon_carbonintensityzones_id', 'plugin_carbon_zones_id', "int unsigned NOT NULL DEFAULT '0'");
$migration->migrationOneTable($table);
$migration->addKey($table, ['date', 'plugin_carbon_carbonintensitysources_id', 'plugin_carbon_zones_id'], 'unicity', 'UNIQUE');
$migration->migrationOneTable($table);

$old_table = 'glpi_plugin_carbon_carbonintensitysources_carbonintensityzones';
$new_table = 'glpi_plugin_carbon_carbonintensitysources_zones';
$migration->renameTable($old_table, $new_table);
$migration->dropKey($new_table, 'unicity');
$migration->changeField($new_table, 'plugin_carbon_carbonintensityzones_id', 'plugin_carbon_zones_id', "int unsigned NOT NULL DEFAULT '0'");
$migration->migrationOneTable($new_table);
$migration->addKey($new_table, ['plugin_carbon_carbonintensitysources_id', 'plugin_carbon_zones_id'], 'unicity', 'UNIQUE');

$old_table = 'glpi_plugin_carbon_environmentalimpacts';
$new_table = 'glpi_plugin_carbon_usageinfos';
$migration->renameTable($old_table, $new_table);
$migration->changeField($new_table, 'computers_id', 'items_id', "int unsigned NOT NULL DEFAULT '0'");
// The update option forces the tabe to be updated now
$migration->addField($new_table, 'itemtype', 'string', ['after' => 'id', 'update' => "'Computer'"]);
$migration->addField($new_table, 'planned_lifespan', "int unsigned NOT NULL DEFAULT '0'", ['after' => 'plugin_carbon_computerusageprofiles_id']);
$migration->dropKey($new_table, 'unicity');
// Must apply changes now re-add the key
$migration->migrationOneTable($new_table);
$migration->addKey($new_table, ['itemtype', 'items_id'], 'unicity', 'UNIQUE');
