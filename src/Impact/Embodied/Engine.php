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

use RuntimeException;
use CommonDBTM;
use CommonGLPI;
use DBmysql;
use GlpiPlugin\Carbon\AbstractModel;
use GlpiPlugin\Carbon\Config;
use GlpiPlugin\Carbon\DataSource\Lca\Boaviztapi\Client;
use GlpiPlugin\Carbon\DataSource\RestApiClient;
use GlpiPlugin\Carbon\DataTracking\AbstractTracked;
use GlpiPlugin\Carbon\Impact\Embodied\Boavizta\AbstractAsset;
use GlpiPlugin\Carbon\Impact\Type;

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
     * @param CommonDBTM $item item to analyze
     * @return EmbodiedImpactInterface|null an instance if an embodied impact calculation object or null on error
     */
    public static function getEngineFromItemtype(CommonDBTM $item): ?EmbodiedImpactInterface
    {
        $itemtype = get_class($item);

        if (self::hasModelData($item)) {
            return self::getInternalEngineFromItemtype($item);
        }

        $embodied_impact_namespace = Config::getEmbodiedImpactEngine();
        $embodied_impact_class = $embodied_impact_namespace . '\\' . $itemtype;
        $must_implement = AbstractEmbodiedImpact::class;
        if (!class_exists($embodied_impact_class) || !is_subclass_of($embodied_impact_class, $must_implement)) {
            return self::getInternalEngineFromItemtype($item);
        }

        /** @var AbstractEmbodiedImpact $embodied_impact */
        $embodied_impact = new $embodied_impact_class($item);
        try {
            return self::configureEngine($embodied_impact);
        } catch (RuntimeException $e) {
            // If the engine cannot be configured, it is not usable
            return null;
        }
    }

    /**
     * Get an instance of the internal engine to calcilate impacts for the given itemtype
     * This is a fallback engine
     *
     * @param CommonDBTM $item item to analyze
     * @return ?EmbodiedImpactInterface
     */
    public static function getInternalEngineFromItemtype(CommonDBTM $item): ?EmbodiedImpactInterface
    {
        $itemtype = get_class($item);
        $embodied_impact_class = 'GlpiPlugin\\Carbon\\Impact\\Embodied\Internal' . '\\' . $itemtype;
        if (!class_exists($embodied_impact_class) || !is_subclass_of($embodied_impact_class, AbstractEmbodiedImpact::class)) {
            return null;
        }
        $embodied_impact = new $embodied_impact_class($item);
        return $embodied_impact;
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
                /** @var AbstractAsset $engine  */
                $engine->setClient(new Client(new RestApiClient()));
        }

        return $engine;
    }

    /**
     * Check if the asset has a model specific dmeodied impact data
     *
     * @param CommonDBTM $item
     * @return bool
     */
    private static function hasModelData(CommonDBTM $item): bool
    {
        /** @var DBmysql $DB */
        global $DB;

        $itemtype = get_class($item);
        $glpi_model_class = $itemtype . 'Model';
        $glpi_model_class_fk = getForeignKeyFieldForItemType($glpi_model_class);
        /**
         * @var class-string<AbstractModel> $model_class
         */
        $model_class = 'GlpiPlugin\\Carbon\\' . $glpi_model_class;
        $model_table = getTableForItemType($model_class);
        $glpi_model_id = $item->fields[$glpi_model_class_fk];
        $crit = [
            $glpi_model_class_fk => $glpi_model_id,
        ];
        $types = Type::getImpactTypes();
        foreach ($types as $key => $type) {
            if (!$DB->fieldExists($model_table, $type)) {
                continue;
            }
            $crit['OR'][] = [
                $type . '_quality' => ['<>', AbstractTracked::DATA_QUALITY_UNSET_VALUE],
            ];
        }
        $model = new $model_class();
        return $model->getFromDBByCrit($crit);
    }
}
