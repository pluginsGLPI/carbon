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
use CronTask;
use DisplayPreference;
use GlpiPlugin\Carbon\ComputerPower;
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
}
