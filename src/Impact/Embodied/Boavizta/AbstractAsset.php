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

namespace GlpiPlugin\Carbon\Impact\Embodied\Boavizta;

use CommonDBTM;
use DBmysql;
use GlpiPlugin\Carbon\Engine\V1\EngineInterface;
use GlpiPlugin\Carbon\DataSource\Boaviztapi;
use GlpiPlugin\Carbon\DataTracking\AbstractTracked;
use GlpiPlugin\Carbon\DataTracking\TrackedFloat;
use GlpiPlugin\Carbon\EmbodiedImpact;
use GlpiPlugin\Carbon\Impact\Embodied\AbstractEmbodiedImpact;
use Session;
use Toolbox as GlpiToolbox;

abstract class AbstractAsset extends AbstractEmbodiedImpact implements AssetInterface
{
    protected static string $itemtype = '';

    /**
     * @var int|null $hardware_analyzed id of the analyzed asset
     *
     * Used to avoid repeated requests to the backend for a single item
    */
    protected ?int $hardware_analyzed = null;

    /** @var array $hardware hardware description for the request */
    protected array $hardware = [];

    /** @var int $limit maximum number of entries to build */
    protected int $limit = 0;

    protected bool $limit_reached = false;

    protected ?TrackedFloat $gwp = null;

    protected ?TrackedFloat $adp = null;

    protected ?TrackedFloat $pe = null;

    protected ?Boaviztapi $client = null;

    abstract public function getEvaluableQuery(bool $entity_restrict = true): array;

    abstract public static function getEngine(CommonDBTM $item): EngineInterface;

    /**
     * Analyze the hardware of  the asset to prepare the request to the assment backend
     *
     * @return void
     */
    abstract protected function analyzeHardware(int $items_id);

    abstract public function calculateGwp(int $items_id): ?TrackedFloat;

    abstract public function calculateAdp(int $items_id): ?TrackedFloat;

    abstract public function calculatePe(int $items_id): ?TrackedFloat;

    public function __construct()
    {
        $this->gwp = null;
        $this->adp = null;
        $this->pe = null;
    }

    /**
     * Set the REST API client to use for requests
     *
     * @param Boaviztapi $client
     * @return void
     */
    public function setClient(Boaviztapi $client)
    {
        $this->client = $client;
    }

    public function getItemtype(): string
    {
        return static::$itemtype;
    }

    /**
     * Delete all calculated embodied impact for an asset
     *
     * @param integer $items_id
     * @return boolean
     */
    public function resetImpact(int $items_id): bool
    {
        $embodied_impact = new EmbodiedImpact();
        return $embodied_impact->deleteByCriteria([
            'itemtype' => static::getItemtype(),
            'items_id' => $items_id
        ]);
    }

    final public function getUnit(int $type, bool $short = true): ?string
    {
        switch ($type) {
            case AssetInterface::IMPACT_GWP:
                return $short ? 'gCO2eq' : __('grams of carbon dioxyde equivalent', 'carbon');
            case AssetInterface::IMPACT_ADP:
                return $short ? 'gSbeq' : __('grams of antimony equivalent', 'carbon');
            case AssetInterface::IMPACT_PE:
                return $short ? 'J' : __('joules', 'carbon');
        }

        return null;
    }

    /**
     * Get all impacts of the asset
     *
     * @return \Generator
     */
    final public function getImpacts(int $id): \Generator
    {
        foreach ($this->getImpactTypes() as $type) {
            yield $type => $this->getImpact($type, $id);
        }
    }

    final public function getImpact(int $type, int $id): ?AbstractTracked
    {
        switch ($type) {
            case AssetInterface::IMPACT_GWP:
                return $this->getGwp($id);
            case AssetInterface::IMPACT_ADP:
                return $this->getAdp($id);
            case AssetInterface::IMPACT_PE:
                return $this->getPe($id);
        }

        return null;
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

    /**
     * Evaluate all impacts of the asset
     *
     * @param integer $id
     * @return integer count of asserts evaluated
     */
    public function evaluateItem(int $id): int
    {
        $itemtype = static::$itemtype;
        $item = $itemtype::getById($id);
        if ($item === false) {
            return 0;
        }

        $input = [];
        $key_map = [
            AssetInterface::IMPACT_GWP => 'gwp',
            AssetInterface::IMPACT_ADP => 'adp',
            AssetInterface::IMPACT_PE  => 'pe',
        ];
        /** @var AbstractTracked $value */
        foreach ($this->getImpacts($id) as $impact => $value) {
            $key = $key_map[$impact];
            if ($value === null) {
                $input[$key] = null;
                $input["{$key}_quality"] = AbstractTracked::DATA_QUALITY_UNSPECIFIED;
                continue;
            }
            $input[$key] = $value->getValue();
            $input["{$key}_quality"] = $value->getLowestSource();
        }

        $embodied_impact = new EmbodiedImpact();
        $input_item = [
            'itemtype' => static::getItemtype(),
            'items_id' => $id,
        ];
        $embodied_impact->getFromDBByCrit($input_item);
        if ($embodied_impact->isNewItem()) {
            $input = array_merge($input, $input_item);
            if ($embodied_impact->add($input) === false) {
                return 0;
            }

            return 1;
        }

        $input = array_merge($input, ['id' => $embodied_impact->getID()]);
        if ($embodied_impact->update($input) === false) {
            return 0;
        }

        return 1;
    }

    /**
     * Calculate embodied impact for an item
     *
     * requres prior call to setClient()
     *
     * @param integer $items_id
     * @return boolean
     */
    public function calculateImpact(int $items_id): bool
    {
        $calculated = $this->evaluateItem($items_id);
        if ($calculated === 0) {
            Session::addMessageAfterRedirect(
                sprintf(__('Failed to calculate embodied impact', 'carbon'), $calculated),
            );
            return false;
        }

        Session::addMessageAfterRedirect(
            sprintf(__('Embodied impact sucessfully evaluated', 'carbon'), $calculated),
        );
        return true;
    }

    protected function evaluateItemPerImpact(CommonDBTM $item, EngineInterface $engine, int $impact): bool
    {
        return true;
    }


    public function getImpactTypes(): array
    {
        return [
            AssetInterface::IMPACT_GWP,
            AssetInterface::IMPACT_ADP,
            AssetInterface::IMPACT_PE,
        ];
    }

    final public function getGwp(int $items_id): ?TrackedFloat
    {
        return $this->calculateGwp($items_id);
    }

    final public function getAdp(int $items_id): ?TrackedFloat
    {
        return $this->calculateAdp($items_id);
    }

    final public function getPe(int $items_id): ?TrackedFloat
    {
        return $this->calculatePe($items_id);
    }
}
