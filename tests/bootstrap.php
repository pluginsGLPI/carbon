<?php
// fix empty CFG_GLPI on boostrap; see https://github.com/sebastianbergmann/phpunit/issues/325
global $CFG_GLPI, $PLUGIN_HOOKS, $_CFG_GLPI;

define('TEST_PLUGIN_NAME', 'carbon');

class UnitTestAutoload
{

   public static function register() {
      spl_autoload_register(array('UnitTestAutoload', 'autoload'));
   }

   public static function autoload($className) {
      $file = __DIR__ . "/src/$className.php";
      if (is_readable($file) && is_file($file)) {
         include_once(__DIR__ . "/src/$className.php");
         return true;
      }
      return false;
   }
}

if (!$glpiConfigDir = getenv('TEST_GLPI_CONFIG_DIR')) {
   echo "Environment var TEST_GLPI_CONFIG_DIR is not set" . PHP_EOL;
   exit(1);
}


UnitTestAutoload::register();

define('GLPI_ROOT', realpath(__DIR__ . '/../../../'));
define("GLPI_CONFIG_DIR", GLPI_ROOT . "/$glpiConfigDir");
if (!file_exists(GLPI_CONFIG_DIR . '/config_db.php')) {
   echo GLPI_ROOT . "/$glpiConfigDir/config_db.php missing. Did GLPI successfully initialized ?\n";
   exit(1);
}
unset($glpiConfigDir);

define('GLPI_LOG_DIR', __DIR__ . '/logs');
@mkdir(GLPI_LOG_DIR);

include (GLPI_ROOT . "/inc/includes.php");
