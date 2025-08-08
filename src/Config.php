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

namespace GlpiPlugin\Carbon;

use Config as GlpiConfig;
use CommonDBTM;
use CommonGLPI;
use Computer as GlpiComputer;
use Monitor as GlpiMonitor;
use NetworkEquipment as GlpiNetworkEquipment;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Carbon\Impact\Embodied\Engine;
use Session;

class Config extends GlpiConfig
{
    public static function getTypeName($nb = 0)
    {
        return plugin_carbon_getFriendlyName();
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $tabName = '';
        if (!$withtemplate) {
            if ($item->getType() == GlpiConfig::class) {
                $tabName = self::getTypeName();
            }
        }
        return $tabName;
    }

    /**
     * Undocumented function
     *
     * @param CommonGLPI $item
     * @param integer $tabnum
     * @param integer $withtemplate
     * @return void
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        /** @var CommonDBTM $item */
        if ($item->getType() == GlpiConfig::class) {
            $config = new self();
            $config->showForm($item->getId());
        }
    }

    public function showForm($ID, $options = [])
    {
        $current_config = GlpiConfig::getConfigurationValues('plugin:carbon');
        $current_config['geocoding_enabled'] = $current_config['geocoding_enabled'] ?? '0';
        $canedit        = Session::haveRight(Config::$rightname, UPDATE);

        TemplateRenderer::getInstance()->display('@carbon/config.html.twig', [
            'can_edit'       => $canedit,
            'current_config' => $current_config,
            'impact_engines' => Engine::getAvailableBackends(),
            'action'         => (isset($options['plugin_config']) ? Config::getFormURL() : GlpiConfig::getFormURL()),
        ]);

        return true;
    }

    /**
     * Prepare input for configuration update
     *
     * @param array $input
     * @return array
     */
    public static function configUpdate(array $input): array
    {
        // Prevent erasing protected fields
        // When set but empty, don't update them
        $protected_fields = [
            'electricitymap_api_key',
        ];
        foreach ($protected_fields as $field) {
            if (isset($input[$field]) && empty($input[$field])) {
                unset($input[$field]);
            }
        }

        //Test Boavizta URL by acquiring zones
        if (isset($input['boaviztapi_base_url']) && strlen($input['boaviztapi_base_url']) > 0) {
            $old_url = GlpiConfig::getConfigurationValue('plugin:carbon', 'boaviztapi_base_url');
            if ($old_url != $input['boaviztapi_base_url']) {
                $boavizta = new DataSource\Boaviztapi(new DataSource\RestApiClient(), $input['boaviztapi_base_url']);
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

    /**
     * Get an array of supported assets
     *
     * @return array
     */
    public static function getSupportedAssets(): array
    {
        return [
            GlpiComputer::class,
            GlpiMonitor::class,
            GlpiNetworkEquipment::class,
            // Printer::class,
            // Phone::class
        ];
    }

    /**
     * Get the namespace of the active embodied impact engine
     *
     * @return string
     */
    public static function getEmbodiedImpactEngine(): string
    {
        $default_engine = 'Boavizta';
        $engine = GlpiConfig::getConfigurationValue('plugin:carbon', 'impact_engines');
        if ($engine === null || $engine === '') {
            GlpiConfig::setConfigurationValues('plugin:carbon', ['impact_engines' => $default_engine]);
            $engine = $default_engine;
        }

        return __NAMESPACE__ . '\\Impact\\Embodied\\' . $engine;
    }

    /**
     * Get the namespace of the active usage impact engine
     *
     * @return string
     */
    public static function getUsageImpactEngine(): string
    {
        $default_engine = 'Boavizta';
        $engine = GlpiConfig::getConfigurationValue('plugin:carbon', 'impact_engines');
        if ($engine === null || $engine === '') {
            GlpiConfig::setConfigurationValues('plugin:carbon', ['impact_engines' => $default_engine]);
            $engine = $default_engine;
        }

        return __NAMESPACE__ . '\\Impact\\Usage\\' . $engine;
    }

    /**
     * Get demo mode status
     *
     * @return boolean true if demo mode enabled
     */
    public static function isDemoMode(): bool
    {
        $demo_mode = GlpiConfig::getConfigurationValue('plugin:carbon', 'demo');

        return $demo_mode != 0;
    }

    /**
     * Disable demo mode
     *
     * @return void
     */
    public static function exitDemoMode()
    {
        GlpiConfig::deleteConfigurationValues('plugin:carbon', ['demo']);
    }
}
