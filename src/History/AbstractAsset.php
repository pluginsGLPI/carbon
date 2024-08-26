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
use DateTimeImmutable;
use DbUtils;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\CarbonIntensityZone;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\Engine\V1\EngineInterface;
use GlpiPlugin\Carbon\Toolbox;
use Location;

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

    abstract public function getHistorizableQuery(): array;

    public function getItemtype(): string
    {
        return static::$itemtype;
    }

    /**
     * Is it possible to historize carbon emissions for the item
     * @param int $id : ID of the item to examinate
     *
     * @return boolean
     */
    public function canHistorize(int $id): bool
    {
        global $DB;

        $request = $this->getHistorizableQuery();
        $request['WHERE'][static::$itemtype::getTableField('id')] = $id;

        $iterator = $DB->request($request);

        return $iterator->count() > 0;
    }

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
        $type_instance->getEmpty();
        $crit = [];
        if ($type_instance->maybeDeleted()) {
            $crit['is_deleted'] = 0;
        }
        if ($type_instance->maybeTemplate()) {
            $crit['is_template'] = 0;
        }
        $rows = $type_instance->find($crit);
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

        if (!$this->canHistorize($id)) {
            return 0;
        }

        // Determine first date to compute. May be modified by available intensity data
        $resume_date = $this->getStartDate($id);
        if ($resume_date === null) {
            return 0;
        }
        $start_date = max($start_date, $resume_date);

        // Determine the last date to compute
        $last_available_date = $this->getStopDate($id);
        if ($end_date === null) {
            $end_date = $last_available_date;
        } else {
            $end_date = min($last_available_date, $end_date);
        }

        $engine = static::getEngine($item);

        $count = 0;
        $carbon_emission = new CarbonEmission();
        $gaps = $carbon_emission->findGaps($itemtype, $id, $start_date, $end_date);
        foreach ($gaps as $gap) {
            $date_cursor = new DateTime($gap['start']);
            $date_cursor->setTime(0, 0, 0, 0);
            $end_date = new DateTime($gap['end']);
            while ($date_cursor < $end_date) {
                $success = $this->historizeItemPerDay($item, $engine, $date_cursor);
                if ($success) {
                    $count++;
                    if ($this->limit !== 0 && $count >= $this->limit) {
                        $this->limit_reached = true;
                        break 2;
                    }
                }
                $date_cursor = $date_cursor->add(new DateInterval(static::$date_increment));
            }
        }

        return $count;
    }

    protected function historizeItemPerDay(CommonDBTM $item, EngineInterface $engine, DateTime $day): bool
    {
        $energy = $engine->getEnergyPerDay($day);
        $emission = 0;
        if ($energy !== 0) {
            $emission = $engine->getCarbonEmissionPerDay($day);
            if ($emission === null) {
                return false;
            }
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
    protected function getStartDate(int $id): ?DateTimeImmutable
    {
        // Find the date the asset entered in the inventory
        $toolbox = new Toolbox();
        $inventory_date = $toolbox->getOldestAssetDate([
            'itemtype' => static::$itemtype,
            getTableForItemType(static::$itemtype) . '.id' => $id,
        ]);

        return $inventory_date;
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
