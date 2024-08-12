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

namespace GlpiPlugin\Carbon\History;

use CommonDBTM;
use DateInterval;
use DateTime;
use DbUtils;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\CarbonIntensityZone;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\Engine\V1\EngineInterface;
use Location;
use Infocom;

abstract class AbstractAsset extends CommonDBTM implements AssetInterface
{
    protected static string $itemtype = '';
    protected static string $type_itemtype  = '';
    protected static string $model_itemtype = '';

    /** @var string Date interval to shift the end date relatively to the now */
    protected static string $date_end_shift = 'P1D';

    /** @var string Date interval to increment the next date to compute */
    protected static string $date_increment = 'P1D';

    /** @var int $limit maximum number of entries to build */
    protected int $limit = 0;

    protected bool $limit_reached = false;

    public function setLimit(int $limit)
    {
        $this->limit = $limit;
    }

    /**
     * Start the historization of all items
     *
     * @return int count of entries generated
     */
    public function historizeItems(): int
    {
        $itemtype = static::$itemtype;
        if ($itemtype === '') {
            throw new \LogicException('Itemtype not set');
        }
        if (!is_subclass_of($itemtype, CommonDBTM::class)) {
            throw new \LogicException('Itemtype does not inherits from ' . CommonDBTM::class);
        }

        $count = 0;

        $type_instance = new $itemtype();
        $rows = $type_instance->find();
        foreach ($rows as $row) {
            $count += $this->historizeItem($row['id']);
            if ($this->limit_reached) {
                break;
            }
        }

        return $count;
    }

    /**
     * Historize environnemental impact data of an asset
     * Days interval is [$start_date, $end_date[
     *
     * @param integer  $id
     * @param DateTime $start_date First date to compute (if not set, use the latest date found in DB)
     * @param DateTime $end_date   Last date to compute (if not set use now - 1 day)
     * @return int     count of generated entries
     */
    public function historizeItem(int $id, ?DateTime $start_date = null, ?DateTime $end_date = null): int
    {
        /** @var CommonDBTM $item */
        $itemtype = static::$itemtype;
        $item = $itemtype::getById($id);
        if ($item === false) {
            return 0;
        }

        // TODO: determine zone and source

        $last_entry = $this->getEmissionStartDate($id);
        if ($last_entry === null) {
            return 0;
        }

        // Find first date of existence of the asset in nventory
        $inventory_date = $this->getInventoryIncomingDate($id);

        // Determine first date to compute
        if ($start_date === null) {
            $start_date = $last_entry;
        } else {
            $start_date = max($last_entry, $start_date, $inventory_date);
        }

        // Determine the last date to compute
        $last_available_date = $this->getStopDate($id);
        if ($end_date === null) {
            $end_date = $last_available_date;
        } else {
            $end_date = min($last_available_date, $end_date);
        }

        $engine = static::getEngine($item);

        $count = 0;
        $date_cursor = $start_date;
        while ($date_cursor <= $end_date && !$this->limit_reached) {
            $success = $this->historizeItemPerDay($item, $engine, $date_cursor);
            if ($success) {
                $count++;
                if ($this->limit !== 0 && $count >= $this->limit) {
                    $this->limit_reached = true;
                }
            }
            $date_cursor = $date_cursor->add(new DateInterval(static::$date_increment));
        }

        return $count;
    }

    protected function historizeItemPerDay(CommonDBTM $item, EngineInterface $engine, DateTime $day): bool
    {
        $energy = $engine->getEnergyPerDay($day);
        if ($energy === null) {
            return false;
        }

        $emission = $engine->getCarbonEmissionPerDay($day);
        if ($emission === null) {
            return false;
        }

        $entry = new CarbonEmission();
        $type_fk = static::$type_itemtype::getForeignKeyField();
        $model_fk = static::$model_itemtype::getForeignKeyField();
        $id = $entry->add([
            'itemtype'          => $item->getType(),
            'items_id'          => $item->getID(),
            'entities_id'       => $item->fields['entities_id'],
            'types_id'          => $item->fields[$type_fk],
            'models_id'         => $item->fields[$model_fk],
            'locations_id'      => $item->fields['locations_id'],
            'energy_per_day'    => $energy,
            'emission_per_day'  => $emission,
            'date'              => $day->format('Y-m-d 00:00:00'),
        ]);

        return !$this->isNewID($id);
    }

