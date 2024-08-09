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
 * @license   MIT https://opensource.org/licenses/mit-license.php
 * @link      https://github.com/pluginsGLPI/carbon
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Carbon\Dashboard\Tests;

use Computer;
use ComputerModel;
use ComputerType as GlpiComputerType;
use DateInterval;
use DateTime;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\Dashboard\Provider;
use GlpiPlugin\Carbon\EnvironnementalImpact;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use Location;
use Session;

class ProviderTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->login('glpi', 'glpi');
    }

    protected function handledComputersCountFixture(): int
    {
        // Switch to an empty entity
        $entities_id = $this->isolateInEntity('glpi', 'glpi');

        $glpi_computer_type_empty = $this->getItem(GlpiComputerType::class);

        $glpi_computer_type = $this->getItem(GlpiComputerType::class);

        $computer_type_empty = $this->getItem(ComputerType::class, [
            'computertypes_id' => $glpi_computer_type_empty->getID(),
            'power_consumption' => 0,
        ]);

        $computer_type = $this->getItem(ComputerType::class, [
            'computertypes_id' => $glpi_computer_type->getID(),
            'power_consumption' => 90,
        ]);

        $computer_model_empty = $this->getItem(ComputerModel::class, [
            'power_consumption' => 0,
        ]);

        $computer_model = $this->getItem(ComputerModel::class, [
            'power_consumption' => 150,
        ]);

        $location_empty = $this->getItem(Location::class);
        // $location_empty_2 = $this->getItem(Location::class, [
        //     'latitude' => 1,
        // ]);
        // $location_empty_3 = $this->getItem(Location::class, [
        //     'longitude' => 1,
        // ]);
        $location = $this->getItem(Location::class, [
            'country' => 'France',
        ]);

        $usage_profile_empty = $this->getItem(ComputerUsageProfile::class);
        $usage_profile = $this->getItem(ComputerUsageProfile::class, [
            'average_load' => 90,
        ]);

        $total_count = 0;
        $computers_definition = [
            Computer::class => [
                [
                    'computermodels_id' => $computer_model_empty->getID(),
                    'computertypes_id'  => $glpi_computer_type_empty->getID(),
                    'locations_id'      => $location_empty->getID(),
                ],
                [
                    'computermodels_id' => $computer_model->getID(),
                    'computertypes_id'  => $glpi_computer_type_empty->getID(),
                    'locations_id'      => $location_empty->getID(),
                ],
                [
                    'computermodels_id' => $computer_model_empty->getID(),
                    'computertypes_id'  => $glpi_computer_type->getID(),
                    'locations_id'      => $location_empty->getID(),
                ],
                [
                    'computermodels_id' => $computer_model->getID(),
                    'computertypes_id'  => $glpi_computer_type->getID(),
                    'locations_id'      => $location_empty->getID(),
                ],

                [
                    'computermodels_id' => $computer_model_empty->getID(),
                    'computertypes_id'  => $glpi_computer_type_empty->getID(),
                    'locations_id'      => $location->getID(),
                ],
                [
                    'computermodels_id' => $computer_model->getID(),
                    'computertypes_id'  => $glpi_computer_type_empty->getID(),
                    'locations_id'      => $location->getID(),
                ],
                [
                    'computermodels_id' => $computer_model_empty->getID(),
                    'computertypes_id'  => $glpi_computer_type->getID(),
                    'locations_id'      => $location->getID(),
                ],
                [
                    'computermodels_id' => $computer_model->getID(),
                    'computertypes_id'  => $glpi_computer_type->getID(),
                    'locations_id'      => $location->getID(),
                ],
            ]
        ];
        $computers = $this->getItems($computers_definition);
        $total_count += count($computers[Computer::class]);

        // Computers with a empty usage profile
        $computers = $this->getItems($computers_definition);
        $total_count += count($computers[Computer::class]);
        foreach ($computers[Computer::class] as $computers_id => $computer) {
            $impact = $this->getItem(EnvironnementalImpact::class, [
                'computers_id' => $computers_id,
                'plugin_carbon_computerusageprofiles_id' => $usage_profile_empty->getID(),
            ]);
        }

        // computers with a usage profile; 3 of them are complete
        $computers = $this->getItems($computers_definition);
        $total_count += count($computers[Computer::class]);
        foreach ($computers[Computer::class] as $computers_id => $computer) {
            $impact = $this->getItem(EnvironnementalImpact::class, [
                'computers_id' => $computers_id,
                'plugin_carbon_computerusageprofiles_id' => $usage_profile->getID(),
            ]);
        }

        return $total_count;
    }

    public function testGetHandledComputersCount()
    {
        $total_count = $this->handledComputersCountFixture();

        // 3 computers are complete
        // 1 having both power_consumption from computer type and computer model
        // 1 having both power_consumption from computer type only
        // 1 having both power_consumption from computer model only
        $handled_count = Provider::getHandledComputersCount();
        $this->assertEquals(3, $handled_count);
    }

    public function testGetUnhandledComputersCount()
    {
        $total_count = $this->handledComputersCountFixture();

        $unhandled_count = Provider::getUnhandledComputersCount();
        $this->assertEquals($total_count - 3, $unhandled_count);
    }

    public function testGetSumEmissionsPerModel()
    {
        $entities_id = $this->isolateInEntity('glpi', 'glpi');

        $computer_type    = $this->getItem(GlpiComputerType::class);
        $computer_model_1 = $this->getItem(ComputerModel::class);
        $computer_model_2 = $this->getItem(ComputerModel::class);
        $location = $this->getItem(Location::class, [
            'latitude'  => '48.864716',
            'longitude' => '2.349014',
            'country'   => 'France'
        ]);
        $computer_1 = $this->getItem(Computer::class);
        $computer_2 = $this->getItem(Computer::class);

        $date = new DateTime('now');
        $date->setTime(0, 0, 0);
        for ($shift = 1; $shift < 5; $shift++) {
            $date = $date->sub(new DateInterval('P1D'));
            $rows = [
                CarbonEmission::class => [
                    [
                        'itemtype'         => $computer_1::getType(),
                        'items_id'         => $computer_1->getID(),
                        'entities_id'      => $entities_id,
                        'types_id'         => $computer_type->getID(),
                        'models_id'        => $computer_model_1->getID(),
                        'locations_id'     => $location->getID(),
                        'energy_per_day'   => 0.5,
                        'emission_per_day' => 1,
                        'date'             => $date->format('Y-m-d'),
                    ], [
                        'itemtype'         => $computer_2::getType(),
                        'items_id'         => $computer_2->getID(),
                        'entities_id'      => $entities_id,
                        'types_id'         => $computer_type->getID(),
                        'models_id'        => $computer_model_2->getID(),
                        'locations_id'     => $location->getID(),
                        'energy_per_day'   => 1,
                        'emission_per_day' => 2,
                        'date'             => $date->format('Y-m-d'),
                    ],
                ]
            ];

            $items = $this->getItems($rows);
        }

        $output = Provider::getSumEmissionsPerModel();
        $expected = [
            [
                'number' => '4 gCO₂eq',
                'url' => ComputerModel::getFormURLWithID($computer_model_1->getID()),
                'label' => $computer_model_1->fields['name'] . " (1 Computer)",
            ], [
                'number' => '8 gCO₂eq',
                'url' => ComputerModel::getFormURLWithID($computer_model_2->getID()),
                'label' => $computer_model_2->fields['name'] . " (1 Computer)",
            ]
        ];
        $this->assertEquals($expected, $output);
    }

    public function testGetSumPowerPerModel()
    {
        $entities_id = $this->isolateInEntity('glpi', 'glpi');

        $country = $this->getUniqueString();
        $usage_profile = [
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
        ];
        $computer_1 = $this->createComputerUsageProfilePowerLocation($usage_profile, 60, $country);
        $computer_2 = $this->createComputerUsageProfilePowerLocation($usage_profile, 60, $country);
        $computer_3 = $this->createComputerUsageProfilePowerLocation($usage_profile, 60, $country);
        $computer_4 = $this->createComputerUsageProfilePowerLocation($usage_profile, 60, $country);

        $computer_model_1 = $this->getItem(ComputerModel::class, [
            'power_consumption' => 10
        ]);
        $computer_model_2 = $this->getItem(ComputerModel::class, [
            'power_consumption' => 40
        ]);

        $computer_1->update([
            'id' => $computer_1->getID(),
            ComputerModel::getForeignKeyField() => $computer_model_1->getID(),
        ]);
        $computer_2->update([
            'id' => $computer_2->getID(),
            ComputerModel::getForeignKeyField() => $computer_model_1->getID(),
        ]);
        $computer_3->update([
            'id' => $computer_3->getID(),
            ComputerModel::getForeignKeyField() => $computer_model_2->getID(),
        ]);
        $computer_4->update([
            'id' => $computer_4->getID(),
            ComputerModel::getForeignKeyField() => $computer_model_2->getID(),
        ]);

        $output = Provider::getSumPowerPerModel();
        $expected = [
            [
                'number' => 20.0,
                'url' => ComputerModel::getFormURLWithID($computer_model_1->getID()),
                'label' => $computer_model_1->fields['name'] . " (2 computers)",
            ], [
                'number' => 80.0,
                'url' => ComputerModel::getFormURLWithID($computer_model_2->getID()),
                'label' => $computer_model_2->fields['name'] . " (2 computers)",
            ]
        ];
        $this->assertEquals($expected, $output);
    }

    public function testGetCarbonEmissionPerMonth()
    {
        $country = $this->getUniqueString();
        $source  = $this->getUniqueString();
        $usage_profile = [
            'name' => 'Test laptop usage profile',
            'average_load' => 30,
            'time_start' => "09:00:00",
            'time_stop' => "17:00:00",
            'day_1' => 1,
            'day_2' => 1,
            'day_3' => 1,
            'day_4' => 1,
            'day_5' => 1,
            'day_6' => 1,
            'day_7' => 1,
        ];
        $computer_1 = $this->createComputerUsageProfilePowerLocation($usage_profile, 60, $country);
        $start_date = new DateTime('now');
        $start_date->modify('-5 month');
        $duration = 'P4M';
        $this->createCarbonIntensityData($country, $source, $start_date, 1, $duration);
        $this->createCarbonEmissionData($computer_1, $start_date, new DateInterval($duration), 1, 2);
        $output = Provider::getCarbonEmissionPerMonth();

        // TODO: check all values
    }
}
