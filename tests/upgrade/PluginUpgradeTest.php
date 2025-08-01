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

        // $this->markTestSkipped('There is no upgrade to test yet');

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

        $DB->clearSchemaCache();
        ob_start();
        $success = plugin_carbon_install();
        $install_output = ob_get_clean();
        $this->assertTrue($success, 'Failed to install plugin', $install_output);
        // Ignore SQL warnings. We must rely on schema comparison to detect errors
        // which impact plugin upgrade
        file_put_contents(GLPI_LOG_DIR . "/sql-errors.log", '');
        $this->assertTrue($plugin->isInstalled($plugin_name));
    }

    protected function checkConfig()
    {
        $plugin_path = Plugin::getPhpDir(TEST_PLUGIN_NAME, true);
        require_once($plugin_path . '/setup.php');

        $expected = [
            'electricitymap_api_key'             => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
            'impact_engine'                      => 'Boavizta',
            'boaviztapi_base_url'                => '',
            'geocoding_enabled'                  => '0',
            'RTE_zone_setup_complete'            => '0',
            'ElectricityMap_zone_setup_complete' => '0',
            'demo'                               => '0',
            'dbversion'                          => PLUGIN_CARBON_SCHEMA_VERSION,
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
