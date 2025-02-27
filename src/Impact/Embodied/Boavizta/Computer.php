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
use Computer as GlpiComputer;
use GlpiPlugin\Carbon\Engine\V1\EngineInterface;
use GlpiPlugin\Carbon\Engine\V1\Computer as EngineComputer;
use DbUtils;
use DeviceProcessor;
use GlpiPlugin\Carbon\DataTracking\TrackedFloat;
use Item_DeviceMemory;
use Item_DeviceProcessor;
use Item_Devices;
use Item_Disk;
use Toolbox;

class Computer extends AbstractAsset
{
    protected static string $itemtype       = GlpiComputer::class;

    public static function getEngine(CommonDBTM $item): EngineInterface
    {
        return new EngineComputer($item->getID());
    }

    public function setLimit(int $limit)
    {
        $this->limit = $limit;
    }

    public function getEvaluableQuery(bool $entity_restrict = true): array
    {
        $item_table = self::$itemtype::getTable();

        $request = [
            'SELECT' => [
                self::$itemtype::getTableField('id'),
            ],
            'FROM' => $item_table,
        ];

        if ($entity_restrict) {
            $entity_restrict = (new DbUtils())->getEntitiesRestrictCriteria($item_table, '', '', 'auto');
            $request['WHERE'] = $entity_restrict;
        }

        return $request;
    }

    public function calculateGwp(int $items_id): ?TrackedFloat
    {
        $this->analyzeHardware($items_id);
        return $this->gwp;
    }

    public function calculateAdp(int $items_id): ?TrackedFloat
    {
        $this->analyzeHardware($items_id);
        return $this->adp;
    }

    public function calculatePe(int $items_id): ?TrackedFloat
    {
        $this->analyzeHardware($items_id);
        return $this->pe;
    }

    protected function analyzeHardware(int $items_id)
    {
        if ($this->hardware_analyzed === $items_id) {
            return;
        }

        $configuration = [];
        // Yes, string expected here.
        $iterator = Item_Devices::getItemsAssociatedTo(GlpiComputer::class, (string) $items_id);
        foreach ($iterator as $item_device) {
            switch ($item_device->getType()) {
                case Item_DeviceProcessor::class:
                    $cpu = DeviceProcessor::getById($item_device->fields['deviceprocessors_id']);
                    if ($cpu) {
                        if (isset($configuration['cpu'])) {
                            // The server does not support several CPU with different specifications
                            // then, just increment CPU count
                            $configuration['cpu']['units']++;
                        } else {
                            $configuration['cpu'] = [
                                'units'      => 1,
                                'name'       => $cpu->fields['designation'],
                            ];
                            if (isset($item_device->fields['nbcores'])) {
                                $configuration['cpu']['core_units'] = $item_device->fields['nbcores'];
                            }
                        }
                    }
                    break;
                case Item_DeviceMemory::class:
                    $configuration['memory'][] = [
                        'units' => 1,
                        'capacity' => ceil($item_device->fields['size'] / 1024),
                    ];
                    break;
                case Item_Disk::class:
                    $configuration['disk'][] = [
                        'units' => 1,
                        'capacity' => ceil($item_device->fields['capacity'] / 1024),
                    ];
                    break;
            }
        }

        // Disable usage
        $this->hardware['configuration'] = $configuration;
        $this->hardware['usage'] = [
            'avg_power' => 0
        ];

        try {
            $response = $this->client->request('server', [
                'json' => $this->hardware,
            ]);
        } catch (\RuntimeException $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return;
        }

        // Normalize estimations
        $this->gwp = new TrackedFloat(
            $response['impacts']['gwp']['embedded']['value'],
            null,
            TrackedFloat::DATA_QUALITY_ESTIMATED
        );
        if ($response['impacts']['gwp']['unit'] === 'kgCO2eq') {
            $this->gwp->setValue($this->gwp->getValue() * 1000);
        }

        $this->adp = new TrackedFloat(
            $response['impacts']['adp']['embedded']['value'],
            null,
            TrackedFloat::DATA_QUALITY_ESTIMATED
        );
        if ($response['impacts']['adp']['unit'] === 'kgSbeq') {
            $this->adp->setValue($this->adp->getValue() * 1000);
        }

        $this->pe = new TrackedFloat(
            $response['impacts']['pe']['embedded']['value'],
            null,
            TrackedFloat::DATA_QUALITY_ESTIMATED
        );
        if ($response['impacts']['pe']['unit'] === 'MJ') {
            $this->pe->setValue($this->pe->getValue() * 1000000);
        }

        // Update last analyzed item id
        $this->hardware_analyzed = $items_id;
    }
}
