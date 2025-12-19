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

namespace GlpiPlugin\Carbon\DataSource\Lca\Boaviztapi;

use GlpiPlugin\Carbon\DataSource\RestApiClient;
use Session;
use GlpiPlugin\Carbon\Config as PluginConfig;
use GlpiPlugin\Carbon\DataSource\ConfigInterface;

class Config implements ConfigInterface
{
    public const ENV_BOAVIZTAPI_BASE_URL = 'GLPI_PLUGIN_CARBON_BOAVIZTAPI_BASE_URL';

    public static function getSecuredConfigs(): array
    {
        return [];
    }

    public function getConfigTemplate(): string
    {
        $hide_boaviztapi_base_url = (getenv(self::ENV_BOAVIZTAPI_BASE_URL) !== false);
        $commercial_url = 'https://boavizta.org/';
        $twig = <<<TWIG
        {% import "components/form/fields_macros.html.twig" as fields %}

        {{ fields.largeTitle(
            __('Boavizta', 'carbon'),
            'fas fa-gears'
        ) }}

            <a target="_blank" href="$commercial_url" ><i class="fa-solid fa-globe"></i>&nbsp;About</a>

TWIG;
        if (!$hide_boaviztapi_base_url) {
            $twig .= <<<TWIG
            {{ fields.textField(
                'boaviztapi_base_url',
                current_config['boaviztapi_base_url'],
                __('Base URL to the Boaviztapi instance', 'carbon')
            ) }}
TWIG;
        }
        $twig .= <<<TWIG
        <div>
            <p>{{ __('Geocoding converts a location into a ISO 3166 (3 letters) code. Boavizta needs this to determine usage impacts of assets. This feature sends the address stored in a location to nominatim.org service. If this is an issue, you can disable it below, and fill the coutry code manually.', 'carbon') }}</p>
        </div>

        {{ fields.checkboxField(
            'geocoding_enabled',
            current_config['geocoding_enabled'],
            __('Enable geocoding', 'carbon')
        ) }}
TWIG;

        return $twig;
    }

    public function configUpdate(array $input): array
    {
        if (isset($input['boaviztapi_base_url']) && strlen($input['boaviztapi_base_url']) > 0) {
            $old_url = PluginConfig::getPluginConfigurationValue('boaviztapi_base_url');
            if ($old_url != $input['boaviztapi_base_url']) {
                $boavizta = new Client(new RestApiClient(), $input['boaviztapi_base_url']);
                $zones = [];
                try {
                    $zones = $boavizta->queryZones();
                } catch (\Exception $e) {
                    unset($input['boaviztapi_base_url']);
                    Session::addMessageAfterRedirect(__('Invalid Boavizta API URL', 'carbon'), false, ERROR);
                }
                if (count($zones) > 0) {
                    // Create the source if it does not exists already
                    if ($boavizta->createSource()) {
                        // Save zones into database
                        $boavizta->saveZones($zones);
                    }
                    Session::addMessageAfterRedirect(__('Connection to Boavizta API established', 'carbon'), false, INFO);
                }
            }
        }

        return $input;
    }

    public static function getConfigurationValue(string $name)
    {
        if ($name === 'boaviztapi_base_url') {
            $value = getenv(self::ENV_BOAVIZTAPI_BASE_URL);
            if ($value !== false) {
                return $value;
            }
        }

        return PluginConfig::getPluginConfigurationValue($name);
    }
}
