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

use CommonDBTM;
use Computer as GlpiComputer;
use ComputerModel as GlpiComputerModel;
use ComputerType as GlpiComputerType;
use DBmysql;
use DeviceHardDrive;
use DeviceHardDriveType;
use DeviceProcessor;
use GlpiPlugin\Carbon\CloudInventoryConnector;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Cloudinventory\Amazon;
use GlpiPlugin\Cloudinventory\Azure;
use GlpiPlugin\Cloudinventory\CloudInstance;
use GlpiPlugin\Cloudinventory\Google;
use GlpiPlugin\Cloudinventory\Ovh;
use GlpiPlugin\Cloudinventory\Scaleway;
use InterfaceType;
use Item_DeviceHardDrive;
use Item_DeviceMemory;
use Item_DeviceProcessor;
use Item_Devices;
use Manufacturer;
use UnhandledMatchError;

trait ComputerModelizationAdapterTrait
{
    protected const USAGE_NULL = [
        'avg_power' => 0,
    ];

    /**
     * If the plugin CloudInventory is available, this is an object from that
     * plugin representing the cloud related data of the computer
     */
    protected ?CloudInstance $cloud_instance = null;

    /**
     * @var array Description of the asset for querying Boaviztapi
     */
    protected array $description = [];

    private function chooseEvaluationMode(int $type): string
    {
        if ($type === ComputerType::CATEGORY_CLOUD) {
            $cloud_provider = '';
            switch ($this->cloud_instance->fields['itemtype']) {
                case Amazon::class:
                    $cloud_provider = 'aws';
                    break;
                case Azure::class:
                    $cloud_provider = 'azure';
                    break;
                case Google::class:
                    $cloud_provider = 'gcp';
                    break;
                case Ovh::class:
                    $cloud_provider = 'ovhcloud';
                    break;
                case Scaleway::class:
                    $cloud_provider = 'scaleway';
                    break;
            }
            $glpi_computer_model = GlpiComputerModel::getById($this->cloud_instance->fields['computermodels_id']);
            if ($glpi_computer_model !== false) {
                $instance_types = $this->client->getCloudInstances($cloud_provider);
                $model = $this->normalizeModel($cloud_provider, $glpi_computer_model->fields['name']);
                if (in_array($model, $instance_types)) {
                    $this->prepareCloudDescription($cloud_provider, $model);
                    return 'cloud';
                }
            }
        }

        $this->prepareHardwareDescription($type);
        return 'hardware';
    }

    /**
     * Get the type of the computer
     * @param CommonDBTM $item
     * @return int The type of the computer
     */
    protected function getType(CommonDBTM $item): int
    {
        $cloudInventory_connector = new CloudInventoryConnector();
        if ($cloudInventory_connector->pluginAvailable()) {
            $cloud_instance = new CloudInstance();
            $cloud_instance->getFromDBByCrit([
                'computers_id' => $item->getID(),
            ]);
            if (!$cloud_instance->isNewItem()) {
                $this->cloud_instance = $cloud_instance;
                return ComputerType::CATEGORY_CLOUD;
            }
        }

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
                    ],
                ],
                $computer_table => [
                    'FKEY' => [
                        $glpi_computer_type_table => 'id',
                        $computer_table           => 'computertypes_id',
                    ],
                ],
            ],
            'WHERE' => [
                GlpiComputer::getTableField('id') => $item->getID(),
            ],
        ]);
        if ($found === false) {
            return ComputerType::CATEGORY_UNDEFINED;
        }

        return $computer_type->fields['category'];
    }

    /**
     * Prepare description of the asset for the Boaviztapi query
     */
    private function prepareHardwareDescription(int $type): void
    {
        try {
            $this->endpoint = match ($type) {
                ComputerType::CATEGORY_SERVER     => 'server',
                ComputerType::CATEGORY_LAPTOP     => 'terminal/laptop',
                ComputerType::CATEGORY_TABLET     => 'terminal/tablet',
                ComputerType::CATEGORY_SMARTPHONE => 'terminal/smartphone',
            };
        } catch (UnhandledMatchError $e) {
            $this->endpoint = 'terminal/desktop';
        }

        $this->description = [
            'configuration' => $this->analyzeHardware(),
            'usage' => self::USAGE_NULL,
        ];
    }

    /**
     * Prepare description of the asset for the Boaviztapi query
     *
     * @param string $provider
     * @param string $model
     * @return void
     */
    protected function prepareCloudDescription(string $provider, string $model)
    {
        $this->endpoint = 'cloud/instance';

        $this->description = [
            'usage'    => self::USAGE_NULL,
        ];
        $this->description['provider'] = $provider;
        $this->description['instance_type'] = $model;
    }

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

    protected function normalizeModel(string $provider, string $model): string
    {
        switch ($provider) {
            case 'scaleway':
                // CloudInventory sets scaleway models with the prefix "SCW-"
                return strtolower(substr($model, 4));
        }

        return $model;
    }
}
