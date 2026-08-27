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

namespace GlpiPlugin\Carbon\DataSource\CarbonIntensity\ElectricityMaps;

use GlpiPlugin\Carbon\Config as PluginConfig;
use GlpiPlugin\Carbon\DataSource\ConfigInterface;
use Override;

class Config implements ConfigInterface
{
    #[Override]
    public static function getSecuredConfigs(): array
    {
        return [
            'electricitymap_api_key',
        ];
    }

    #[Override]
    public function getConfigTemplate(): string
    {
        $commercial_url = 'https://www.electricitymaps.com/';
        $twig = <<<TWIG
        {% import "components/form/fields_macros.html.twig" as fields %}

        {{ fields.largeTitle(
            __('Electricity maps', 'carbon'),
            'fas fa-gears'
        ) }}

        <a target="_blank" href="$commercial_url" ><i class="fa-solid fa-globe"></i>&nbsp;About</a>

        {{ fields.passwordField(
            'electricitymap_api_key',
            '',
            __('Key for electricitymap.org API', 'carbon')
        ) }}

        {{ fields.checkboxField(
            'electricitymap_free_plan',
            current_config['electricitymap_free_plan'] ?? 0,
            __('Free plan', 'carbon')
        ) }}

        {{ fields.checkboxField(
            'electricitymap_fake_data',
            current_config['electricitymap_fake_data'] ?? 0,
            __('API key is development key (fake data)', 'carbon')
        ) }}
TWIG;

        return $twig;
    }

    #[Override]
    public function configUpdate(array $input): array
    {
        $to_delete = [];
        foreach (self::getSecuredConfigs() as $field) {
            if (isset($input[$field]) && empty($input[$field])) {
                unset($input[$field]);
            }
            if (($input['_blank_' . $field] ?? '') === 'on') {
                $to_delete[] = $field;
            }
        }
        if (count($to_delete)) {
            PluginConfig::deletePluginConfigurationValues($to_delete);
        }

        return $input;
    }

    public static function getConfigurationValue(string $name)
    {
        return PluginConfig::getPluginConfigurationValue($name);
    }
}
