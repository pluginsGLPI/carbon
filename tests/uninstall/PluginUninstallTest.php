<?php

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
    public function testUninstallPlugin() {
        global $DB;

        $pluginName = TEST_PLUGIN_NAME;

        $plugin = new Plugin();
        $plugin->getFromDBbyDir($pluginName);

        // Uninstall the plugin
        $log = '';
        ob_start(function($in) use (&$log) {
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
        foreach($result as $row) {
           $tables[] = array_pop($row);
        }
        $this->AssertEquals(0, count($tables), "not deleted tables \n" . json_encode($tables, JSON_PRETTY_PRINT));

        $this->checkConfig();
        // $this->checkRequestType();
        $this->checkAutomaticAction();
        // $this->checkDashboard();
        $this->checkRights();
        $this->checkDisplayPrefs();
    }

    public function checkAutomaticAction()
    {
        $cronTask = new CronTask();
        $cronTask->getFromDBByCrit([
            'itemtype' => ComputerPower::class,
            'name'     => 'ComputePowersTask',
        ]);
        $this->assertTrue($cronTask->isNewItem());

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

    private function checkRights() {
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
