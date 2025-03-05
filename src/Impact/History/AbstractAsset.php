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

namespace GlpiPlugin\Carbon\Impact\History;

use CommonDBTM;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use DBmysql;
use DbUtils;
use GlpiPlugin\Carbon\Zone;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\DataTracking\TrackedFloat;
use GlpiPlugin\Carbon\Engine\V1\EngineInterface;
use GlpiPlugin\Carbon\Toolbox;
use Location;
use LogicException;
use Session;
use Toolbox as GlpiToolbox;

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

    /** @var bool tells if the batch evaluation must stop */
    protected bool $limit_reached = false;

    /**
     * Get request in Query builder format to find evaluable items
     *
     * @param boolean $entity_restrict
     * @return array
     */
    abstract public function getEvaluableQuery(bool $entity_restrict = true): array;

    abstract public static function getEngine(CommonDBTM $item): EngineInterface;

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
        /** @var DBmysql $DB */
        global $DB;

        $request = $this->getEvaluableQuery();
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

        $iterator = $DB->request($this->getEvaluableQuery(false));
        foreach ($iterator as $row) {
            $count += $this->evaluateItem($row['id']);
            if ($this->limit_reached) {
                break;
            }
        }

        return $count;
    }

    /**
     * Historize environmental impact data of an asset
     * Days interval is [$start_date, $end_date[
     *
     * @param integer  $id
     * @param DateTime $start_date First date to compute (if not set, use the latest date found in DB)
     * @param DateTime $end_date   Last date to compute (if not set use now - 1 day)
     * @return int     count of generated entries
     */
    public function evaluateItem(int $id, ?DateTime $start_date = null, ?DateTime $end_date = null): int
    {
        /** @var DBmysql $DB */
        global $DB;

        $itemtype = static::$itemtype;
        $item = $itemtype::getById($id);
        if ($item === false) {
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
        $end_date = $end_date ?? $last_available_date ?? (new DateTime('now'))->sub(new DateInterval(self::$date_end_shift));

        $engine = static::getEngine($item);

        $count = 0;
        $carbon_emission = new CarbonEmission();
        $gaps = $carbon_emission->findGaps($itemtype, $id, $start_date, $end_date);

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
        $timezone = $DB->guessTimezone();
        foreach ($gaps as $gap) {
            // $date_cursor = DateTime::createFromFormat('U', $gap['start']);
            // $date_cursor->setTimezone(new DateTimeZone($timezone));
            // $date_cursor->setTime(0, 0, 0, 0);
            // $end_date = DateTime::createFromFormat('U', $gap['end']);
            // $end_date->setTimezone(new DateTimeZone($timezone));
            $date_cursor = $gap['start']->setTime(0, 0, 0, 0);
            $end_date = $gap['end']->setTime(0, 0, 0, 0);
            while ($date_cursor < $end_date) {
                $success = $this->evaluateItemPerDay($item, $engine, $date_cursor);
                if ($success) {
                    $count++;
                    if ($this->limit !== 0 && $count >= $this->limit) {
                        $this->limit_reached = true;
                        break 2;
                    }
                }
                if ($memory_limit && $memory_limit < memory_get_usage()) {
                    // 8 MB memory left, emergency exit
                    $this->limit_reached = true;
                    break 2;
                }
                $date_cursor = $date_cursor->add(new DateInterval(static::$date_increment));
            }
        }

        return $count;
    }

    /**
     * Evaluate usage carbon emission for a single day
     *
     * @param CommonDBTM $item item ti evaluate
     * @param EngineInterface $engine Calculatin engine to use
     * @param DateTime $day Day to calculate
     * @return boolean
     */
    protected function evaluateItemPerDay(CommonDBTM $item, EngineInterface $engine, DateTimeInterface $day): bool
    {
        $energy = $engine->getEnergyPerDay($day);
        $item_id = $item->getID();
        $zone = $this->getZone($item_id /* ,$date_cursor */);
        if ($zone === null) {
            return false;
        }

        $emission = new TrackedFloat(0, $energy);
        if ($energy->getValue() !== 0) {
            $emission = $engine->getCarbonEmissionPerDay($day, $zone);
            if ($emission === null) {
                return false;
            }
        }

        $entry = new CarbonEmission();
        $type_fk = static::$type_itemtype::getForeignKeyField();
        $model_fk = static::$model_itemtype::getForeignKeyField();
        $id = $entry->add([
            'itemtype'          => $item->getType(),
            'items_id'          => $item_id,
            'entities_id'       => $item->fields['entities_id'],
            'types_id'          => $item->fields[$type_fk],
            'models_id'         => $item->fields[$model_fk],
            'locations_id'      => $item->fields['locations_id'],
            'date'              => $day->format('Y-m-d H:i:s'),
            'energy_per_day'    => $energy->getValue(),
            'energy_quality'    => $energy->getLowestSource(),
            'emission_per_day'  => $emission->getValue(),
            'emission_quality'  => $emission->getLowestSource(),
        ]);

        return !$this->isNewID($id);
    }

    /**
     * Find the date where daily computation must start
     *
     * @param integer $id
     * @return DateTimeImmutable|null
     */
    protected function getStartDate(int $id): ?DateTimeImmutable
    {
        // Find the date the asset entered in the inventory
        return $this->getInventoryIncomingDate($id);
    }

    /**
     * Find the most accurate date to determine the first use of an asset
     *
     * @param integer $id id of the asset to examinate
     * @return DateTimeImmutable|null
     */
    protected function getInventoryIncomingDate(int $id): ?DateTimeImmutable
    {
        $toolbox = new Toolbox();
        $inventory_date = $toolbox->getOldestAssetDate([
            'itemtype' => static::$itemtype,
            getTableForItemType(static::$itemtype) . '.id' => $id,
        ]);

        return $inventory_date;
    }

    /**
     * Find the most accurate date to determine the end of use of an asset
     *
     * @param integer $id
     * @return DateTimeImmutable|null
     */
    protected function getInventoryExitDate(int $id): ?DateTimeImmutable
    {
        $toolbox = new Toolbox();
        $inventory_date = $toolbox->getLatestAssetDate([
            'itemtype' => static::$itemtype,
            getTableForItemType(static::$itemtype) . '.id' => $id,
        ]);

        return $inventory_date;
    }

    /**
     * Find the date where daily computation must stop
     *
     * @param integer $id
     * @return DateTimeImmutable|null
     */
    protected function getStopDate(int $id): ?DateTimeImmutable
    {
        return $this->getInventoryExitDate($id);
    }

    /**
     * Get the zone the asset belongs to
     * Location's country must match a zone name
     *
     * @param integer $items_id
     * @param DateTime $date Date for which the zone must be found
     * @return Zone|null
     */
    protected function getZone(int $items_id, DateTime $date = null): ?Zone
    {
        // TODO: use date to find where was the asset at the given date
        if ($date === null) {
            $item_table = (new DbUtils())->getTableForItemType(static::$itemtype);
            $location_table = Location::getTable();
            $zone_table = Zone::getTable();

            $zone = new Zone();
            $found = $zone->getFromDBByRequest([
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
            if ($found === false) {
                return null;
            }

            return $zone;
        }

        throw new LogicException('Not implemented yet');
    }

    /**
     * Ddelete all calculated usage impact for an asset
     *
     * @param integer $items_id
     * @return boolean
     */
    public function resetHistory(int $items_id): bool
    {
        /** @var DBmysql $DB */
        global $DB;

        return $DB->delete(
            CarbonEmission::getTable(),
            [
                'itemtype' => static::getItemtype(),
                'items_id' => $items_id
            ]
        );
    }

    /**
     * Calculate usage impact of an asset
     *
     * @param integer $items_id
     * @return boolean
     */
    public function calculateImpact(int $items_id): bool
    {
        $calculated = $this->evaluateItem($items_id);
        if ($calculated === 0) {
            Session::addMessageAfterRedirect(
                sprintf(__('Failed to calculate usage impact', 'carbon'), $calculated),
            );
            return false;
        }

        Session::addMessageAfterRedirect(
            sprintf(__('%d entries calculated', 'carbon'), $calculated),
        );
        return true;
    }
}
