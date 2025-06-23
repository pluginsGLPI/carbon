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
     * Get the namespace of the active usage impact engine
     *
     * @return string
     */
    public static function getGwpUsageImpactEngine(): string
    {
        $default_engine = 'Boavizta';
        $engine = GlpiConfig::getConfigurationValue('plugin:carbon', 'impact_engines');
        if ($engine === null || $engine === '') {
            GlpiConfig::setConfigurationValues('plugin:carbon', ['impact_engines' => $default_engine]);
            $engine = $default_engine;
        }

        return __NAMESPACE__ . '\\Impact\\History\\' . $engine;
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
