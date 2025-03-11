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
use DeviceProcessor;
use GlpiPlugin\Carbon\ComputerType;
use ComputerType as GlpiComputerType;
use DBmysql;
use Item_DeviceMemory;
use Item_DeviceProcessor;
use Item_Devices;
use Item_Disk;

class Computer extends AbstractAsset
{
    protected static string $itemtype = GlpiComputer::class;

    protected string $endpoint        = 'server';

    protected function doEvaluation(CommonDBTM $item): ?array
    {
        // TODO: determine if the computer is a server, a computer, a laptop, a tablet...
        // then adapt $this->endpoint depending on the result

        $type = $this->getType($item);
        $this->endpoint = $this->getEndpoint($type);

        // Ask for embodied impact only
        $configuration = $this->analyzeHardware($item);
        if (count($configuration) === 0) {
            return null;
        }
        $description = [
            'configuration' => $configuration,
            'usage' => [
                'avg_power' => 0
            ],
        ];
        $response = $this->query($description);
        $embodied_impacts = $this->parseResponse($response);

        return $embodied_impacts;
    }

    /**
     * Get the type of the computer
     * @param CommonDBTM $item
     * @return int The type of the computer
     */
    protected function getType(CommonDBTM $item): int
    {
        /** @var DBmysql $DB */
        global $DB;

        $computer_table = GlpiComputer::getTable();
        $computer_type_table = ComputerType::getTable();
        $glpi_computer_type_table = GlpiComputerType::getTable();
        $result = $DB->request([
            'SELECT'     => ComputerType::getTableField('type'),
            'FROM'       => $computer_type_table,
            'INNER JOIN' => [
                $glpi_computer_type_table => [
                    'FKEY' => [
                        $computer_type_table => 'computertypes_id',
                        $glpi_computer_type_table => 'id',
                    ]
                ],
                $computer_table => [
                    'FKEY' => [
                        $glpi_computer_type_table => 'id',
                        $computer_table           => 'computertypes_id'
                    ],
                ],
            ],
            'WHERE' => [
                GlpiComputer::getTableField('id') => $item->getID(),
            ]
        ]);
        $row_count = $result->count();
        if ($row_count === 0) {
            return ComputerType::TYPE_UNDEFINED;
        } elseif ($result->count() > 1) {
            trigger_error(sprintf('SQL query shall return 1 row, got %d', $row_count), WARNING);
        }

        return $result->current()['type'];
    }

    /**
     * Get the endpoint to use for the given type
     */
    protected function getEndpoint(int $type)
    {
        switch ($type) {
            case ComputerType::TYPE_SERVER:
                return 'server';
            case ComputerType::TYPE_LAPTOP:
                return 'terminal/laptop';
            case ComputerType::TYPE_TABLET:
                return 'terminal/tablet';
            case ComputerType::TYPE_SMARTPHONE:
                return 'terminal/smartphone';
        }

        // ComputerType::TYPE_UNDEFINED
        // ComputerType::TYPE_DESKTOP
        return 'terminal/desktop';
    }

    protected function analyzeHardware(CommonDBTM $item): array
    {
        $configuration = [];
        // Yes, string expected here.
        $iterator = Item_Devices::getItemsAssociatedTo($item->getType(), (string) $item->getID());
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

        return $configuration;
    }
}
