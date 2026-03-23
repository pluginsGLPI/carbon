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

namespace GlpiPlugin\Carbon\Impact\Embodied\Internal;

use CommonDBTM;
use GlpiPlugin\Carbon\DataTracking\TrackedFloat;
use GlpiPlugin\Carbon\Impact\Embodied\AbstractEmbodiedImpact;
use GlpiPlugin\Carbon\Impact\Type;

abstract class AbstractAsset extends AbstractEmbodiedImpact
{
    protected static string $itemtype;

    /** @var string $engine Name of the calculation engine */
    protected string $engine = 'Internal';

    /** @var string $engine_version Version of the calculation engine */
    protected static string $engine_version = '1';

    protected function getVersion(): string
    {
        return self::$engine_version;
    }

    protected function doEvaluation(): array
    {
        /**
         * @template TModel of CommonDBTM
         * @var class-string<TModel>
         */
        $glpi_model_itemtype = static::$itemtype . 'Model';
        $glpi_model_fk = getForeignKeyFieldForItemType($glpi_model_itemtype);
        if ($glpi_model_itemtype::isNewID($this->item->fields[$glpi_model_fk])) {
            return [];
        }

        /** @var CommonDBTM $model */
        $model = getItemForItemtype($glpi_model_itemtype);
        $model->getFromDBByCrit([
            $glpi_model_fk => $this->item->fields[$glpi_model_fk],
        ]);
        if ($model->isNewItem()) {
            return [];
        }

        $impacts = [];
        $types = Type::getImpactTypes();
        foreach ($types as $type) {
            if (!isset($model->fields[$type]) || empty($model->fields[$type])) {
                continue;
            }
            $impacts[Type::getImpactId($type)] = new TrackedFloat(
                $model->fields[$type],
                null,
                $model->fields["{$type}_quality"]
            );
        }

        return $impacts;
    }
}
