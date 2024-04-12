<?php

namespace GlpiPlugin\Carbon\Tests;

use PHPUnit\Framework\TestCase;
use Auth;
use Config;
use DB;
use Html;
use Session;
use Toolbox;

class CommonTestCase extends TestCase
{

    /** @var integer $debugMode save state of GLPI debug mode */
    private $debugMode = null;

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
        Config::setConfigurationValues('core', $settings);

        $CFG_GLPI = $settings + $CFG_GLPI;
    }
}