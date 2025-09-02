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

use Config as GlpiConfig;
use GlpiPlugin\Carbon\Dashboard\Widget;
use Glpi\Plugin\Hooks;
use GlpiPlugin\Carbon\Config;
use GlpiPlugin\Carbon\UsageInfo;
use GlpiPlugin\Carbon\Profile;
use GlpiPlugin\Carbon\Report;
use Location as GlpiLocation;
use Profile as GlpiProfile;
use GlpiPlugin\Carbon\Dashboard\Grid;

define('PLUGIN_CARBON_VERSION', '1.0.0-dev+glpi11');
define('PLUGIN_CARBON_SCHEMA_VERSION', '1.0.0');

// Minimal GLPI version, inclusive
define("PLUGIN_CARBON_MIN_GLPI_VERSION", "11.0.0-beta");
// Maximum GLPI version, exclusive
define("PLUGIN_CARBON_MAX_GLPI_VERSION", "12.0.0");

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
    /** @var array $CFG_GLPI */
     /** @var array $PLUGIN_HOOKS */
    global $CFG_GLPI, $PLUGIN_HOOKS;

    if (!Plugin::isPluginActive('carbon')) {
        return;
    }

    require_once(__DIR__ . '/vendor/autoload.php');
    plugin_carbon_setupHooks();
    plugin_carbon_registerClasses();

    $CFG_GLPI['javascript']['tools'][strtolower(Report::class)] = ['dashboard'];
}

function plugin_carbon_setupHooks()
{
    /** @var array $PLUGIN_HOOKS */
    global $PLUGIN_HOOKS;

    // Secured config
    $PLUGIN_HOOKS[Hooks::SECURED_CONFIGS]['carbon'] = [
        'electricitymap_api_key',
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
    $PLUGIN_HOOKS[Hooks::POST_ITEM_FORM]['carbon'] = 'plugin_carbon_postItemForm';

    // Actions taken on locations events
    $PLUGIN_HOOKS[Hooks::ITEM_ADD]['carbon'][GlpiLocation::class] = 'plugin_carbon_locationAdd';
    $PLUGIN_HOOKS[Hooks::PRE_ITEM_UPDATE]['carbon'][GlpiLocation::class] = 'plugin_carbon_locationPreUpdate';
    $PLUGIN_HOOKS[Hooks::ITEM_UPDATE]['carbon'][GlpiLocation::class] = 'plugin_carbon_locationUpdate';
    $PLUGIN_HOOKS[Hooks::PRE_ITEM_PURGE]['carbon'][GlpiLocation::class] = 'plugin_carbon_locationPrePurge';
    // Updating profile rights impacts data for itemtype ProfileRight, then we must use PRE_ITEM_* hooks
    $PLUGIN_HOOKS[Hooks::PRE_ITEM_UPDATE]['carbon'][GlpiProfile::class] = 'plugin_carbon_profileUpdate';
    $PLUGIN_HOOKS[Hooks::PRE_ITEM_ADD]['carbon'][GlpiProfile::class] = 'plugin_carbon_profileAdd';

    // Add ApexCharts.js library
    $js_file = 'lib/apexcharts.js';
    /** @phpstan-ignore-next-line */
    if (version_compare(GLPI_VERSION, '11.0', '<')) {
        // For GLPI < 11.0, we need to add resource the old way
        $js_file = 'public/lib/apexcharts.js';
    }
    $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['carbon'][] = $js_file;

    // Import CSS
    $css_file = 'main.css';
    /** @phpstan-ignore-next-line */
    if (version_compare(GLPI_VERSION, '11.0', '<')) {
        // For GLPI < 11.0, we need to add resource the old way
        $css_file = 'css/main.css';
    }
    $PLUGIN_HOOKS[Hooks::ADD_CSS]['carbon'][] = $css_file;

    $PLUGIN_HOOKS['add_default_where']['carbon'] = 'plugin_carbon_add_default_where';

    $PLUGIN_HOOKS['use_massive_action']['carbon'] = 1;
}

function plugin_carbon_registerClasses()
{
    Plugin::registerClass(Config::class, ['addtabon' => GlpiConfig::class]);
    Plugin::registerClass(Profile::class, ['addtabon' => GlpiProfile::class]);

    foreach (PLUGIN_CARBON_TYPES as $itemtype) {
        $item_type_class = 'GlpiPlugin\\Carbon\\' . $itemtype . 'Type';
        $core_type_class = $itemtype . 'Type';
        Plugin::registerClass($item_type_class, ['addtabon' => $core_type_class]);
        Plugin::registerClass(UsageInfo::class, ['addtabon' => $itemtype]);
    }
}

/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_carbon()
{
    $requirements = [
        'name'           => 'Carbon',
        'version'        => PLUGIN_CARBON_VERSION,
        'author'         => '<a href="http://www.teclib.com">Teclib\'</a>',
        'license'        => 'GPLv3',
        'homepage'       => '',
        'requirements'   => [
            'glpi' => [
                'min' => PLUGIN_CARBON_MIN_GLPI_VERSION,
            ]
        ]
    ];

    $dev_version = strpos(PLUGIN_CARBON_VERSION, '-dev') !== false;
    if (!$dev_version) {
        // This is not a development version
        $requirements['requirements']['glpi']['max'] = PLUGIN_CARBON_MAX_GLPI_VERSION;
    }
    return $requirements;
}

/**
 * Check plugin's prerequisites before installation
 *
 * @return boolean
 */
function plugin_carbon_check_prerequisites()
{
    /** @var DBmysql $DB */
    global $DB;

    $prerequisitesSuccess = true;

    /** @phpstan-ignore if.alwaysFalse */
    if (version_compare(GLPI_VERSION, PLUGIN_CARBON_MIN_GLPI_VERSION, 'lt')) {
        echo "This plugin requires GLPI >= " . PLUGIN_CARBON_MIN_GLPI_VERSION . " and GLPI < " . PLUGIN_CARBON_MAX_GLPI_VERSION . "<br>";
        $prerequisitesSuccess = false;
    }

    if (!is_readable(__DIR__ . '/vendor/autoload.php') || !is_file(__DIR__ . '/vendor/autoload.php')) {
        echo "Run composer install --no-dev in the plugin directory<br>";
        $prerequisitesSuccess = false;
    }

    if (getenv('CI') === false) {
        // only when not under test
        $version_string = $DB->getVersion();

        $server  = preg_match('/-MariaDB/', $version_string) ? 'MariaDB' : 'MySQL';
        $version = preg_replace('/^((\d+\.?)+).*$/', '$1', $version_string);
        if ($server === 'MySQL' && version_compare($version, '8.0.0', '<')) {
            echo 'This plugin requires MySQL >= 8.0 or MariaDB >= 10.2<br>';
            $prerequisitesSuccess = false;
        }

        if ($server === 'MariaDB' && version_compare($version, '10.2.0', '<')) {
            echo 'This plugin requires MySQL >= 8.0 or MariaDB >= 10.2<br>';
            $prerequisitesSuccess = false;
        }
    }

    return $prerequisitesSuccess;
}

/**
 * Get the path to the empty SQL schema file
 * @param string $version The version of the schema file to get
 *
 * @return string|null
 */
function plugin_carbon_getSchemaPath(?string $version = null): ?string
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

    // Plugin::getPhpDir may return false
    /** @phpstan-ignore identical.alwaysFalse */
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
