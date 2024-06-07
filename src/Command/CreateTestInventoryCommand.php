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
 * @copyright Copyright (C) 2024 by the carbon plugin team.
 * @license   MIT https://opensource.org/licenses/mit-license.php
 * @link      https://github.com/pluginsGLPI/carbon
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Carbon\Command;

use DateTime;
use CommonDBTM;
use Computer as GlpiComputer;
use ComputerType as GlpiComputerType;
use Entity;
use Location;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\EnvironnementalImpact;
use Symfony\Component\Console\Command\Command;
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
                'average_load' => 20,
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
            'power' => 70 /* Watt */,
            'count' => 2,
        ],
        'Laptop' => [
            'usage_profile' => [
                'name' => 'Test laptop usage profile',
                'average_load' => 30,
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
            'power' => 40 /* Watt */,
            'count' => 5,
        ],
        'Server' => [
            'usage_profile' => [
                'name' => 'Test server usage profile',
                'average_load' => 50,
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
            'power' => 150 /* Watt */,
            'count' => 2,
        ],
        'Tablet' => [
            'usage_profile' => [
                'name' => 'Test tablet usage profile',
                'average_load' => 10,
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
            'power' => 15 /* Watt */,
            'count' => 1,
        ],
    ];

    private InputInterface $input;
    private OutputInterface $output;

    protected function configure() {
        $this
           ->setName('plugin:carbon:create_test_inventory')
           ->setDescription("Create a test inventory");
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->input = $input;
        $this->output = $output;

        $message = __("Creating test inventory", 'carbon');
        $output->writeln("<info>$message</info>");

        $this->createTestInventory(self::TEST_INVENTORY_DATA);

        return Command::SUCCESS;
    }

    private function createItemIfNotExist(string $item_type, array $crit, array $input = null) : CommonDBTM
    {
        $item = new $item_type();

        $ret = $item->getFromDBByCrit($crit);
        if (!$ret) {
            if (is_null($input)) {
                $input = $crit;
            }
            $this->output->writeln("<info>creating " . $item_type . "(" . implode(",", array_values($input)) . ")</info>");
            $item->add($input);

            // Reload the item to ensure that all fields are set
            $item->getFromDB($item->getID());
        }

        return $item;
    }

    private function getComputerType(string $type_name, int $power) : CommonDBTM
    {
        $glpi_computer_type = $this->createItemIfNotExist(GlpiComputerType::class, [ 'name' => $type_name]);
        $computer_type = $this->createItemIfNotExist(
            ComputerType::class,
            [
                GlpiComputerType::getForeignKeyField() => $glpi_computer_type->getID(),
                'power_consumption' => $power,
            ]
        );

        return $glpi_computer_type;
    }

    private function getComputer(string $computer_name, Location $location, GlpiComputerType $computer_type, ComputerUsageProfile $usage_profile) : CommonDBTM
    {
        $glpi_computer = $this->createItemIfNotExist(
            GlpiComputer::class, 
            [
                'name' => $computer_name,
                Location::getForeignKeyField() => $location->getID(),
                GlpiComputerType::getForeignKeyField() => $computer_type->getID(),
                Entity::getForeignKeyField() => 0,
            ]
        );
        $impact = $this->createItemIfNotExist(
            EnvironnementalImpact::class, 
            [
                GlpiComputer::getForeignKeyField() => $glpi_computer->getId(),
                ComputerUsageProfile::getForeignKeyField() => $usage_profile->getID(),
            ]
        );

        return $glpi_computer;
    }

    private function createTestInventory(array $inventory_data)
    {
        $location = $this->createItemIfNotExist(
            Location::class, 
            [
                'name' => self::TEST_LOCATION_NAME,
                'country' => self::TEST_LOCATION_COUNTRY
            ]
        );

        foreach($inventory_data as $type_name => $type_data) {
            $computer_type = $this->getComputerType($type_name, $type_data['power']);
            $usage_profile = $this->createItemIfNotExist(ComputerUsageProfile::class, $type_data['usage_profile']);
            for($computer_count = 0; $computer_count < $type_data['count']; $computer_count++) {
                $computer_name = $type_name . '-' .strval($computer_count);
                $this->getComputer($computer_name, $location, $computer_type, $usage_profile);
            }
        }
    }
}