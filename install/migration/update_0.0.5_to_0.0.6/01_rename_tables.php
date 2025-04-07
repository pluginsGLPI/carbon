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