    /**
     * Find the date where daily computation must start
     *
     * @param integer $id
     * @return DateTime|null
     */
    protected function getEmissionStartDate(int $id): ?DateTime
    {
        // Find the oldest carbon emissions date calculated for the item
        $itemtype = static::$itemtype;
        $carbon_emission = new CarbonEmission();
        $last_entry = $carbon_emission->find([
            'itemtype' => $itemtype,
            'items_id' => $id,
        ], [
            'date DESC'
        ], 1);

        if (count($last_entry) === 1) {
            $last_entry = array_pop($last_entry);
            $start_date = new DateTime($last_entry['date']);
            return $start_date;
        }

        // No data found,
        // Guess the oldest date to compute
        $zones_id = $this->getZoneId($id);
        $carbon_intensity = new CarbonIntensity();
        $first_entry = $carbon_intensity->find([
            'plugin_carbon_carbonintensityzones_id' => $zones_id,
        ], [
            'date ASC'
        ], 1);

        if (count($first_entry) === 1) {
            $first_entry = array_pop($first_entry);
            $start_date = new DateTime($first_entry['date']);
            return $start_date;
        }

        // No carbon intensity in DB, cannot find a date
        return null;
    }

    /**
     * Find the most accurate date to determine the first use of an asset
     *
     * @param integer $id id of the asset to examinate
     * @return DateTime|null
     */
    protected function getInventoryIncomingDate(int $id): ?DateTime
    {
        $start_date = null;
        $infocom = new Infocom();

        $itemtype = static::$itemtype;
        $infocom->getFromDBByCrit([
            'itemtype' => $itemtype,
            'items_id' => $id,
        ]);
        if (!$infocom->isNewItem()) {
            $start_date = $infocom->fields['use_date']
            ?? $infocom->fields['delivery_date']
            ?? $infocom->fields['buy_date']
            ?? null;
        }

        if ($start_date === null) {
            $asset = new $itemtype();
            if (!$asset->getFromDb($id)) {
                return null;
            }
            $start_date = $asset->fields['date_creation'] ?? $asset->fields['date_mod'] ?? null;
            if ($start_date === null) {
                return null;
            }
        }

        return new DateTime($start_date);
    }

    /**
     * Find the date where daily computation must stop
     *
     * @param integer $id
     * @return DateTime|null
     */
    protected function getStopDate(int $id): ?DateTime
    {
        $today = new DateTime('now');
        $today->setTime(0, 0, 0);
        $today = $today->sub(new DateInterval(static::$date_end_shift));

        $carbon_intensity = new CarBonIntensity();
        $zone_id = $this->getZoneId($id);
        $last = $carbon_intensity->find(
            [
                CarbonIntensityZone::getForeignKeyField() => $zone_id,
            ],
            ['date DESC'],
            '1'
        );

        $last_intensity_date = DateTime::createFromFormat('Y-m-d H:i:s', reset($last)['date']);
        $stop_date = min($today, $last_intensity_date);

        return $stop_date;
    }

    /**
     * Get the ID of the zone the asset belongs to
     * Location's country must match a zone name
     *
     * @param integer $items_id
     * @return integer|null
     */
    protected function getZoneId(int $items_id): ?int
    {
        global $DB;

        $item_table = (new DbUtils())->getTableForItemType(static::$itemtype);
        $location_table = Location::getTable();
        $zone_table = CarbonIntensityZone::getTable();
        $iterator = $DB->request([
            'SELECT' => CarbonIntensityZone::getTableField('id'),
            'FROM' => $zone_table,
            'INNER JOIN' => [
                $location_table => [
                    'FKEY' => [
                        $zone_table => 'name',
                        $location_table => 'country',
                    ],
                ],
                $item_table => [
                    'FKEY' => [
                        $item_table => Location::getForeignKeyField(),
                        $location_table => 'id',
                    ],
                ]
            ],
            'WHERE' => [
                $item_table . '.id' => $items_id
            ]
        ]);
        if ($iterator->count() !== 1) {
            return null;
        }

        return $iterator->current()['id'];
    }
}
