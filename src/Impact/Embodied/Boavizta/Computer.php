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
use DBmysql;
use DeviceProcessor;
use GlpiPlugin\Carbon\ComputerType;
use ComputerType as GlpiComputerType;
use DeviceHardDrive;
use InterfaceType;
use Item_DeviceHardDrive;
use Item_DeviceMemory;
use Item_DeviceProcessor;
use Item_Devices;
use Manufacturer;

class Computer extends AbstractAsset
{
    protected static string $itemtype = GlpiComputer::class;

    protected string $endpoint        = 'server';

    protected function doEvaluation(CommonDBTM $item): ?array
    {
        // adapt $this->endpoint depending on the type of computer (server, laptop, ...)
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
        $impacts = $this->parseResponse($response);

        return $impacts;
    }

    /**
     * Get the type of the computer
     * @param CommonDBTM $item
     * @return int The type of the computer
     */
    protected function getType(CommonDBTM $item): int
    {
        $computer_table = GlpiComputer::getTable();
        $computer_type_table = ComputerType::getTable();
        $glpi_computer_type_table = GlpiComputerType::getTable();
        $computer_type = new ComputerType();
        $found = $computer_type->getFromDBByRequest([
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
        if ($found === false) {
            return ComputerType::CATEGORY_UNDEFINED;
        }

        return $computer_type->fields['category'];
    }

    /**
     * Get the endpoint to use for the given type
     */
    protected function getEndpoint(int $type)
    {
        switch ($type) {
            case ComputerType::CATEGORY_SERVER:
                return 'server';
            case ComputerType::CATEGORY_LAPTOP:
                return 'terminal/laptop';
            case ComputerType::CATEGORY_TABLET:
                return 'terminal/tablet';
            case ComputerType::CATEGORY_SMARTPHONE:
                return 'terminal/smartphone';
        }

        // ComputerType::CATEGORY_UNDEFINED
        // ComputerType::CATEGORY_DESKTOP
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
                    $manufacturer = $this->getDeviceManufacturer($item_device);
                    $ram = [
                        'units'    => 1,
                        'capacity' => ceil($item_device->fields['size'] / 1024), // Convert to GB
                    ];
                    if (!empty($manufacturer)) {
                        $ram['manufacturer'] = $manufacturer;
                    }
                    $configuration['ram'][] = $ram;
                    break;
                case Item_DeviceHardDrive::class:
                    $hard_drive = [
                        'units'    => 1,
                        'capacity' => ceil($item_device->fields['capacity'] / 1024), // Convert to GB
                    ];
                    $type = 'hdd';
                    $device_hard_drive = new DeviceHardDrive();
                    $device_hard_drive->getFromDB($item_device->fields['deviceharddrives_id']);
                    if (!$device_hard_drive->isNewItem()) {
                        $interface_type = new InterfaceType();
                        $interface_type->getFromDB($device_hard_drive->fields['interfacetypes_id']);
                        if (!$interface_type->isNewItem()) {
                            if (in_array($interface_type->fields['name'], ['NVME'])) {
                                $type = 'ssd';
                                $manufacturer = $this->getDeviceManufacturer($item_device);
                                if ($manufacturer !== null) {
                                    $$hard_drive['manufacturer'] = $manufacturer;
                                }
                                $hard_drive['manufacturer'] = $manufacturer;
                            }
                        }
                    }
                    $hard_drive['type'] = $type;
                    $configuration['disk'][] = $hard_drive;
                    break;
            }
        }

        return $configuration;
    }

    /**
     * Get the manufacturer of the device
     *
     * @param Item_Devices $item
     * @return string|null
     */
    private function getDeviceManufacturer(Item_Devices $item): ?string
    {
        /** @var DBmysql $DB */
        global $DB;

        // Get the manufacturer of the device
        $table_device = getTableForItemType($item::$itemtype_2);
        $table_device_item = getTableForItemType($item->getType());
        $table_manufacturer = getTableForItemType(Manufacturer::class);
        $device_fk = getForeignKeyFieldForItemType($item::$itemtype_2);
        $manufacturer_fk = getForeignKeyFieldForItemType(Manufacturer::class);
        $request = [
            'SELECT' => [
                $table_manufacturer => ['id', 'name'],
            ],
            'FROM' => $table_device_item,
            'INNER JOIN' => [
                $table_device => [
                    'ON' => [
                        $table_device_item => $device_fk,
                        $table_device => 'id',
                    ]
                ],
                $table_manufacturer => [
                    'ON' => [
                        $table_manufacturer => 'id',
                        $table_device  => $manufacturer_fk,
                    ]
                ],
            ],
            'WHERE' => [
                $item->getTableField('id') => $item->getID(),
            ]
        ];

        $result = $DB->request($request);
        if ($result->numRows() === 0) {
            return null;
        }
        $data = $result->current();
        if (empty($data['name'])) {
            return null;
        }

        return $data['name'];
    }
}
