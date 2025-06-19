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

use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\Zone;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\EmbodiedImpact;
use GlpiPlugin\Carbon\UsageInfo;
use GlpiPlugin\Carbon\Location;
use GlpiPlugin\Carbon\MonitorType;
use GlpiPlugin\Carbon\NetworkEquipmentType;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use CommonDBTM;
use CommonGLPI;
use DbUtils;
use Plugin;

class SearchOptionTest extends CommonTestCase
{
    private array $exceptions = [
        CarbonEmission::class => [
            'types_id',
            'models_id',
        ],
        ComputerType::class => [],
        MonitorType::class => [],
        UsageInfo::class => [],
        CarbonIntensity::class => [
            'data_quality'
        ],
        EmbodiedImpact::class => [
            'gwp_quality',
            'adp_quality',
            'pe_quality',
        ],
        NetworkEquipmentType::class => [],
        Location::class => [],
        Zone::class => [
            'entities_id',
        ],
    ];

    private array $mapping = [
        CarbonIntensity::class => [
            'plugin_carbon_carbonintensitysources_id' => 'name',
            'plugin_carbon_zones_id'   => 'name',
        ],
        Zone::class => [
            'plugin_carbon_carbonintensitysources_id_historical' => 'name',
        ]
    ];

    public function testSearchOption()
    {
        global $DB;

        // Find each .php file in /src directory and subdirectories
        $plugin_dir = Plugin::getPhpDir(TEST_PLUGIN_NAME);
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($plugin_dir . '/src'),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        // Traverse each item
        foreach ($iterator as $item) {
            if (!$item->isFile() || pathinfo($item->getFilename(), PATHINFO_EXTENSION) !== 'php') {
                continue;
            }

            // Compute the class name from the path
            $class_name = str_replace($plugin_dir . '/src', '', $item->getPath());
            //$class_name = ltrim($class_name, '/');
            $class_name = str_replace('/', '\\', $class_name);
            // Remove leading slash
            $class_name .= '\\' . pathinfo($item->getFilename(), PATHINFO_FILENAME);
            // $class_name = str_replace(['_', '.'], ['\\', '\\'], $class_name);
            $class_name = 'GlpiPlugin\\Carbon' . $class_name;

            // Check the class has CommonDBTM and CommonGLPI as ancestors
            $parents = class_parents($class_name);
            if ($parents === false) {
                continue;
            }
            if (!in_array(CommonDBTM::class, $parents) || !in_array(CommonGLPI::class, $parents)) {
                continue;
            }

            // Check a table exists the the class
            $dbutils = new DbUtils();
            $table = $dbutils->getTableForItemType($class_name);
            if (!$DB->tableExists($table)) {
                continue;
            }

            // Instanciate the class
            /** @var CommonDBTM $instance */
            $instance = new $class_name();

            // Get all columns of the class's table
            $table = $instance::getTable();
            $this->assertTrue(!empty($table));
            $table_fields = $DB->listFields($table);

            // Check that a search option exists for each field
            foreach ($table_fields as $key => $value) {
                if (isset($this->exceptions[$class_name]) && count($this->exceptions[$class_name]) === 0) {
                    // the itemtype is ignored (no search option for any field and this is intended)
                    continue;
                }
                if (isset($this->exceptions[$class_name]) && in_array($key, $this->exceptions[$class_name])) {
                    // The field is ignored (aka no search option and this is intended)
                    continue;
                }

                if (isset($this->mapping[$class_name]) && isset($this->mapping[$class_name][$key])) {
                    $key = $this->mapping[$class_name][$key];
                }
                $search_option = $instance->getSearchOptionByField('field', $key);
                $this->assertTrue(!empty($search_option), "No search option for field $key in class $class_name");
            }
        }
    }
}
