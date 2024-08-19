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

namespace GlpiPlugin\Carbon\Tests;

use Plugin;
use Config;
use DbUtils;

class GlobalFixture
{
    /**
     * Load fixtures shared among all test cases of all test suites
     *
     * STDOUt is used to output messages to prevent header already sent errors
     * when GLPI initializes a session
     *
     * @return void
     */
    public static function loadDataset()
    {
        global $DB, $GLPI_CACHE;

        $version = '1.0.0';

        if (!Plugin::isPluginActive(TEST_PLUGIN_NAME)) {
        // Plugin not activated yet
            return;
        }

        $conf = Config::getConfigurationValue('carbon:test_dataset', 'version');
        if ($conf !== null && $conf == $version) {
            fwrite(STDOUT, sprintf(PHP_EOL . "Plugin dataset version %s already loaded" . PHP_EOL, $conf));
            return;
        }

        fwrite(STDOUT, sprintf(PHP_EOL . "Loading GLPI dataset version %s" . PHP_EOL, $version));

        // The following dataset contains data for France, then timezone must be Europe/Paris
        $DB->setTimezone('Europe/Paris');
        //Set GLPI timezone as well
        Config::setConfigurationValues('core', ['timezone' => 'Europe/Paris']);
        $DB->beginTransaction();

        $source_table = 'glpi_plugin_carbon_carbonintensitysources';
        $fake_source_name = 'Fake source';
        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM' => $source_table,
            'WHERE' => [
                'name' => $fake_source_name
            ],
        ]);
        if ($iterator->count() === 0) {
            $result = $DB->insert($source_table, [
                'name' => $fake_source_name,
            ]);
            $source_id = $DB->insertId();
        } else {
            $source_id = $iterator->current()['id'];
        }

        $zone_table = 'glpi_plugin_carbon_carbonintensityzones';
        $fake_zone_name = 'Fake zone';
        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM' => $zone_table,
            'WHERE' => [
                'name' => $fake_zone_name
            ],
        ]);
        if ($iterator->count() === 0) {
            $result = $DB->insert($zone_table, [
                'name' => $fake_zone_name,
            ]);
            $zone_id = $DB->insertId();
        } else {
            $zone_id = $iterator->current()['id'];
        }
        $source_zone_table = 'glpi_plugin_carbon_carbonintensitysources_carbonintensityzones';
        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM' => $source_zone_table,
            'WHERE' => [
                'plugin_carbon_carbonintensitysources_id' => $source_id,
                'plugin_carbon_carbonintensityzones_id'   => $zone_id,
            ],
        ]);
        if ($iterator->count() === 0) {
            $result = $DB->insert($source_zone_table, [
                'plugin_carbon_carbonintensitysources_id' => $source_id,
                'plugin_carbon_carbonintensityzones_id'   => $zone_id,
                'code'                                    => 'FZ',
            ]);
        }

        $intensity_table = 'glpi_plugin_carbon_carbonintensities';
        // Support any line ending type
        $line_ending_mode = ini_get('auto_detect_line_endings');
        ini_set('auto_detect_line_endings', true);
        if (($handle = fopen(__DIR__ . '/../fixtures/carbon_intensity.csv', 'r')) === false) {
            fwrite(STDOUT, sprintf('Failed to open carbon intensity dataset CSV file' . PHP_EOL));
            exit(1);
        }
        while (($row = fgetcsv($handle, 256)) !== false) {
            $DB->insert($intensity_table, [
                'plugin_carbon_carbonintensitysources_id' => $source_id,
                'plugin_carbon_carbonintensityzones_id'   => $zone_id,
                'date' => $row[0],
                'intensity' => $row[1],
            ]);
        }
        ini_set('auto_detect_line_endings', $line_ending_mode);
        $count = (new DbUtils())->countElementsInTable($intensity_table);
        if ($count !== 3648) {
            fwrite(STDOUT, sprintf('Failed to load carbon intensity dataset' . PHP_EOL));
            exit(1);
        }

        $DB->commit();
        $GLPI_CACHE->clear();

        Config::setConfigurationValues('carbon:test_dataset', ['version' => $version]);
    }
}
