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

use Session;
use Config;
use CronTask as GLPICronTask;
use DisplayPreference;
use GLPIKey;
use Plugin;
use Profile;
use ProfileRight;
use Glpi\System\Diagnostic\DatabaseSchemaIntegrityChecker;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\CronTask;
use GlpiPlugin\Carbon\Report;

class PluginInstallTest extends CommonTestCase
{
    public function setUp(): void
    {
        //   parent::setUp();
        //   self::setupGLPIFramework();
        self::login('glpi', 'glpi', true);
    }


    /**
     * Execute plugin installation in the context if tests
     *
     * @return void
     */
    protected function executeInstallation()
    {
        global $DB;

        $pluginName = TEST_PLUGIN_NAME;

        $this->setupGLPIFramework();
        $this->assertTrue($DB->connected);

        //Drop plugin configuration if exists
        $config = new Config();
        $config->deleteByCriteria(['context' => $pluginName]);

        // Drop tables of the plugin if they exist
        $result = $DB->listTables('glpi_plugin_' . $pluginName . '_%');
        foreach ($result as $data) {
            $DB->dropTable($data['TABLE_NAME']);
        }

        // Reset logs
        $this->resetGLPILogs();

        $plugin = new Plugin();
        // Since GLPI 9.4 plugins list is cached
        $plugin->checkStates(true);
        $plugin->getFromDBbyDir($pluginName);
        $this->assertFalse($plugin->isNewItem());

        // Install the plugin
        ob_start(function ($in) {
            return $in;
        });
        $plugin->install($plugin->fields['id']);
        $installOutput = ob_get_contents();
        ob_end_clean();
        $this->assertTrue($plugin->isInstalled($pluginName), $installOutput);

        // Enable the plugin
        $plugin->activate($plugin->fields['id']);
        $plugin->init();
        $messages = $_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR] ?? [];
        $messages = implode(PHP_EOL, $messages);
        $this->assertTrue(Plugin::isPluginActive($pluginName), 'Cannot enable the plugin: ' . $messages);
    }

    public function testInstallPlugin()
    {
        if (!Plugin::isPluginActive(TEST_PLUGIN_NAME)) {
            // For unit test script which expects that installation runs in the tests context
            $this->executeInstallation();
        }
        $this->assertTrue(Plugin::isPluginActive(TEST_PLUGIN_NAME), 'Plugin not activated');
        $this->checkSchema(PLUGIN_CARBON_VERSION);

        $this->checkConfig();
        // $this->checkRequestType();
        $this->checkAutomaticAction();
        // $this->checkDashboard();
        $this->checkRights();
        $this->checkDataSources();
        $this->checkDisplayPrefs();
    }

    public function testConfigurationExists()
    {
        $config = Config::getConfigurationValues(TEST_PLUGIN_NAME);
        $expected = [];
        $diff = array_diff_key(array_flip($expected), $config);
        $this->assertEquals(0, count($diff));

        return $config;
    }

    private function checkSchema(
        string $version,
        bool $strict = true,
        bool $ignore_innodb_migration = false,
        bool $ignore_timestamps_migration = false,
        bool $ignore_utf8mb4_migration = false,
        bool $ignore_dynamic_row_format_migration = false,
        bool $ignore_unsigned_keys_migration = false
    ): bool {
        global $DB;

        $schemaFile = plugin_carbon_getSchemaPath($version);

        $checker = new DatabaseSchemaIntegrityChecker(
            $DB,
            $strict,
            $ignore_innodb_migration,
            $ignore_timestamps_migration,
            $ignore_utf8mb4_migration,
            $ignore_dynamic_row_format_migration,
            $ignore_unsigned_keys_migration
        );

        try {
            $differences = $checker->checkCompleteSchema($schemaFile, true, 'plugin:carbon');
        } catch (\Throwable $e) {
            $message = __('Failed to check the sanity of the tables!', 'carbon');
            if (isCommandLine()) {
                echo $message . PHP_EOL;
            } else {
                Session::addMessageAfterRedirect($message, false, ERROR);
            }
            return false;
        }

        if (count($differences) > 0) {
            foreach ($differences as $table_name => $difference) {
                $message = null;
                switch ($difference['type']) {
                    case DatabaseSchemaIntegrityChecker::RESULT_TYPE_ALTERED_TABLE:
                        $message = sprintf(__('Table schema differs for table "%s".'), $table_name);
                        break;
                    case DatabaseSchemaIntegrityChecker::RESULT_TYPE_MISSING_TABLE:
                        $message = sprintf(__('Table "%s" is missing.'), $table_name);
                        break;
                    case DatabaseSchemaIntegrityChecker::RESULT_TYPE_UNKNOWN_TABLE:
                        $message = sprintf(__('Unknown table "%s" has been found in database.'), $table_name);
                        break;
                }
                echo $message . PHP_EOL;
                echo $difference['diff'] . PHP_EOL;
            }
            return false;
        }

        return true;
    }

    private function checkAutomaticAction()
    {
        $cronTask = new GLPICronTask();
        $rows = $cronTask->find([
            'itemtype' => ['LIKE', '%' . 'Carbon' . '%'],
        ]);
        $this->assertEquals(3, count($rows));

        $cronTask = new GLPICronTask();
        $cronTask->getFromDBByCrit([
            'itemtype' => CronTask::class,
            'name'     => 'Historize',
        ]);
        $this->assertFalse($cronTask->isNewItem());
        $this->assertEquals(10000, $cronTask->fields['param']);

        $cronTask = new GLPICronTask();
        $cronTask->getFromDBByCrit([
            'itemtype' => CronTask::class,
            'name'     => 'DownloadRte',
        ]);
        $this->assertFalse($cronTask->isNewItem());

        $cronTask = new GLPICronTask();
        $cronTask->getFromDBByCrit([
            'itemtype' => CronTask::class,
            'name'     => 'DownloadElectricityMap',
        ]);
        $this->assertFalse($cronTask->isNewItem());
    }

    private function checkConfig()
    {
        $plugin_path = Plugin::getPhpDir(TEST_PLUGIN_NAME, true);
        require_once($plugin_path . '/setup.php');

        $expected = [
            'electricitymap_api_key'  => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
            'dbversion'               => PLUGIN_CARBON_SCHEMA_VERSION,
        ];

        $config = Config::getConfigurationValues('plugin:' . TEST_PLUGIN_NAME);
        $this->assertCount(count($expected), $config); // 1 more key : dbversion

        $glpi_key = new GLPIKey();
        foreach ($expected as $key => $expected_value) {
            $value = $config[$key];
            if (!empty($value) && $glpi_key->isConfigSecured('plugin:carbon', $key)) {
                $value = $glpi_key->decrypt($config[$key]);
            }
            $this->assertEquals($expected_value, $value, "configuration key $key mismatch");
        }
    }

    private function checkRights()
    {
        // Key is ID of the profile, value is the name of the profile
        $expected_profiles = [
            4 =>  READ, // 'Super-Admin'
        ];
        $this->checkRight(Report::$rightname, $expected_profiles);
    }

    private function checkRight(string $rightname, array $profiles)
    {
        global $DB;

        $profile_table = Profile::getTable();
        $profile_fk = Profile::getForeignKeyField();
        $profileright_table = ProfileRight::getTable();
        $request = [
            'SELECT' => [
                Profile::getTableField('id'),
                ProfileRight::getTableField('rights'),
            ],
            'FROM' => $profile_table,
            'LEFT JOIN' => [
                $profileright_table => [
                    'FKEY' => [
                        $profile_table => 'id',
                        $profileright_table => $profile_fk,
                    ],
                ],
            ],
            'WHERE' => [
                ProfileRight::getTableField('name') => $rightname,
            ]
        ];

        foreach ($DB->request($request) as $profile_right) {
            if (!isset($profiles[$profile_right['id']])) {
                $this->assertEquals(0, $profile_right['rights']);
            } else {
                $this->assertEquals($profiles[$profile_right['id']], $profile_right['rights']);
            }
        }
    }

    private function checkDisplayPrefs()
    {
        $displayPreference = new DisplayPreference();
        $preferences = $displayPreference->find(['itemtype' => CarbonIntensity::class, 'users_id' => 0]);

        $this->assertEquals(5, count($preferences));
    }

    private function checkDataSources()
    {
        $sources = ['RTE', 'ElectricityMap'];
        foreach ($sources as $source_name) {
            $source = new CarbonIntensitySource();
            $source->getFromDBByCrit([
                'name' => $source_name
            ]);
            $this->assertFalse($source->isNewItem());
        }
    }
}
