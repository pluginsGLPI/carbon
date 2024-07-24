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

global $DB;

use GlpiPlugin\Carbon\CarbonIntensitySource;

// Create RTE and Electrucuty map data sources in DB

$data_sources = ['RTE', 'ElectricityMap'];
$dbUtil = new DbUtils();
$source_table = $dbUtil->getTableForItemType(CarbonIntensitySource::class);
$source_table_exists = $DB->tableExists($source_table);
foreach ($data_sources as $data_source) {
    if ($source_table_exists) {
        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM' => $source_table,
            'WHERE' => ['name' => $data_source]
        ]);
        if ($iterator->count() === 1) {
            continue;
        }
    }
    $query = $DB->buildInsert($source_table, ['name' => $data_source]);
    $migration->addPostQuery($query);
}
