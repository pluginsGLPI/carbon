<?php

namespace GlpiPlugin\Carbon\Tests;

use PHPUnit\Framework\TestCase;
use Auth;
use CommonDBTM;
use Config;
use DB;
use Entity;
use Html;
use Session;
use Ticket;
use Toolbox;
use User;

class CommonTestCase extends TestCase
{
    /** @var integer $debugMode save state of GLPI debug mode */
    private $debugMode = null;

    protected $str = null;

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

    /**
     * Get a unique random string
     */
    protected function getUniqueString()
    {
        if (is_null($this->str)) {
            return $this->str = uniqid('str');
        }
        return $this->str .= 'x';
    }

    /**
     * Create an item of the given itemtype
     *
     * @param string $itemtype itemtype to create
     * @param array $input
     * @return CommonDBTM
     */
    protected function getItem(string $itemtype, array $input = []): CommonDBTM
    {
        /** @var CommonDBTM */
        $item = new $itemtype();

        $this->handleDeprecations($itemtype, $input);

        // assign entity if not already set
        if ($item->isEntityAssign()) {
            $entity = 0;
            if (Session::getLoginUserID(true)) {
                $entity = Session::getActiveEntity();
            }
            if (!isset($input[Entity::getForeignKeyField()])) {
                $input[Entity::getForeignKeyField()] = $entity;
            }
        }

        // assign recursiviy if not already set
        if ($item->maybeRecursive()) {
            $recursive = 0;
            if (Session::getLoginUserID(true)) {
                $recursive = Session::getActiveEntity();
            }
            if (!isset($input['is_recursive'])) {
                $input['is_recursive'] = $recursive;
            }
        }

        // set random name if not already set
        if (!isset($item->fields['name'])) {
            if (!isset($input['name'])) {
                $input['name'] = $this->getUniqueString();
            }
        }

        $item->add($input);
        $this->assertFalse($item->isNewItem(), $this->getSessionMessage());

        // Reload the item to ensure that all fields are set
        $this->assertTrue($item->getFromDB($item->getID()));

        return $item;
    }

    protected function getSessionMessage() {
        if (isset($_SESSION['MESSAGE_AFTER_REDIRECT'][INFO])
           || isset($_SESSION['MESSAGE_AFTER_REDIRECT'][WARNING])
           || isset($_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR])) {
           return null;
        }

        $messages = '';
        if (isset($_SESSION['MESSAGE_AFTER_REDIRECT'][INFO])) {
           $messages .= implode(' ', $_SESSION['MESSAGE_AFTER_REDIRECT'][INFO]);
        }
        if (isset($_SESSION['MESSAGE_AFTER_REDIRECT'][WARNING])) {
           $messages .= ' ' . implode(' ', $_SESSION['MESSAGE_AFTER_REDIRECT'][WARNING]);
        }
        if (isset($_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR])) {
           $messages .= ' ' . implode(' ', $_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR]);
        }
        return $messages;
     }

    /**
     * Handle deprecations in GLPI
     * Helps to make unit tests without deprecations warnings, accross 2 version of GLPI
     *
     * @param string $itemtype
     * @param array $input
     * @return void
     */
    private function handleDeprecations($itemtype, &$input): void
    {
        switch ($itemtype) {
            case Ticket::class:
                if (version_compare(GLPI_VERSION, '10.1') < 0) {
                    break;
                }
                // in GLPI 10.1
                if (isset($input['users_id_validate'])) {
                    if (!is_array($input['users_id_validate'])) {
                        $input['users_id_validate'] = [$input['users_id_validate']];
                    }
                    $input['_validation_targets'] = [];
                    foreach ($input['users_id_validate'] as $validator_user) {
                        $input['_validation_targets'][] = [
                            'itemtype_target' => User::class,
                            'items_id_target' => $validator_user,
                        ];
                    }
                    unset($input['users_id_validate']);
                }
                break;
        }
    }
}
