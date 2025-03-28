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

namespace GlpiPlugin\Carbon\Impact\Usage;

use DBmysql;
use DbUtils;
use CommonDBTM;
use GlpiPlugin\Carbon\DataTracking\AbstractTracked;
use GlpiPlugin\Carbon\UsageImpact;
use GlpiPlugin\Carbon\Impact\Type;
use Location as GlpiLocation;
use Toolbox as GlpiToolbox;

abstract class AbstractUsageImpact implements UsageImpactInterface
{
    /** @var string Handled itemtype */
    protected static string $itemtype = '';

    /** @var int maximum number of entries to build */
    protected int $limit = 0;

    /** @var bool Is limit reached when evaluating a batch of assets ? */
    protected bool $limit_reached = false;

    /** @var string $engine Name of the calculation engine */
    protected string $engine = 'undefined';

    /** @var string $engine_version Version of the calculation engine */
    protected string $engine_version = 'unknown';

    /** @var array of TrackedFloat */
    protected array $impacts = [];

    public function __construct()
    {
        foreach (array_flip(Type::getImpactTypes()) as $type) {
            $this->impacts[$type] = null;
        }
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
            case Type::IMPACT_GWP:
                return $short ? 'gCO2eq' : __('grams of carbon dioxyde equivalent', 'carbon');
            case Type::IMPACT_ADP:
                return $short ? 'gSbeq' : __('grams of antimony equivalent', 'carbon');
            case Type::IMPACT_PE:
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

        /** @var int $attempts_count count of evaluation attempts */
        $attempts_count = 0;
        /** @var int $count count of successfully evaluated assets */
        $count = 0;
        $iterator = $DB->request($this->getEvaluableQuery());
        foreach ($iterator as $row) {
            if ($this->evaluateItem($row['id'])) {
                $count++;
            }
            $attempts_count++;
            if ($this->limit !== 0 && $attempts_count >= $this->limit) {
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

    public function evaluateItem(int $id): bool
    {
        $itemtype = static::$itemtype;
        $item = $itemtype::getById($id);
        if ($item === false) {
            return false;
        }

        try {
            $impacts = $this->doEvaluation($item);
        } catch (\RuntimeException $e) {
            return false;
        }

        if ($impacts === null) {
            // Nothing calculated
            return false;
        }

        // Find an existing row, if any
        $input = [
            'itemtype' => $itemtype,
            'items_id' => $id,
        ];
        $usage_impact = new UsageImpact();
        $usage_impact->getFromDBByCrit($input);
        $impact_types = Type::getImpactTypes();

        $input['engine'] = $this->engine;
        $input['engine_version'] = $this->engine_version;

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
        if ($usage_impact->isNewItem()) {
            if ($usage_impact->add($input) !== false) {
                return true;
            }
        } else {
            unset($input['itemtype'], $input['items_id']); // prevent updating these columns
            if ($usage_impact->update(['id' => $usage_impact->getID()] + $input)) {
                return true;
            }
        }

        return false;
    }

    public function getEvaluableQuery(array $crit = [], bool $entity_restrict = true): array
    {
        $itemtype = static::$itemtype;
        $item_table = $itemtype::getTable();
        $glpi_location_table = GlpiLocation::getTable();
        $usage_impact_table = UsageImpact::getTable();

        // $where = [];
        // if (!$recalculate) {
        //     $where = [UsageImpact::getTableField('id') => null];
        // }

        $request = [
            'SELECT' => [
                $itemtype::getTableField('id'),
            ],
            'FROM' => $item_table,
            'LEFT JOIN' => [
                $usage_impact_table => [
                    'FKEY' => [
                        $usage_impact_table => 'items_id',
                        $item_table            => 'id',
                        ['AND' =>
                            [
                                UsageImpact::getTableField('itemtype') => $itemtype,
                            ]
                        ],
                    ],
                ],
                $glpi_location_table => [
                    'FKEY' => [
                        $glpi_location_table => 'id',
                        $item_table => 'locations_id',
                    ]
                ],
            ],
            'WHERE' => [
                ['NOT' => [GlpiLocation::getTableField('id') => null]],
            ] + $crit,
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
     * @param CommonDBTM $item
     * @return ?array
     */
    abstract protected function doEvaluation(CommonDBTM $item): ?array;
}
