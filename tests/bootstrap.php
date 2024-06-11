<?php
// fix empty CFG_GLPI on boostrap; see https://github.com/sebastianbergmann/phpunit/issues/325
global $CFG_GLPI, $PLUGIN_HOOKS, $_CFG_GLPI;

define('TEST_PLUGIN_NAME', 'carbon');

if (!$glpiConfigDir = getenv('TEST_GLPI_CONFIG_DIR')) {
   fwrite(STDOUT, "Environment var TEST_GLPI_CONFIG_DIR is not set" . PHP_EOL);
   $glpiConfigDir = 'tests/config';
}

define('GLPI_ROOT', realpath(__DIR__ . '/../../../'));
define("GLPI_CONFIG_DIR", GLPI_ROOT . "/$glpiConfigDir");
fwrite(STDOUT, "GLPI config path: " . GLPI_CONFIG_DIR . PHP_EOL);
fwrite(STDOUT, "checking config file " . GLPI_CONFIG_DIR . '/config_db.php' . PHP_EOL);
if (!file_exists(GLPI_CONFIG_DIR . '/config_db.php')) {
   echo GLPI_ROOT . "/$glpiConfigDir/config_db.php missing. Was GLPI successfully initialized ?" . PHP_EOL;
   exit(1);
}
unset($glpiConfigDir);

define('GLPI_LOG_DIR', __DIR__ . '/logs');
@mkdir(GLPI_LOG_DIR);

ini_set('session.use_cookies', 0); //disable session cookies
require_once GLPI_ROOT . "/inc/includes.php";

loadDataset();

/**
 * Load fixtures shared among all test cases of all test suites
 *
 * STDOUt is used to output messages to prevent header already sent errors
 * when GLPI initializes a session
 *
 * @return void
 */
function loadDataset()
{
   global $DB, $GLPI_CACHE;

   $version = '1.0.0';

   if (!Plugin::isPluginActive(TEST_PLUGIN_NAME)) {
      // Plugin not activated yet
      return;
   }

   $conf = Config::getConfigurationValue('carbon:test_dataset', 'version');
   if ($conf !== null && $conf == $version) {
      fwrite(STDOUT, sprintf(PHP_EOL . "Plugin dataset version %s already loaded" . PHP_EOL, $conf));
      return;
   }

   fwrite(STDOUT, sprintf(PHP_EOL . "Loading GLPI dataset version %s" . PHP_EOL, $version));

   $DB->beginTransaction();

   if (!$DB->runFile(__DIR__ . '/fixtures/carbon_intensity.sql')) {
      fwrite(STDOUT, sprintf('Failed to load carbon intensity dataset' . PHP_EOL));
      exit(1);
   }

   $DB->commit();
   $GLPI_CACHE->clear();

   Config::setConfigurationValues('carbon:test_dataset', ['version' => $version]);
}