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

use Config;
use GLPIKey;
use Glpi\Plugin\Hooks;
use Plugin;

// Load base class
// TODO: Create common code in tests/src/<someclass>.php
require_once(__DIR__ . '/../install/PluginInstallTest.php');
class PluginUpgradeTest extends PluginInstallTest
{
    private string $old_version = '0.0.1';

    /**
     * Install an old schema and configuration of the plugin
     * Assume there is no data from a previous installation
     *
     * @return void
     */
    protected function executeInstallation()
    {
        global $DB, $PLUGIN_HOOKS;

        $plugin_name = TEST_PLUGIN_NAME;

        $this->setupGLPIFramework();
        $this->assertTrue($DB->connected);

        $success = $DB->runFile(__DIR__ . "/../../install/mysql/plugin_carbon_{$this->old_version}_empty.sql");
        $this->assertTrue($success, 'Failed to install old version schema');

        $success = $this->runSqlFile(__DIR__ . "/../fixtures/version_{$this->old_version}_data.sql");
        $this->assertTrue($success, 'Failed to install old version data');

        // Ignore SQL warnings which may occur when installing an old schema
        file_put_contents(GLPI_LOG_DIR . "/sql-errors.log", '');

        // Set encrypted configuration values
        $PLUGIN_HOOKS[Hooks::SECURED_CONFIGS]['carbon'] = [
            'electricitymap_api_key',
        ];
        $current_config = Config::getConfigurationValues('plugin:carbon');
        $config_entries = [
            'electricitymap_api_key'              => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
        ];
        foreach ($config_entries as $key => $value) {
            if (!isset($current_config[$key])) {
                Config::setConfigurationValues('plugin:carbon', [$key => $value]);
            }
        }

        $initial_state = Plugin::NOTACTIVATED;
        $success = $DB->doQuery("UPDATE `glpi_plugins`
            SET `version`='{$this->old_version}', `state`={$initial_state}
            WHERE `directory`='{$plugin_name}'");
        $this->assertTrue($success);

        $plugin = new Plugin();
        // Since GLPI 9.4 plugins list is cached
        @$plugin->checkStates(true); // Disable PHP warning triggered bu plugin version change
        $plugin->getFromDBbyDir($plugin_name);
        $this->assertFalse($plugin->isNewItem());
        $plugin->activate($plugin->getID());
        $plugin->init();

        ob_start(function ($in) {
            return $in;
        });
        $DB->clearSchemaCache();
        plugin_carbon_install();
        // Ignore SQL warnings. We must rely on schema comparison to detect errors
        // which impact plugin upgrade
        file_put_contents(GLPI_LOG_DIR . "/sql-errors.log", '');
        $install_output = ob_get_contents();
        ob_end_clean();
        $this->assertTrue($plugin->isInstalled($plugin_name), $install_output);
    }

    protected function checkConfig()
    {
        $plugin_path = Plugin::getPhpDir(TEST_PLUGIN_NAME, true);
        require_once($plugin_path . '/setup.php');

        $expected = [
            'electricitymap_api_key'             => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
            'dbversion'                          => PLUGIN_CARBON_SCHEMA_VERSION,
            'RTE_zone_setup_complete'            => 0,
            'ElectricityMap_zone_setup_complete' => 0,
        ];

        $config = Config::getConfigurationValues('plugin:' . TEST_PLUGIN_NAME);
        $this->assertCount(count($expected), $config);

        $glpi_key = new GLPIKey();
        foreach ($expected as $key => $expected_value) {
            $value = $config[$key];
            if (!empty($value) && $glpi_key->isConfigSecured('plugin:carbon', $key)) {
                $value = $glpi_key->decrypt($config[$key]);
            }
            $this->assertEquals($expected_value, $value, "configuration key $key mismatch");
        }
    }

    public function runSqlFile($path)
    {
        global $DB;

        $script = fopen($path, 'r');
        if (!$script) {
            return false;
        }
        $sql_query = @fread(
            $script,
            @filesize($path)
        ) . "\n";
        $sql_query = html_entity_decode($sql_query, ENT_COMPAT, 'UTF-8');

        $sql_query = $DB->removeSqlRemarks($sql_query);
        $queries = preg_split('/;\s*$/m', $sql_query);

        foreach ($queries as $query) {
            $query = trim($query);
            if ($query != '') {
                if (!$DB->doQuery($query)) {
                    return false;
                }
            }
        }

        return true;
    }
}
