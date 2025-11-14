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
use GlpiPlugin\Carbon\Impact\Embodied\Boavizta\AbstractAsset;
use GlpiPlugin\Carbon\DataSource\RestApiClientInterface;

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
     * @param ?RestApiClientInterface $client
     * @return EmbodiedImpactInterface|null an instance if an embodied impact calculation object or null on error
     */
    public static function getEngineFromItemtype(string $itemtype, ?RestApiClientInterface $client = null): ?EmbodiedImpactInterface
    {
        $embodied_impact_namespace = Config::getEmbodiedImpactEngine();
        $embodied_impact_class = $embodied_impact_namespace . '\\' . $itemtype;
        $must_implement = AbstractEmbodiedImpact::class;
        if (!class_exists($embodied_impact_class) || !is_subclass_of($embodied_impact_class, $must_implement)) {
            return self::getInternalEngineFromItemtype($itemtype);
        }

        /** @var AbstractEmbodiedImpact $embodied_impact */
        $embodied_impact = new $embodied_impact_class();
        try {
            return self::configureEngine($embodied_impact, $client);
        } catch (\RuntimeException $e) {
            // If the engine cannot be configured, it is not usable
            return null;
        }
    }

    /**
     * Get an instance of the internal engine to calcilate impacts for the given itemtype
     * This is a fallback engine
     *
     * @param string $itemtype
     * @return ?EmbodiedImpactInterface
     */
    public static function getInternalEngineFromItemtype(string $itemtype): ?EmbodiedImpactInterface
    {
        $embodied_impact_class = 'GlpiPlugin\\Carbon\\Impact\\Embodied\Internal' . '\\' . $itemtype;
        if (!class_exists($embodied_impact_class) || !is_subclass_of($embodied_impact_class, AbstractEmbodiedImpact::class)) {
            return null;
        }
        $embodied_impact = new $embodied_impact_class();
        return $embodied_impact;
    }

    /**
     * Configure the engine depending on its specificities
     *
     * @param EmbodiedImpactInterface $engine the engine to configure
     * @param ?RestApiClientInterface $client
     * @return EmbodiedImpactInterface the configured engine
     */
    protected static function configureEngine(EmbodiedImpactInterface $engine, ?RestApiClientInterface $client = null): EmbodiedImpactInterface
    {
        $embodied_impact_namespace = explode('\\', get_class($engine));
        switch (array_slice($embodied_impact_namespace, -2, 1)[0]) {
            case 'Boavizta':
                if ($client === null) {
                    throw new \RuntimeException('A RestApiClientInterface instance is required to configure Boavizta embodied impact engine');
                }
                /** @var AbstractAsset $engine  */
                $client = $client ?? new RestApiClient();
                $engine->setClient(new Boaviztapi($client));
        }

        return $engine;
    }
}
