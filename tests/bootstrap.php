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

use Glpi\Application\Environment;
use Glpi\Application\ResourcesChecker;
use Glpi\Cache\CacheManager;
use Glpi\Cache\SimpleCache;
use Glpi\Kernel\Kernel;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

define('TEST_PLUGIN_NAME', 'carbon');

define('GLPI_URI', getenv('GLPI_URI') ?: 'http://localhost');

define('TU_USER', '_test_user');
define('TU_PASS', 'PhpUnit_4');

ini_set('session.use_cookies', 0); //disable session cookies

// Check the resources state before trying to be sure that the tests are executed with up-to-date dependencies.
require_once dirname(__DIR__, 3) . '/src/Glpi/Application/ResourcesChecker.php';
(new ResourcesChecker(dirname(__DIR__, 3)))->checkResources();

global $GLPI_CACHE;

require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

$kernel = new Kernel(Environment::TESTING->value);
$kernel->boot();

if (!file_exists(GLPI_CONFIG_DIR . '/config_db.php')) {
    echo("\nConfiguration file for tests not found\n\nrun: php bin/console database:install --env=testing ...\n\n");
    exit(1);
}
if (Update::isUpdateMandatory()) {
    echo 'The GLPI codebase has been updated. The update of the GLPI database is necessary.' . PHP_EOL;
    exit(1);
}

//init cache
if (file_exists(GLPI_CONFIG_DIR . DIRECTORY_SEPARATOR . CacheManager::CONFIG_FILENAME)) {
    // Use configured cache for cache tests
    $cache_manager = new CacheManager();
    $GLPI_CACHE = $cache_manager->getCoreCacheInstance();
} else {
    // Use "in-memory" cache for other tests
    $GLPI_CACHE = new SimpleCache(new ArrayAdapter());
}

// To prevent errors caught by `error` asserter to also generate logs, unregister GLPI error handler.
// Errors that are pushed directly to logs (SQL errors/warnings for instance) will still have to be explicitly
// validated by `$this->has*LogRecord*()` asserters, otherwise it will make test fails.
set_error_handler(null);

define('PLUGIN_CARBON_TEST_FAKE_SOURCE_NAME', 'Fake source');
define('PLUGIN_CARBON_TEST_FAKE_ZONE_NAME', 'Fake zone');
