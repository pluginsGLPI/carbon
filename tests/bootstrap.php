<?php

/**
 * -------------------------------------------------------------------------
 * carbon plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2024 Teclib' and contributors.
 * @license   MIT https://opensource.org/licenses/mit-license.php
 * @link      https://github.com/pluginsGLPI/carbon
 * -------------------------------------------------------------------------
 */

use GlpiPlugin\Carbon\Tests\GlobalFixture;

// fix empty CFG_GLPI on boostrap; see https://github.com/sebastianbergmann/phpunit/issues/325
global $CFG_GLPI, $PLUGIN_HOOKS;

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

define('PLUGIN_CARBON_TEST_FAKE_SOURCE_NAME', 'Fake source');
define('PLUGIN_CARBON_TEST_FAKE_ZONE_NAME', 'Fake zone');

GlobalFixture::loadDataset();
