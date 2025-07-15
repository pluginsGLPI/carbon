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
        try {
            return self::configureEngine($embodied_impact);
        } catch (\RuntimeException $e) {
            // If the engine cannot be configured, it is not usable
            return null;
        }
    }

    public static function getEngine(string $engine_class): ?EmbodiedImpactInterface
    {
        if (!is_subclass_of($engine_class, EmbodiedImpactInterface::class)) {
            return null;
        }
        $embodied_impact = new $engine_class();

        try {
            return self::configureEngine($embodied_impact);
        } catch (\RuntimeException $e) {
            // If the engine cannot be configured, it is not usable
            return null;
        }
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
                /** @var Boavizta\AbstractAsset $engine  */
                $engine->setClient(new Boaviztapi(new RestApiClient()));
        }

        return $engine;
    }
}
