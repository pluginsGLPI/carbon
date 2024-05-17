<?php

/**
 * -------------------------------------------------------------------------
 * carbon plugin for GLPI
 * Copyright (C) 2022 by the carbon Development Team.
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
 *
 * --------------------------------------------------------------------------
 */

use GlpiPlugin\Carbon\Dashboard\Dashboard;
use Glpi\Plugin\Hooks;
use GlpiPlugin\Carbon\Menu;
use GlpiPlugin\Carbon\EnvironnementalImpact;
use ComputerType as GlpiComputerType;
use MonitorType as GlpiMonitorType;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\MonitorType;

define('PLUGIN_CARBON_VERSION', '0.0.1');

// Minimal GLPI version, inclusive
define("PLUGIN_CARBON_MIN_GLPI_VERSION", "10.0.0");
// Maximum GLPI version, exclusive
define("PLUGIN_CARBON_MAX_GLPI_VERSION", "10.1.0");

// Plugin compatible itemtypes
define('PLUGIN_CARBON_TYPES', [
    Computer::class,
    //    Monitor::class,
    //    NetworkEquipment::class,
    //    Phone::class,
    //    Printer::class,
]);

/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_carbon()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS[Hooks::CSRF_COMPLIANT]['carbon'] = true;

    // add new cards to the dashboard
    $PLUGIN_HOOKS[Hooks::DASHBOARD_CARDS]['carbon'] = [Dashboard::class, 'dashboardCards'];

    if (Session::haveRight('config', UPDATE)) {
        $PLUGIN_HOOKS['config_page']['carbon'] = 'front/config.form.php';
    }

    $PLUGIN_HOOKS[Hooks::REDEFINE_MENUS]['carbon'] = [Menu::class, 'hookRedefineMenu'];

    Plugin::registerClass(ComputerType::class, ['addtabon' => GlpiComputerType::class]);
    Plugin::registerClass(EnvironnementalImpact::class, ['addtabon' => Computer::class]);
    // TODO: enable monitor power consumption before enabling UI
    // Plugin::registerClass(MonitorType::class, ['addtabon' => GlpiMonitorType::class]);

    // Add ApexCharts.js library
    $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['carbon'][] = 'dist/bundle.js';

    // Import CSS
    $PLUGIN_HOOKS[Hooks::ADD_CSS]['carbon'][] = 'dist/main.css';
}

/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_carbon()
{
    return [
        'name'           => 'GLPI Carbon',
        'version'        => PLUGIN_CARBON_VERSION,
        'author'         => '<a href="http://www.teclib.com">Teclib\'</a>',
        'license'        => 'MIT',
        'homepage'       => '',
        'requirements'   => [
            'glpi' => [
                'min' => PLUGIN_CARBON_MIN_GLPI_VERSION,
                'max' => PLUGIN_CARBON_MAX_GLPI_VERSION,
            ]
        ]
    ];
}

/**
 * Get the path to the empty SQL schema file
 *
 * @return string|null
 */
function plugin_carbon_getSchemaPath(string $version = null): ?string
{
    $version = $version ?? PLUGIN_CARBON_VERSION;

    // Drop suffixes for alpha, beta, rc versions
    $matches = [];
    preg_match('/^(\d+\.\d+\.\d+)/', $version, $matches);
    $version = $matches[1];

    $matches = [];
    preg_match('/^(\d+\.\d+\.\d+)/', PLUGIN_CARBON_VERSION, $matches);
    $current_version = $matches[1];

    if ($version === $current_version) {
        $schemaPath = Plugin::getPhpDir('carbon') . '/install/mysql/plugin_carbon_empty.sql';
    } else {
        $schemaPath = Plugin::getPhpDir('carbon') . "/install/mysql/plugin_carbon_{$version}_empty.sql";
    }

    if ($schemaPath === false) {
        return null;
    }

    return $schemaPath;
}
