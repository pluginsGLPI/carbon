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

        fwrite(STDOUT, sprintf(PHP_EOL . "Loading Carbon dataset version %s" . PHP_EOL, $version));

        // The following dataset contains data for France, then timezone must be Europe/Paris
        $DB->setTimezone('Europe/Paris');
        //Set GLPI timezone as well
        Config::setConfigurationValues('core', ['timezone' => 'Europe/Paris']);
        $DB->beginTransaction();

        $source_table = 'glpi_plugin_carbon_sources';
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

        $zone_table = 'glpi_plugin_carbon_zones';
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
                'plugin_carbon_sources_id_historical' => $source_id,
            ]);
            $zone_id = $DB->insertId();
        } else {
            $zone_id = $iterator->current()['id'];
        }
        $source_zone_table = 'glpi_plugin_carbon_sources_zones';
        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM' => $source_zone_table,
            'WHERE' => [
                'plugin_carbon_sources_id' => $source_id,
                'plugin_carbon_zones_id'   => $zone_id,
            ],
        ]);
        if ($iterator->count() === 0) {
            $result = $DB->insert($source_zone_table, [
                'plugin_carbon_sources_id' => $source_id,
                'plugin_carbon_zones_id'   => $zone_id,
                'code'                                    => 'FZ',
            ]);
        }

        $intensity_table = 'glpi_plugin_carbon_carbonintensities';
        // Support any line ending type
        // $line_ending_mode = ini_get('auto_detect_line_endings');
        // ini_set('auto_detect_line_endings', true);
        $file = dirname(__DIR__) . '/fixtures/carbon_intensity.csv';
        if (($handle = fopen($file, 'r')) === false) {
            fwrite(STDOUT, sprintf('Failed to open carbon intensity dataset CSV file' . PHP_EOL));
            exit(1);
        }
        while (($row = fgetcsv($handle, 256, ',', '"', '\\')) !== false) {
            $DB->insert($intensity_table, [
                'plugin_carbon_sources_id' => $source_id,
                'plugin_carbon_zones_id'   => $zone_id,
                'date' => $row[0],
                'intensity' => $row[1],
            ]);
        }
        // ini_set('auto_detect_line_endings', $line_ending_mode);
        $condition = [
            'plugin_carbon_sources_id' => $source_id,
            'plugin_carbon_zones_id'   => $zone_id,
        ];
        $count = (new DbUtils())->countElementsInTable($intensity_table, $condition);
        if ($count !== 3648) {
            fwrite(STDOUT, sprintf('Failed to load carbon intensity dataset' . PHP_EOL));
            exit(1);
        }

        $DB->commit();
        $GLPI_CACHE->clear();

        Config::setConfigurationValues('carbon:test_dataset', ['version' => $version]);
    }
}
