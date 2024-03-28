<?php

namespace GlpiPlugin\Carbon;

use PHPUnit\Framework\TestCase;
use Session;
use Auth;
use Toolbox;
use DB;
use Config;
use Html;
use User;
use Profile;
use Plugin;

class PluginInstallTest extends TestCase
{

    /** @var integer $debugMode save state of GLPI debug mode */
    private $debugMode = null;

    public function setUp(): void
    {
        //   parent::setUp();
        //   self::setupGLPIFramework();
        self::login('glpi', 'glpi', true);
    }

    protected function disableDebug()
    {
        $this->debugMode = Session::DEBUG_MODE;
        if (isset($_SESSION['glpi_use_mode'])) {
            $this->debugMode = $_SESSION['glpi_use_mode'];
        }
        Toolbox::setDebugMode(Session::NORMAL_MODE);
    }

    protected function restoreDebug()
    {
        Toolbox::setDebugMode($this->debugMode);
    }

    protected function resetGLPILogs()
    {
        // Reset error logs
        file_put_contents(GLPI_LOG_DIR . "/sql-errors.log", '');
        file_put_contents(GLPI_LOG_DIR . "/php-errors.log", '');
    }

    protected function setupGLPIFramework()
    {
        global $DB, $LOADED_PLUGINS, $AJAX_INCLUDE, $PLUGINS_INCLUDED;

        if (session_status() == PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        $LOADED_PLUGINS = null;
        $PLUGINS_INCLUDED = null;
        $AJAX_INCLUDE = null;
        $_SESSION = [];
        if (is_readable(GLPI_ROOT . "/config/config.php")) {
            $configFile = "/config/config.php";
        } else {
            $configFile = "/inc/config.php";
        }
        include(GLPI_ROOT . $configFile);
        require(GLPI_ROOT . "/inc/includes.php");
        //\Toolbox::setDebugMode(Session::DEBUG_MODE);

        $DB = new DB();

        // Security of PHP_SELF
        $_SERVER['PHP_SELF'] = Html::cleanParametersURL($_SERVER['PHP_SELF']);

        if (session_status() == PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        ini_set('session.use_cookies', 0); //disable session cookies
        session_start();
        $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
    }

    protected function login($name, $password, $noauto = false)
    {
        Session::start();
        $auth = new Auth();
        $this->disableDebug();
        $result = $auth->login($name, $password, $noauto);
        $this->restoreDebug();
        $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
        $this->setupGLPIFramework();

        return $result;
    }

    protected function setupGLPI()
    {
        global $CFG_GLPI;
        $settings = [
            'use_mailing' => '1',
            'enable_api'  => '1',
            'enable_api_login_credentials'  => '1',
            'enable_api_login_external_token'  => '1',
        ];
        config::setConfigurationValues('core', $settings);

        $CFG_GLPI = $settings + $CFG_GLPI;
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
