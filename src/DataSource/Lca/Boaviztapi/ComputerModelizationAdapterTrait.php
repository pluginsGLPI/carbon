<?php

/**
 * -------------------------------------------------------------------------
 * Carbon plugin for GLPI
 *
 * @copyright Copyright (C) 2024-2025 Teclib' and contributors.
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

namespace GlpiPlugin\Carbon\DataSource\Lca\Boaviztapi;

use DBmysql;
use DeviceHardDrive;
use DeviceHardDriveType;
use DeviceProcessor;
use InterfaceType;
use Item_DeviceHardDrive;
use Item_DeviceMemory;
use Item_DeviceProcessor;
use Item_Devices;
use Manufacturer;

trait ComputerModelizationAdapterTrait
{
    /**
     * Get a description of the computer for Boaviztapi
     *
     * @return array
     */
    protected function analyzeHardware(): array
    {
        $configuration = [];
        // Yes, string expected here.
        $iterator = Item_Devices::getItemsAssociatedTo(get_class($this->item), (string) $this->item->getID());
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
                    $ram = [
                        'capacity' => ceil($item_device->fields['size'] / 1024), // Convert to GB
                    ];
                    $manufacturer = $this->getDeviceManufacturer($item_device);
                    if (!empty($manufacturer)) {
                        $ram['manufacturer'] = $manufacturer;
                    }
                    $key_match = $this->arrayMatch($ram, $configuration['ram'] ?? []);
                    if ($key_match !== null) {
                        // increment the units count of the RAM
                        $configuration['ram'][$key_match]['units']++;
                    } else {
                        $ram['units'] = 1;
                        $configuration['ram'][] = $ram;
                    }
                    break;
                case Item_DeviceHardDrive::class:
                    $hard_drive = [
                        'capacity' => ceil($item_device->fields['capacity'] / 1024), // Convert to GB
                    ];
                    $type = 'hdd';
                    $device_hard_drive = new DeviceHardDrive();
                    $device_hard_drive->getFromDB($item_device->fields['deviceharddrives_id']);
                    if (!$device_hard_drive->isNewItem()) {
                        $device_hard_drive_type = DeviceHardDriveType::getById(
                            $device_hard_drive->fields[getForeignKeyFieldForItemType(DeviceHardDriveType::class)]
                        );
                        if ($device_hard_drive_type !== false && $device_hard_drive_type->fields['name'] === 'removable') {
                            // Ignore removable storage (USB sticks, ...)
                            break;
                        }
                        $interface_type = new InterfaceType();
                        $interface_type->getFromDB($device_hard_drive->fields['interfacetypes_id']);
                        if (!$interface_type->isNewItem()) {
                            if (in_array($interface_type->fields['name'], ['NVME'])) {
                                $type = 'ssd';
                                $manufacturer = $this->getDeviceManufacturer($item_device);
                                if ($manufacturer !== null) {
                                    $hard_drive['manufacturer'] = $manufacturer;
                                }
                            }
                        }
                    }
                    $hard_drive['type'] = $type;
                    $key_match = $this->arrayMatch($hard_drive, $configuration['disk'] ?? []);
                    if ($key_match !== null) {
                        // increment the units count of the disk
                        $configuration['disk'][$key_match]['units']++;
                    } else {
                        $hard_drive['units'] = 1;
                        $configuration['disk'][] = $hard_drive;
                    }
                    break;
            }
        }

        return $configuration;
    }

    /**
     * Checks if the array $needle matches any of the arrays in $haystack
     *
     * @param array $needle
     * @param array $haystack
     * @return mixed key the key of the component in $haystack if found, null otherwise
     */
    private function arrayMatch(array $needle, array $haystack)
    {
        foreach ($haystack as $key => $item) {
            // ignore units as it does not represents characteristics of a component
            unset($item['units']);
            if ($item === $needle) {
                return $key;
            }
        }

        return null;
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
                    ],
                ],
                $table_manufacturer => [
                    'ON' => [
                        $table_manufacturer => 'id',
                        $table_device  => $manufacturer_fk,
                    ],
                ],
            ],
            'WHERE' => [
                $item->getTableField('id') => $item->getID(),
            ],
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
