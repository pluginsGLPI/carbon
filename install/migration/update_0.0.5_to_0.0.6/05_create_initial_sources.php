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
