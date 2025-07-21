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
