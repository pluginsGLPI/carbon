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

namespace GlpiPlugin\Carbon\Impact\Usage;

use CommonDBTM;
use DBmysql;
use DBmysqlIterator;
use GlpiPlugin\Carbon\DataTracking\AbstractTracked;
use GlpiPlugin\Carbon\Impact\Type;
use GlpiPlugin\Carbon\Toolbox;
use GlpiPlugin\Carbon\UsageImpact;
use LogicException;
use Override;
use RuntimeException;
use Toolbox as GlpiToolbox;

abstract class AbstractUsageImpact implements UsageImpactInterface
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
            throw new LogicException("Given item is empty");
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
     * @param int $type
     * @param bool $short
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

    #[Override]
    public function setLimit(int $limit)
    {
        $this->limit = $limit;
    }

    #[Override]
    public function getItemsToEvaluate(string $itemtype, array $crit = []): DBmysqlIterator
    {
        /** @var DBmysql $DB */
        global $DB;

        if (!GlpiToolbox::isCommonDBTM($itemtype)) {
            throw new LogicException('Itemtype does not inherits from ' . CommonDBTM::class);
        }

        $crit[] = [
            'OR' => [
                UsageImpact::getTableField('id') => null,
                UsageImpact::getTableField('recalculate') => 1,
            ],
        ];
        $iterator = $DB->request($this->getEvaluableQuery($itemtype, $crit));

        return $iterator;
    }

    #[Override]
    public function evaluateItems(DBmysqlIterator $iterator): int
    {
        /**
         * Huge quantity of SQL queries will be executed
         * We NEED to check memory usage to avoid running out of memory
         * @see DBmysql::doQuery()
         */
        $memory_limit = Toolbox::getMemoryLimit();

        /** @var int $attempts_count count of evaluation attempts */
        $attempts_count = 0;
        /** @var int $count count of successfully evaluated assets */
        $count = 0;
        foreach ($iterator as $row) {
            if ($this->evaluateItem()) {
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

    #[Override]
    public function evaluateItem(): bool
    {
        $itemtype = get_class($this->item);

        try {
            $this->getVersion();
            $impacts = $this->doEvaluation($this->item);
        } catch (RuntimeException $e) {
            return false;
        }

        if ($impacts === null) {
            // Nothing calculated
            return false;
        }

        // Find an existing row, if any
        $input = [
            'itemtype' => $itemtype,
            'items_id' => $this->item->getID(),
        ];
        $usage_impact = new UsageImpact();
        $usage_impact->getFromDBByCrit($input);
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

    /**
     * Do the environmental impact evaluation of an asset
     *
     * @param CommonDBTM $item
     * @return ?array
     */
    abstract protected function doEvaluation(CommonDBTM $item): ?array;
}
