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
use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;
use Session;

class Config extends GlpiConfig
{
    public static function getTypeName($nb = 0)
    {
        return plugin_carbon_getFriendlyName();
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        $tabNames = [];
        if (!$withtemplate) {
            if ($item->getType() == GlpiConfig::class) {
                $tabNames[] = self::getTypeName();
            }
        }
        return $tabNames;
    }

    /**
     * Undocumented function
     *
     * @param CommonGLPI $item
     * @param integer $tabnum
     * @param integer $withtemplate
     * @return void
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        /** @var CommonDBTM $item */
        if ($item->getType() == GlpiConfig::class) {
            $config = new self();
            $config->showForm($item->getId());
        }
    }

    public function showForm($ID, $options = [])
    {
        $current_config = GlpiConfig::getConfigurationValues('plugin:carbon');
        $canedit        = Session::haveRight(Config::$rightname, UPDATE);

        TemplateRenderer::getInstance()->display('@carbon/config.html.twig', [
            'can_edit'       => $canedit,
            'current_config' => $current_config,
            'action'         => (isset($options['plugin_config']) ? Config::getFormURL() : GlpiConfig::getFormURL()),
        ]);
    }

    /**
     * Prepare input for configuration update
     *
     * @param array $input
     * @return array
     */
    public static function configUpdate(array $input): array
    {
        $protected_fields = [
            'electricitymap_api_key',
            'co2signal_api_key'
        ];
        foreach ($protected_fields as $field) {
            if (isset($input[$field]) && empty($input[$field])) {
                unset($input[$field]);
            }
        }

        return $input;
    }

}
