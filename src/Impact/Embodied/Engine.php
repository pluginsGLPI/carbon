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

namespace GlpiPlugin\Carbon\Impact\Embodied;

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
     * @return EmbodiedImpactInterface|null an instance if an embodied impact calculation object or null on error
     */
    public static function getEngineFromItemtype(string $itemtype): ?EmbodiedImpactInterface
    {
        $embodied_impact_namespace = Config::getEmbodiedImpactEngine();
        $embodied_impact_class = $embodied_impact_namespace . '\\' . $itemtype;
        if (!class_exists($embodied_impact_class) || !is_subclass_of($embodied_impact_class, AbstractEmbodiedImpact::class)) {
            return null;
        }

        $embodied_impact = new $embodied_impact_class();
        return self::configureEngine($embodied_impact);
    }

    public static function getEngine(string $engine_class): ?EmbodiedImpactInterface
    {
        if (!is_subclass_of($engine_class, EmbodiedImpactInterface::class)) {
            return null;
        }
        $embodied_impact = new $engine_class();

        return self::configureEngine($embodied_impact);
    }

    /**
     * Configure the engine depending on its specificities
     *
     * @param EmbodiedImpactInterface $engine the engine to configure
     * @return EmbodiedImpactInterface the configured engine
     */
    protected static function configureEngine(EmbodiedImpactInterface $engine): EmbodiedImpactInterface
    {
        $embodied_impact_namespace = explode('\\', get_class($engine));
        switch (array_slice($embodied_impact_namespace, -2, 1)[0]) {
            case 'Boavizta':
                /** @var GlpiPlugin\Carbon\Impact\Embodied\Boavizta\AssetInterface $engine  */
                $engine->setClient(new Boaviztapi(new RestApiClient()));
        }

        return $engine;
    }
}
