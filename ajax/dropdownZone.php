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

use GlpiPlugin\Carbon\Source_Zone;
use GlpiPlugin\Carbon\Zone;

include(__DIR__ . '/../../../inc/includes.php');

// Check if plugin is activated...
if (!Plugin::isPluginActive('carbon')) {
    http_response_code(404);
    die();
}

// throw new RuntimeException('Required argument missing or incorrect!');

$source_zone_table = Source_Zone::getTable();
$zone_table = Zone::getTable();
$source_id = (int) $_POST['plugin_carbon_sources_id'];
Zone::dropdown([
    'rand' => (int) $_POST['dom_id'],
    'condition' => Zone::getRestrictBySourceCondition($source_id),
    // 'disabled'  => ($source_id === 0),
    'specific_tags' => ($source_id === 0 ? ['disabled' => 'disabled'] : []),
]);
