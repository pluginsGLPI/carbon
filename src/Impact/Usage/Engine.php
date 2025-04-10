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

namespace GlpiPlugin\Carbon\Impact\Usage;

use CommonGLPI;
use GlpiPlugin\Carbon\Config;
use GlpiPlugin\Carbon\DataSource\Boaviztapi;
use GlpiPlugin\Carbon\DataSource\RestApiClient;

class Engine extends CommonGLPI
{
    public static function getAvailableBackends(): array
    {
        return [
            // 'Internal' => __('Internal', 'carbon'),
            'Boavizta' => __('Boavizta', 'carbon'),
            // 'NumEcoVal' => __('NumEcoVal', 'carbon'),
            // 'Resilio' => __('Resilio', 'carbon'),
        ];
    }

    /**
     * Get an instance of the engine to calculate imapcts for the given itemtype
     *
     * Returns null if no engine found
     *
     * @param string $itemtype itemtype of assets to analyze
     * @return AbstractUsageImpact|null an instance if an embodied impact calculation object or null on error
     */
    public static function getEngineFromItemtype(string $itemtype): ?AbstractUsageImpact
    {
        $usage_impact_namespace = Config::getUsageImpactEngine();
        $usage_impact_class = $usage_impact_namespace . '\\' . $itemtype;
        if (!class_exists($usage_impact_class) || !is_subclass_of($usage_impact_class, AbstractUsageImpact::class)) {
            return null;
        }

        $usage_impact = new $usage_impact_class();
        try {
            return self::configureEngine($usage_impact);
        } catch (\RuntimeException $e) {
            return null;
        }
    }

    public static function getEngine(string $engine_class): ?AbstractUsageImpact
    {
        if (!is_subclass_of($engine_class, AbstractUsageImpact::class)) {
            return null;
        }
        $embodied_impact = new $engine_class();

        try {
            return self::configureEngine($embodied_impact);
        } catch (\RuntimeException $e) {
            return null;
        }
    }

    /**
     * Configure the engine depending on its specificities
     *
     * @param AbstractUsageImpact $engine the engine to configure
     * @return AbstractUsageImpact the configured engine
     */
    protected static function configureEngine(AbstractUsageImpact $engine): AbstractUsageImpact
    {
        $embodied_impact_namespace = explode('\\', get_class($engine));
        switch (array_slice($embodied_impact_namespace, -2, 1)[0]) {
            case 'Boavizta':
                /** @var Boavizta\AbstractAsset $engine  */
                $engine->setClient(new Boaviztapi(new RestApiClient()));
        }

        return $engine;
    }
}
