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
use DBmysql;
use GlpiPlugin\Carbon\DataTracking\TrackedFloat;
use GlpiPlugin\Carbon\Impact\Embodied\AbstractEmbodiedImpact;
use GlpiPlugin\Carbon\Impact\Type;
use Override;

abstract class AbstractAsset extends AbstractEmbodiedImpact
{
    protected static string $itemtype;

    /** @var string $engine Name of the calculation engine */
    protected string $engine = 'Internal';

    /** @var string $engine_version Version of the calculation engine */
    protected static string $engine_version = '1';

    protected ?AbstractEmbodiedImpact $external_embodied_impact_engine = null;

    public function __construct(CommonDBTM $item, ?AbstractEmbodiedImpact $external_embodied_impact_engine)
    {
        parent::__construct($item);
        $this->external_embodied_impact_engine = $external_embodied_impact_engine;
    }

    #[Override]
    public function getVersion(): string
    {
        return self::$engine_version;
    }

    #[Override]
    protected function doEvaluation(): array
    {
        /** @var DBmysql $DB */
        global $DB;

        $impacts = [];

        $glpi_model_itemtype = static::$itemtype . 'Model';
        $glpi_model_table = getTableForItemType($glpi_model_itemtype);
        $glpi_model_fk = getForeignKeyFieldForItemType($glpi_model_itemtype);
        $model_itemtype = 'GlpiPlugin\\Carbon\\' . $glpi_model_itemtype;
        $model = getItemForItemtype($model_itemtype);
        if ($model !== false) {
            $model_table = getTableForItemtype($model_itemtype);
            $model->getFromDBByRequest([
                'INNER JOIN' => [
                    $glpi_model_table => [
                        'ON' => [
                            $glpi_model_table => 'id',
                            $model_table => $glpi_model_fk,
                        ],
                    ],
                ],
                'WHERE' => [
                    $glpi_model_fk => $this->item->fields[$glpi_model_fk],
                ],
            ]);

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
        }
        if ($this->external_embodied_impact_engine !== null) {
            $external_impacts = $this->external_embodied_impact_engine->doEvaluation();
            $this->engine .= ' + ' . $this->external_embodied_impact_engine->getEngineName() . ' ' . $this->external_embodied_impact_engine->getVersion();
            $impacts = array_replace($external_impacts, $impacts);
        }

        return $impacts;
    }
}
