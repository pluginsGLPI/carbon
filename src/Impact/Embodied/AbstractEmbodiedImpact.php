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
 * @copyright Copyright (C) 2024 by the carbon plugin team.
 * @license   MIT https://opensource.org/licenses/mit-license.php
 * @link      https://github.com/pluginsGLPI/carbon
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Carbon\Impact\Embodied;

use DBmysql;
use DbUtils;
use CommonDBTM;
use GlpiPlugin\Carbon\DataTracking\AbstractTracked;
use GlpiPlugin\Carbon\EmbodiedImpact;
use InvalidArgumentException;
use Session;
use Toolbox as GlpiToolbox;

abstract class AbstractEmbodiedImpact implements EmbodiedImpactInterface
{
    /** @var string Handled itemtype */
    protected static string $itemtype = '';

    /** @var int maximum number of entries to build */
    protected int $limit = 0;

    /** @var bool Is limit reached when evaluating a batch of assets ? */
    protected bool $limit_reached = false;

    /** @var array of TrackedFloat */
    protected array $impacts = [];

    public function __construct()
    {
        foreach (array_flip($this->getImpactTypes()) as $type) {
            $this->impacts[$type] = null;
        }
    }

    public function getImpactTypes(): array
    {
        return [
            EmbodiedImpactInterface::IMPACT_GWP => 'gwp',
            EmbodiedImpactInterface::IMPACT_ADP => 'adp',
            EmbodiedImpactInterface::IMPACT_PE  => 'pe',
        ];
    }

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
            case EmbodiedImpactInterface::IMPACT_GWP:
                return $short ? 'gCO2eq' : __('grams of carbon dioxyde equivalent', 'carbon');
            case EmbodiedImpactInterface::IMPACT_ADP:
                return $short ? 'gSbeq' : __('grams of antimony equivalent', 'carbon');
            case EmbodiedImpactInterface::IMPACT_PE:
                return $short ? 'J' : __('joules', 'carbon');
        }

        return null;
    }

    public static function getItemtype(): string
    {
        return static::$itemtype;
    }

    public function setLimit(int $limit)
    {
        $this->limit = $limit;
    }

    public function evaluateItems(): int
    {
        /** @var DBmysql $DB */
        global $DB;

        $itemtype = static::$itemtype;
        if ($itemtype === '') {
            throw new \LogicException('Itemtype not set');
        }
        if (!is_subclass_of($itemtype, CommonDBTM::class)) {
            throw new \LogicException('Itemtype does not inherits from ' . CommonDBTM::class);
        }

        $count = 0;

        /**
         * Huge quantity of SQL queries will be executed
         * We NEED to check memory usage to avoid running out of memory
         * @see DbMysql::doQuery()
         */
        $memory_limit = GlpiToolbox::getMemoryLimit() - 8 * 1024 * 1024;
        if ($memory_limit < 0) {
            // May happen in test seems that ini_get("memory_limits") returns
            // enpty string in PHPUnit environment
            $memory_limit = null;
        }

        $iterator = $DB->request($this->getEvaluableQuery(false));
        foreach ($iterator as $row) {
            $count += $this->evaluateItem($row['id']);
            if ($this->limit !== 0 && $count >= $this->limit) {
                $this->limit_reached = true;
                break;
            }
            if ($memory_limit && $memory_limit < memory_get_usage()) {
                // 8 MB memory left, emergency exit
                $this->limit_reached = true;
                break;
            }
            if ($this->limit_reached) {
                break;
            }
        }

        return $count;
    }

    public function evaluateItem(int $id): int
    {
        $itemtype = static::$itemtype;
        $item = $itemtype::getById($id);
        if ($item === false) {
            return 0;
        }
        try {
            $impacts = $this->doEvaluation($item);
        } catch (\RuntimeException $e) {
            return 0;
        }

        if ($impacts === null) {
            // Nothing calculated
            return 0;
        }

        // Find an existing row, if any
        $input = [
            'itemtype' => $itemtype,
            'items_id' => $id,
        ];
        $embodied_impact = new EmbodiedImpact();
        $embodied_impact->getFromDBByCrit($input);
        $impact_types = $this->getImpactTypes();

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
                return 1;
            }
        } else {
            unset($input['itemtype'], $input['items_id']); // prevent updating these columns
            if ($embodied_impact->update(['id' => $embodied_impact->getID()] + $input)) {
                return 1;
            }
        }

        return 0;
    }

    public function getEvaluableQuery(bool $entity_restrict = true, bool $recalculate = false): array
    {
        $itemtype = static::$itemtype;
        $item_table = $itemtype::getTable();
        $embodied_impact_table = EmbodiedImpact::getTable();

        $where = [];
        if (!$recalculate) {
            $where = [EmbodiedImpact::getTableField('id') => null];
        }

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
            ],
            'WHERE' => $where,
        ];

        if ($entity_restrict) {
            $entity_restrict = (new DbUtils())->getEntitiesRestrictCriteria($item_table, '', '', 'auto');
            $request['WHERE'] += $entity_restrict;
        }

        return $request;
    }

    abstract protected function doEvaluation(CommonDBTM $item);
}
