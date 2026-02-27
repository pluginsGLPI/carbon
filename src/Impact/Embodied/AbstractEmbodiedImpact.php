<?php

/**
 * -------------------------------------------------------------------------
 * Carbon plugin for GLPI
 *
 * @copyright Copyright (C) 2024-2025 Teclib' and contributors.
 * @copyright Copyright (C) 2024 by the carbon plugin team.
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

use DBmysql;
use DbUtils;
use CommonDBTM;
use DBmysqlIterator;
use GlpiPlugin\Carbon\DataTracking\AbstractTracked;
use GlpiPlugin\Carbon\DataTracking\TrackedFloat;
use GlpiPlugin\Carbon\EmbodiedImpact;
use GlpiPlugin\Carbon\Impact\Type;
use GuzzleHttp\Exception\ConnectException;
use Session;

abstract class AbstractEmbodiedImpact implements EmbodiedImpactInterface
{
    /** @var CommonDBTM Item to analyze */
    protected CommonDBTM $item;

    /** @var int maximum number of entries to build */
    protected int $limit = 0;

    /** @var bool Is limit reached when evaluating a batch of assets ? */
    protected bool $limit_reached = false;

    /** @var string $engine Name of the calculation engine */
    protected string $engine = 'undefined';

    /** @var string $engine_version Version of the calculation engine */
    protected static string $engine_version = 'unknown';

    /** @var array of TrackedFloat */
    protected array $impacts = [];

    public function __construct(CommonDBTM $item)
    {
        if ($item->isNewItem()) {
            throw new \LogicException("Given item is empty");
        }
        $this->item = $item;
        foreach (array_flip(Type::getImpactTypes()) as $type) {
            $this->impacts[$type] = null;
        }
    }

    abstract protected function getVersion(): string;

    /**
     * Get the unit of an impact
     *
     * @param integer $type
     * @param boolean $short
     * @return string|null
     */
    final public function getUnit(int $type, bool $short = true): ?string
    {
        switch ($type) {
            case Type::IMPACT_GWP:
                return $short ? 'gCO2eq' : __('grams of carbon dioxyde equivalent', 'carbon');
            case Type::IMPACT_ADP:
                return $short ? 'gSbeq' : __('grams of antimony equivalent', 'carbon');
            case Type::IMPACT_PE:
                return $short ? 'J' : __('joules', 'carbon');
        }

        return null;
    }

    public function setLimit(int $limit)
    {
        $this->limit = $limit;
    }

    public static function getItemsToEvaluate(string $itemtype, array $crit = []): DBmysqlIterator
    {
        /** @var DBmysql $DB */
        global $DB;

        $crit[] = [
            'OR' => [
                EmbodiedImpact::getTableField('id') => null,
                EmbodiedImpact::getTableField('recalculate') => 1,
            ]
        ];
        $iterator = $DB->request(self::getEvaluableQuery($itemtype, $crit, false));

        return $iterator;
    }

    public function evaluateItem(): bool
    {
        $itemtype = get_class($this->item);

        try {
            $this->getVersion();
            $impacts = $this->doEvaluation();
        } catch (ConnectException $e) {
            Session::addMessageAfterRedirect(__('Connection to Boavizta failed.', 'carbon'), false, ERROR);
            return false;
        } catch (\RuntimeException $e) {
            Session::addMessageAfterRedirect(__('Embodied impact evaluation falied.', 'carbon'), false, ERROR);
            return false;
        }

        if ($impacts === null || count($impacts) === 0) {
            // Nothing calculated
            return false;
        }

        // Find an existing row, if any
        $input = [
            'itemtype' => $itemtype,
            'items_id' => $this->item->getID(),
        ];
        $embodied_impact = new EmbodiedImpact();
        $embodied_impact->getFromDBByCrit($input);
        $impact_types = Type::getImpactTypes();

        $input['recalculate'] = 0;
        $input['engine'] = $this->engine;
        $input['engine_version'] = self::$engine_version;

        // Prepare inputs for add or update
        foreach ($impacts as $type => $value) {
            $key = $impact_types[$type];
            $key_quality = "{$key}_quality";
            $input[$key] = null;
            $input[$key_quality] = AbstractTracked::DATA_QUALITY_UNSPECIFIED;
            if ($value !== null) {
                /** @var AbstractTracked $value  */
                $input[$key] = $value->getValue();
                $input[$key_quality] = $value->getLowestSource();
            }
        }

        // Add or update the impact for the asset
        if ($embodied_impact->isNewItem()) {
            if ($embodied_impact->add($input) !== false) {
                return true;
            }
        } else {
            unset($input['itemtype'], $input['items_id']); // prevent updating these columns
            if ($embodied_impact->update(['id' => $embodied_impact->getID()] + $input)) {
                return true;
            }
        }

        return false;
    }

    public static function getEvaluableQuery(string $itemtype, array $crit = [], bool $entity_restrict = true): array
    {
        $item_table = getTableForItemType($itemtype);
        $glpi_item_type_table = getTableForItemType($itemtype . 'Type');
        $glpi_item_type_fk = getForeignKeyFieldForTable($glpi_item_type_table);
        $item_type_table = getTableForItemType('GlpiPlugin\\Carbon\\' . $itemtype . 'Type');
        $embodied_impact_table = EmbodiedImpact::getTable();

        // $where = [];
        // if (!$recalculate) {
        //     $where = [EmbodiedImpact::getTableField('id') => null];
        // }

        $crit[] = [
            'OR' => [
                $item_type_table . '.is_ignore' => 0,
                $item_type_table . '.id' => null,
            ]
        ];

        $request = [
            'SELECT' => [
                $itemtype::getTableField('id'),
            ],
            'FROM' => $item_table,
            'LEFT JOIN' => [
                $embodied_impact_table => [
                    'FKEY' => [
                        $embodied_impact_table => 'items_id',
                        $item_table            => 'id',
                        ['AND' =>
                            [
                                EmbodiedImpact::getTableField('itemtype') => $itemtype,
                            ]
                        ],
                    ],
                ],
                $item_type_table => [
                    [
                        'FKEY' => [
                            $item_type_table => $glpi_item_type_fk,
                            $item_table => $glpi_item_type_fk,
                        ],
                    ],
                ],
            ],
            'WHERE' => $crit,
        ];

        if ($entity_restrict) {
            $entity_restrict = (new DbUtils())->getEntitiesRestrictCriteria($item_table, '', '', 'auto');
            $request['WHERE'] += $entity_restrict;
        }

        return $request;
    }

    /**
     * Do the environmental impact evaluation of an asset
     *
     * @return ?array
     */
    abstract protected function doEvaluation(): ?array;

    /**
     * Delete all calculated usage impact for an asset
     *
     * @param CommonDBTM $item
     * @return boolean
     */
    public static function resetForItem(CommonDBTM $item): bool
    {
        $embodied_impact = new EmbodiedImpact();
        return $embodied_impact->deleteByCriteria([
            'itemtype' => get_class($item),
            'items_id' => $item->getID(),
        ]);
    }

    protected function getModelImpacts(CommonDBTM $model): array
    {
        $impacts = [];
        $types = Type::getImpactTypes();
        foreach ($types as $key => $type) {
            if ($model->fields[$type] === null || $model->fields[$type . '_quality'] === AbstractTracked::DATA_QUALITY_UNSET_VALUE) {
                continue;
            }
            $impacts[$key] = new TrackedFloat($model->fields[$type], null, $model->fields[$type . '_quality']);
        };
        return $impacts;
    }
}
