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

namespace GlpiPlugin\Carbon\Command;

use DateTime;
use CommonDBTM;
use Computer as GlpiComputer;
use ComputerType as GlpiComputerType;
use ComputerModel as GlpiComputerModel;
use Entity;
use Location;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\UsageInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateTestInventoryCommand extends Command
{
    const TEST_LOCATION_NAME = 'Test location';
    const TEST_LOCATION_COUNTRY = 'France';
    const TEST_INVENTORY_DATA = [
        'Desktop' => [
            'usage_profile' => [
                'name' => 'Test desktop usage profile',
                'time_start' => "10:00:00",
                'time_stop' => "18:00:00",
                'day_1' => 1,
                'day_2' => 1,
                'day_3' => 1,
                'day_4' => 1,
                'day_5' => 1,
                'day_6' => 0,
                'day_7' => 0,
            ],
            'power' => 70 /* Watt */ ,
            'count' => 2,
            'model' => ['name' => 'Desktop'],
        ],
        'Laptop' => [
            'usage_profile' => [
                'name' => 'Test laptop usage profile',
                'time_start' => "09:00:00",
                'time_stop' => "17:00:00",
                'day_1' => 1,
                'day_2' => 1,
                'day_3' => 1,
                'day_4' => 1,
                'day_5' => 1,
                'day_6' => 0,
                'day_7' => 0,
            ],
            'power' => 40 /* Watt */ ,
            'count' => 5,
            'model' => ['name' => 'Laptop'],
        ],
        'Server' => [
            'usage_profile' => [
                'name' => 'Test server usage profile',
                'time_start' => "00:00:00",
                'time_stop' => "23:59:59",
                'day_1' => 1,
                'day_2' => 1,
                'day_3' => 1,
                'day_4' => 1,
                'day_5' => 1,
                'day_6' => 1,
                'day_7' => 1,
            ],
            'power' => 150 /* Watt */ ,
            'count' => 2,
            'model' => ['name' => 'Server'],
        ],
        'Tablet' => [
            'usage_profile' => [
                'name' => 'Test tablet usage profile',
                'time_start' => "10:30:00",
                'time_stop' => "16:30:00",
                'day_1' => 1,
                'day_2' => 1,
                'day_3' => 0,
                'day_4' => 1,
                'day_5' => 1,
                'day_6' => 0,
                'day_7' => 0,
            ],
            'power' => 15 /* Watt */ ,
            'count' => 1,
            'model' => ['name' => 'Tablet'],
        ],
    ];

    private OutputInterface $output;

    protected function configure()
    {
        $this
            ->setName('plugins:carbon:create_test_inventory')
            ->setDescription('Create a test inventory')
            ->setHelp('This command creates an inventory for testing, using internal data.');

        $this->addOption(
            'entity-id',
            'i',
            InputOption::VALUE_REQUIRED,
            'The ID of the entity in which the assets will be created (entity must have been created first)'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $message = __("Creating test inventory", 'carbon');
        $output->writeln("<info>$message</info>");

        $entity_id = 0;
        $entity_id_option = $input->getOption('entity-id');
        if ($entity_id_option) {
            $entity_id = intval($entity_id_option);
        }

        $this->createTestInventory($entity_id, self::TEST_INVENTORY_DATA);

        return Command::SUCCESS;
    }

    private function createItemIfNotExist(string $item_type, array $crit, ?array $input = null): CommonDBTM
    {
        $item = new $item_type();

        $ret = $item->getFromDBByCrit($crit);
        if (!$ret) {
            if (is_null($input)) {
                $input = $crit;
            }
            $this->output->writeln("<info>creating " . $item_type . "(" . implode(", ", array_values($input)) . ")</info>");
            $item->add($input);

            // Reload the item to ensure that all fields are set
            $item->getFromDB($item->getID());
        }

        return $item;
    }

    private function getComputerType(string $type_name, int $power): CommonDBTM
    {
        $glpi_computer_type = $this->createItemIfNotExist(GlpiComputerType::class, ['name' => $type_name]);
        $this->createItemIfNotExist(
            ComputerType::class,
            [
                GlpiComputerType::getForeignKeyField() => $glpi_computer_type->getID(),
                'power_consumption' => $power,
            ]
        );

        return $glpi_computer_type;
    }

    private function getComputer(string $computer_name, int $entity_id, Location $location, GlpiComputerType $computer_type, ComputerUsageProfile $usage_profile, GlpiComputerModel $model): CommonDBTM
    {
        $glpi_computer = $this->createItemIfNotExist(
            GlpiComputer::class,
            [
                'name' => $computer_name,
                Location::getForeignKeyField() => $location->getID(),
                GlpiComputerType::getForeignKeyField() => $computer_type->getID(),
                Entity::getForeignKeyField() => $entity_id,
                GlpiComputerModel::getForeignKeyField() => $model->getID(),
            ]
        );
        $glpi_computer->update([
            'id' => $glpi_computer->getID(),
            'date_creation' => '2021-01-01 00:00:00',
        ]);
        $this->createItemIfNotExist(
            UsageInfo::class,
            [
                GlpiComputer::getForeignKeyField() => $glpi_computer->getId(),
                ComputerUsageProfile::getForeignKeyField() => $usage_profile->getID(),
            ]
        );

        return $glpi_computer;
    }

    private function createTestInventory(int $entity_id, array $inventory_data)
    {
        /** @var Location $location */
        $location = $this->createItemIfNotExist(
            Location::class,
            [
                'name' => self::TEST_LOCATION_NAME,
                'country' => self::TEST_LOCATION_COUNTRY,
                // Eiffel Tower, Paris, France
                'latitude' => '48.858093',
                'longitude' => '2.294694',
                'locations_id' => 0,
            ]
        );

        foreach ($inventory_data as $type_name => $type_data) {
            /** @var GlpiComputerType $computer_type */
            $computer_type = $this->getComputerType($type_name, $type_data['power']);
            /** @var ComputerUsageProfile $usage_profile */
            $usage_profile = $this->createItemIfNotExist(ComputerUsageProfile::class, $type_data['usage_profile']);
            /** @var GlpiComputerModel $model */
            $model = $this->createItemIfNotExist(GlpiComputerModel::class, $type_data['model']);
            for ($computer_count = 0; $computer_count < $type_data['count']; $computer_count++) {
                $computer_name = $type_name . '-' . strval($computer_count);
                $this->getComputer($computer_name, $entity_id, $location, $computer_type, $usage_profile, $model);
            }
        }
    }
}
