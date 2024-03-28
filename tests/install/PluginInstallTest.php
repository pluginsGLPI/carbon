<?php

namespace GlpiPlugin\Carbon\Tests;

use Session;
use Auth;
use Toolbox;
use DB;
use Config;
use Html;
use User;
use Profile;
use Plugin;

class PluginInstallTest extends CommonTestCase
{

    public function setUp(): void
    {
        //   parent::setUp();
        //   self::setupGLPIFramework();
        self::login('glpi', 'glpi', true);
    }

    public function testInstallPlugin()
    {
        global $DB;

        $pluginName = TEST_PLUGIN_NAME;

        $this->setupGLPIFramework();
        $this->assertTrue($DB->connected);

        //Drop plugin configuration if exists
        $config = new Config;
        $config->deleteByCriteria(['context' => $pluginName]);

        // Drop tables of the plugin if they exist
        $result = $DB->listTables('glpi_plugin_' . $pluginName . '_%');
        foreach($result as $data) {
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
        $this->assertTrue($plugin->isActivated($pluginName), 'Cannot enable the plugin: ' . $messages);

        // $this->checkConfig();
        // $this->checkRequestType();
        // $this->checkPluginName();
        // $this->checkAutomaticAction();
        // $this->checkDashboard();
    }

    public function testConfigurationExists()
    {
        $config = Config::getConfigurationValues(TEST_PLUGIN_NAME);
        $expected = [];
        $diff = array_diff_key(array_flip($expected), $config);
        $this->assertEquals(0, count($diff));

        return $config;
    }
}
