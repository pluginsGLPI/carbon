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
use DbUtils;
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
        $location_empty_2 = $this->getItem(Location::class, [
            'latitude' => 1,
        ]);
        $location_empty_3 = $this->getItem(Location::class, [
            'longitude' => 1,
        ]);
        $location = $this->getItem(Location::class, [
            'latitude' => 1,
            'longitude' => 1,
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
                    'computertypes_id'  => $computer_type_empty->getID(),
                    'locations_id'      => $location_empty->getID(),
                ],
                [
                    'computermodels_id' => $computer_model->getID(),
                    'computertypes_id'  => $computer_type_empty->getID(),
                    'locations_id'      => $location_empty->getID(),
                ],
                [
                    'computermodels_id' => $computer_model_empty->getID(),
                    'computertypes_id'  => $computer_type->getID(),
                    'locations_id'      => $location_empty->getID(),
                ],
                [
                    'computermodels_id' => $computer_model->getID(),
                    'computertypes_id'  => $computer_type->getID(),
                    'locations_id'      => $location_empty->getID(),
                ],

                [
                    'computermodels_id' => $computer_model_empty->getID(),
                    'computertypes_id'  => $computer_type_empty->getID(),
                    'locations_id'      => $location_empty_2->getID(),
                ],
                [
                    'computermodels_id' => $computer_model->getID(),
                    'computertypes_id'  => $computer_type_empty->getID(),
                    'locations_id'      => $location_empty_2->getID(),
                ],
                [
                    'computermodels_id' => $computer_model_empty->getID(),
                    'computertypes_id'  => $computer_type->getID(),
                    'locations_id'      => $location_empty_2->getID(),
                ],
                [
                    'computermodels_id' => $computer_model->getID(),
                    'computertypes_id'  => $computer_type->getID(),
                    'locations_id'      => $location_empty_2->getID(),
                ],

                [
                    'computermodels_id' => $computer_model_empty->getID(),
                    'computertypes_id'  => $computer_type_empty->getID(),
                    'locations_id'      => $location_empty_3->getID(),
                ],
                [
                    'computermodels_id' => $computer_model->getID(),
                    'computertypes_id'  => $computer_type_empty->getID(),
                    'locations_id'      => $location_empty_3->getID(),
                ],
                [
                    'computermodels_id' => $computer_model_empty->getID(),
                    'computertypes_id'  => $computer_type->getID(),
                    'locations_id'      => $location_empty_3->getID(),
                ],
                [
                    'computermodels_id' => $computer_model->getID(),
                    'computertypes_id'  => $computer_type->getID(),
                    'locations_id'      => $location_empty_3->getID(),
                ],

                [
                    'computermodels_id' => $computer_model_empty->getID(),
                    'computertypes_id'  => $computer_type_empty->getID(),
                    'locations_id'      => $location->getID(),
                ],
                [
                    'computermodels_id' => $computer_model->getID(),
                    'computertypes_id'  => $computer_type_empty->getID(),
                    'locations_id'      => $location->getID(),
                ],
                [
                    'computermodels_id' => $computer_model_empty->getID(),
                    'computertypes_id'  => $computer_type->getID(),
                    'locations_id'      => $location->getID(),
                ],
                [
                    'computermodels_id' => $computer_model->getID(),
                    'computertypes_id'  => $computer_type->getID(),
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
        $handled_count = Provider::getHandledComputersCount([Computer::getTableField('entities_id') => Session::getActiveEntity()]);
        $this->assertEquals(3, $handled_count);
    }

    public function testGetUnhandledComputersCount()
    {
        $total_count = $this->handledComputersCountFixture();

        $unhandled_count = Provider::getUnhandledComputersCount([Computer::getTableField('entities_id') => Session::getActiveEntity()]);
        $this->assertEquals($total_count - 3, $unhandled_count);
    }
}
