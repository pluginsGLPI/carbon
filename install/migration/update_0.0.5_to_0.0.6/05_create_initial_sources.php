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

/** @var DBmysql $DB */
/** @var Migration $migration */

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

$add_zone('France');
