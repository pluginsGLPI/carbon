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
 * @license   MIT https://opensource.org/licenses/mit-license.php
 * @link      https://github.com/pluginsGLPI/carbon
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Carbon\Engine\V1;

use DateTime;
use Computer as GlpiComputer;
use DbUtils;

/**
 * Compute environnemental impact of a whole inventory
 *
 * To compute the environemental impact we need to add all items
 * to take into account with the method addItem()
 *
 * Next we call the method getCarbonEmission()
 */
class Inventory implements EngineInterface
{
    private array $items = [];

    /**
     * Check an item is already in the inventory
     *
     * @param string $itemtype
     * @param integer $items_id
     * @return boolean
     */
    public function hasItem(string $itemtype, int $items_id)
    {

        return isset($this->items[$itemtype][$items_id]);
    }

    /**
     * Is the itemtype an asset ?
     *
     * @param string $itemtype
     * @return boolean
     */
    private static function isAsset(string $itemtype): bool
    {
        global $CFG_GLPI;

         return in_array($itemtype, $CFG_GLPI["asset_types"]);
    }

    /**
     * Add an item to the inventory to be processed
     *
     * @param string $itemtype
     * @param integer $items_id
     * @return boolean
     */
    public function addItem(string $itemtype, int $items_id): bool
    {
        if ($this->hasItem($itemtype, $items_id) || !$this->isAsset($itemtype)) {
            return false;
        }

        switch ($itemtype) {
            default:
                $plugin_info = isPluginItemType($itemtype);
                if ($plugin_info === false) {
                    return false;
                }
                // Callback function for plugins
                // It gets the  following arguments
                // - string $itemtype
                // the callback returns the name of a class which implements EngineInterface
                $callback_function = 'plugin_' . $plugin_info['plug'] . '_carbon_engine';
                if (!is_callable($callback_function)) {
                    return false;
                }
                // Unsupported item
                return true;

            case GlpiComputer::class:
                $item = new Computer($items_id);
                break;
        }

        $this->items[$itemtype][$items_id] = $item;

        return true;
    }

    /**
     * Add several items to the inventory by itemtype and a search criteria
     *
     * @param string $itemtype itemtype of the items to add
     * @param array $crit search criteria of items to add
     * @return boolean true if success
     */
    public function addItemsByCrit(string $itemtype, array $crit = []): bool
    {
        if (!$this->isAsset($itemtype)) {
            return false;
        }

        $itemtype_table = (new DbUtils())->getTableForItemType($itemtype);
        $where = $crit + (new DbUtils())->getEntitiesRestrictCriteria($itemtype_table);

        $item = new $itemtype();
        $result = $item->find($where);
        foreach ($result as $key => $row) {
            $success = $this->addItem($itemtype, $key);
            if ($success === false) {
                return false;
            }
        }

        return true;
    }

    public function getPower(): int
    {
        $power = 0;

        foreach ($this->items as $itemtype => $items) {
            foreach ($items as $tems_id => $item) {
                $power += $item->getPower();
            }
        }

        return $power;
    }

    public function getEnergyPerDay(DateTime $day): float
    {
        $energy = 0;

        foreach ($this->items as $itemtype => $items) {
            foreach ($items as $tems_id => $item) {
                $energy += $item->getEnergyPerDay($day);
            }
        }

        return $energy;
    }

    public function getCarbonEmissionPerDay(DateTime $day): ?float
    {
        $carbon_emission = 0;

        foreach ($this->items as $itemtype => $items) {
            foreach ($items as $tems_id => $item) {
                $carbon_emission += $item->getCarbonEmissionPerDay($day);
            }
        }

        return $carbon_emission;
    }

    /**
     * Calculate the carbon emissions of the items in the inventory
     *
     * @param DateTime $begin_date
     * @param DateTime $end_date
     * @return float Carbon emissions in CO2eq
     */
    public function getCarbonEmission(DateTime $begin_date, DateTime $end_date): float
    {
        $carbon_emission = 0;

        foreach ($this->items as $itemtype => $items) {
            foreach ($items as $tems_id => $item) {
                $carbon_emission += $item->getCarbonEmission($begin_date, $end_date);
            }
        }

        return $carbon_emission;
    }
}
