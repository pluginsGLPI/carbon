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

// Time selector in GLPI 11 sets H:m without s (seconds)
// We need to drop seconds from times saved in DB in version 1.0.x for GLPI 10

// This migtation update is repeated here as this format change has been detected on 2025/12
$table = 'glpi_plugin_carbon_computerusageprofiles';
$iterator = $DB->request([
    'SELECT' => ['id', 'time_start', 'time_stop'],
    'FROM' => $table,
]);
foreach ($iterator as $row) {
    $split_start = explode(':', $row['time_start']);
    $split_stop  = explode(':', $row['time_stop']);
    $update = false;
    if (count($split_start) > 2) {
        array_splice($split_start, 2);
        $update = true;
    }
    if (count($split_stop) > 2) {
        array_splice($split_stop, 2);
        $update = true;
    }
    if ($update) {
        $DB->update($table, [
            'time_start' => implode(':', $split_start),
            'time_stop' => implode(':', $split_stop),
        ], [
            'id' => $row['id'],
        ]);
    }
}
