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

use Glpi\Plugin\Hooks;

/** @var array $PLUGIN_HOOKS */
global $PLUGIN_HOOKS;

$PLUGIN_HOOKS[Hooks::SECURED_CONFIGS]['carbon'] = [
    'electricitymap_api_key',
    'co2signal_api_key'
];

$current_config = Config::getConfigurationValues('plugin:carbon');
$config_entries = [
    'electricitymap_api_key' => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
    'demo'                   => 1,
    'impact_engine'          => 'Boavizta',
    'boaviztapi_base_url'    => '',
    'geocoding_enabled'      => '0',
];
foreach ($config_entries as $key => $value) {
    if (!isset($current_config[$key])) {
        Config::setConfigurationValues('plugin:carbon', [$key => $value]);
    }
}
