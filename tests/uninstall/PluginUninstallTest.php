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
use CronTask;
use DisplayPreference;
use Glpi\Dashboard\Dashboard;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\CarbonIntensity;
use Plugin;
use ProfileRight;

class PluginUninstallTest extends CommonTestCase
{
    public function testUninstallPlugin()
    {
        global $DB;

        $pluginName = TEST_PLUGIN_NAME;

        $plugin = new Plugin();
        $plugin->getFromDBbyDir($pluginName);

        // Uninstall the plugin
        $log = '';
        ob_start(function ($in) use (&$log) {
            $log .= $in;
            return '';
        });
        $plugin->uninstall($plugin->getID());
        ob_end_clean();

        // Check the plugin is not installed
        $plugin->getFromDBbyDir(strtolower($pluginName));
        $this->AssertEquals(Plugin::NOTINSTALLED, (int) $plugin->fields['state']);

        // Check all plugin's tables are dropped
        $tables = [];
        $result = $DB->listTables('glpi_plugin_' . $pluginName . '_%');
        foreach ($result as $row) {
            $tables[] = array_pop($row);
        }
        $this->AssertEquals(0, count($tables), "not deleted tables \n" . json_encode($tables, JSON_PRETTY_PRINT));

        $this->checkConfig();
        // $this->checkRequestType();
        $this->checkAutomaticAction();
        // $this->checkDashboard();
        $this->checkRights();
        $this->checkDisplayPrefs();
        $this->checkDashboard();

        Config::deleteConfigurationValues('carbon:test_dataset', ['version']);
    }

    public function checkAutomaticAction()
    {
        $cronTask = new CronTask();
        $cronTask->getFromDBByCrit([
            'itemtype' => CarbonEmission::class,
            'name'     => 'ComputeCarbonEmissionsTask',
        ]);
        $this->assertTrue($cronTask->isNewItem());
    }

    private function checkConfig()
    {
        $config = Config::getConfigurationValues(TEST_PLUGIN_NAME);
        $this->assertArrayNotHasKey('plugin:carbon', $config);
    }

    private function checkRights()
    {
        $profile_right = new ProfileRight();
        $rights = $profile_right->find(['name' => ['LIKE', 'carbon:%']]);

        $this->assertEquals(0, count($rights));
    }

    private function checkDisplayPrefs()
    {
        $displayPreference = new DisplayPreference();
        $preferences = $displayPreference->find(['itemtype' => CarbonIntensity::class, 'users_id' => 0]);

        $this->assertEquals(0, count($preferences));
    }

    private function checkDashboard()
    {
        $dashboard_key = 'plugin_carbon_board';
        $dashboard = new Dashboard();
        $dashboard->getFromDB($dashboard_key);
        $this->assertTrue($dashboard->isNewItem());
    }
}
