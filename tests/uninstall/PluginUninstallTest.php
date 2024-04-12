<?php

namespace GlpiPlugin\Carbon\Tests;

use Plugin;

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
        foreach($result as $data) {
           $tables[] = array_pop($row);
        }
        $this->AssertEquals(0, count($tables), "not deleted tables \n" . json_encode($tables, JSON_PRETTY_PRINT));
    }
}
