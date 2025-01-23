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

use Config as GlpiConfig;
use GlpiPlugin\Carbon\Dashboard\Widget;
use Glpi\Plugin\Hooks;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\Config;
use GlpiPlugin\Carbon\EnvironmentalImpact;
use GlpiPlugin\Carbon\Profile;
use GlpiPlugin\Carbon\Report;
use ComputerType as GlpiComputerType;
use MonitorType as GlpiMonitorType;
use NetworkEquipmentType as GlpiNetworkEquipmentType;
use Profile as GlpiProfile;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\Dashboard\Grid;
use GlpiPlugin\Carbon\MonitorType;
use GlpiPlugin\Carbon\NetworkEquipmentType;

define('PLUGIN_CARBON_VERSION', '0.0.5');
define('PLUGIN_CARBON_SCHEMA_VERSION', '0.0.5');

// Minimal GLPI version, inclusive
define("PLUGIN_CARBON_MIN_GLPI_VERSION", "10.0.0");
// Maximum GLPI version, exclusive
define("PLUGIN_CARBON_MAX_GLPI_VERSION", "10.1.0");

define('PLUGIN_CARBON_DECIMALS', 3);

// Plugin compatible itemtypes
define('PLUGIN_CARBON_TYPES', [
    Computer::class,
    Monitor::class,
    NetworkEquipment::class,
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
    plugin_carbon_setupHooks();
    plugin_carbon_registerClasses();
}

function plugin_carbon_setupHooks()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS[Hooks::CSRF_COMPLIANT]['carbon'] = true;

    // Secured config
    $PLUGIN_HOOKS[Hooks::SECURED_CONFIGS]['carbon'] = [
        'electricitymap_api_key',
        'co2signal_api_key'
    ];

    // add new cards to the dashboard
    $PLUGIN_HOOKS[Hooks::DASHBOARD_CARDS]['carbon'] = [Grid::class, 'getDashboardCards'];
    $PLUGIN_HOOKS[Hooks::DASHBOARD_TYPES]['carbon'] = [Widget::class, 'WidgetTypes'];

    if (Session::haveRight('config', UPDATE)) {
        $PLUGIN_HOOKS['config_page']['carbon'] = 'front/config.form.php';
    }

    $PLUGIN_HOOKS['menu_toadd']['carbon']['tools'] = [Report::class];
    // $PLUGIN_HOOKS['menu_toadd']['carbon']['admin'] = [CarbonIntensity::class];

    $PLUGIN_HOOKS[Hooks::POST_SHOW_TAB]['carbon'] = 'plugin_carbon_postShowTab';
    foreach (PLUGIN_CARBON_TYPES as $itemtype) {
        $PLUGIN_HOOKS[Hooks::ITEM_ADD]['carbon'][$itemtype] = 'plugin_carbon_hook_add_asset';
        $PLUGIN_HOOKS[Hooks::ITEM_UPDATE]['carbon'][$itemtype] = 'plugin_carbon_hook_update_asset';
    }
    $PLUGIN_HOOKS[Hooks::ITEM_ADD]['carbon'][Location::class] = 'plugin_carbon_hook_add_location';
    $PLUGIN_HOOKS[Hooks::ITEM_UPDATE]['carbon'][Location::class] = 'plugin_carbon_hook_update_location';

    // Add ApexCharts.js library
    $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['carbon'][] = 'dist/bundle.js';

    // Import CSS
    $PLUGIN_HOOKS[Hooks::ADD_CSS]['carbon'][] = 'dist/main.css';

    $PLUGIN_HOOKS['add_default_where']['carbon'] = 'plugin_carbon_add_default_where';

    $PLUGIN_HOOKS['use_massive_action']['carbon'] = 1;
}

function plugin_carbon_registerClasses()
{
    Plugin::registerClass(Config::class, ['addtabon' => GlpiConfig::class]);
    Plugin::registerClass(Profile::class, ['addtabon' => GlpiProfile::class]);
    Plugin::registerClass(ComputerType::class, ['addtabon' => GlpiComputerType::class]);
    Plugin::registerClass(EnvironmentalImpact::class, ['addtabon' => Computer::class]);
    Plugin::registerClass(EnvironmentalImpact::class, ['addtabon' => Monitor::class]);
    Plugin::registerClass(EnvironmentalImpact::class, ['addtabon' => NetworkEquipment::class]);
    Plugin::registerClass(MonitorType::class, ['addtabon' => GlpiMonitorType::class]);
    Plugin::registerClass(NetworkEquipmentType::class, ['addtabon' => GlpiNetworkEquipmentType::class]);
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
 * Check plugin's prerequisites before installation
 *
 * @return boolean
 */
function plugin_carbon_check_prerequisites()
{
    $prerequisitesSuccess = true;

    if (version_compare(GLPI_VERSION, PLUGIN_CARBON_MIN_GLPI_VERSION, 'lt')) {
        echo "This plugin requires GLPI >= " . PLUGIN_CARBON_MIN_GLPI_VERSION . " and GLPI < " . PLUGIN_CARBON_MAX_GLPI_VERSION . "<br>";
        $prerequisitesSuccess = false;
    }

    if (!is_readable(__DIR__ . '/vendor/autoload.php') || !is_file(__DIR__ . '/vendor/autoload.php')) {
        echo "Run composer install --no-dev in the plugin directory<br>";
        $prerequisitesSuccess = false;
    }

    return $prerequisitesSuccess;
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

/**
 * Get friendly name of the plugin, may be used in various places
 *
 * @return string
 */
function plugin_carbon_getFriendlyName(): string
{
    return __('Environmental Impact', 'carbon');
}
