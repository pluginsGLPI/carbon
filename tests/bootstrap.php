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

use GlpiPlugin\Carbon\Tests\GlobalFixture;

// fix empty CFG_GLPI on boostrap; see https://github.com/sebastianbergmann/phpunit/issues/325
global $CFG_GLPI, $PLUGIN_HOOKS;

define('TEST_PLUGIN_NAME', 'carbon');

if (!$glpiConfigDir = getenv('TEST_GLPI_CONFIG_DIR')) {
    fwrite(STDOUT, "Environment var TEST_GLPI_CONFIG_DIR is not set, using tests/config in GLPI directory" . PHP_EOL);
    $glpiConfigDir = 'tests/config';
}

define('GLPI_ROOT', realpath(__DIR__ . '/../../../'));
fwrite(STDOUT, "GLPI config path: " . $glpiConfigDir . PHP_EOL);
fwrite(STDOUT, "checking config file " . $glpiConfigDir . '/config_db.php' . PHP_EOL);
if (!file_exists(GLPI_ROOT . '/' . $glpiConfigDir . '/config_db.php')) {
    fwrite(STDERR, GLPI_ROOT . "/$glpiConfigDir/config_db.php missing. Faling back to standard config path" . PHP_EOL);
    $glpiConfigDir = 'config';
    if (!file_exists(GLPI_ROOT . '/config/config_db.php')) {
        echo GLPI_ROOT . "/config/config_db.php missing" . PHP_EOL;
        echo "No config file found in GLPI directory. Please run GLPI install first." . PHP_EOL;
        exit(1);
    }
}
define("GLPI_CONFIG_DIR", GLPI_ROOT . "/$glpiConfigDir");
unset($glpiConfigDir);

define('GLPI_LOG_DIR', __DIR__ . '/logs');
if (!file_exists(GLPI_LOG_DIR)) {
    if (!mkdir(GLPI_LOG_DIR)) {
        echo "Failed to create log directory " . GLPI_LOG_DIR . PHP_EOL;
        exit(1);
    }
}

ini_set('session.use_cookies', 0); //disable session cookies
require_once GLPI_ROOT . "/inc/includes.php";

define('PLUGIN_CARBON_TEST_FAKE_SOURCE_NAME', 'Fake source');
define('PLUGIN_CARBON_TEST_FAKE_ZONE_NAME', 'Fake zone');

GlobalFixture::loadDataset();
