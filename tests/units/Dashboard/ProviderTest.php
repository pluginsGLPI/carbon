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
use GlpiPlugin\Carbon\UsageInfo;
use GlpiPlugin\Carbon\Impact\History\Computer as HistoryComputer;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use Infocom;
use Location;
use PHPUnit\Framework\Attributes\CoversClass;
use Session;

use function PHPUnit\Framework\assertEquals;

#[CoversClass('GlpiPlugin\Carbon\Dashboard\Provider')]
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

        $usage_profile = $this->getItem(ComputerUsageProfile::class);

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

        // computers with a usage profile; 3 of them are complete
        $computers = $this->getItems($computers_definition);
        $total_count += count($computers[Computer::class]);
        foreach ($computers[Computer::class] as $computers_id => $computer) {
            $impact = $this->getItem(UsageInfo::class, [
                'itemtype' => Computer::class,
                'items_id' => $computers_id,
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
        $handled_count = Provider::getHandledAssetCount(Computer::class, true);
        $this->assertEquals(3, $handled_count['number']);
    }

    public function testGetUnhandledComputersCount()
    {
        $total_count = $this->handledComputersCountFixture();

        $unhandled_count = Provider::getHandledAssetCount(Computer::class, false);
        $this->assertEquals($total_count - 3, $unhandled_count['number']);
    }

    public function testGetHandledAssetsRatio()
    {
        $total_count = $this->handledComputersCountFixture();
        $result = Provider::getHandledAssetsRatio([Computer::class]);
        $expected = 19; // This is a percentage
        $this->assertEquals($expected, $result['data'][0]['number']);
    }

    public function testGetSumUsageEmissionsPerModel()
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

        // Create carbon emissions for the assets
        // $date = new DateTime('now');
        $date = new DateTime('2024-08-15');
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

        $output = Provider::getSumUsageEmissionsPerModel();
        $expected = [
            'data' => [
                'series'   => [
                    8.0,
                    4.0,
                ],
                'labels'   => [
                    $computer_model_2->fields['name'] . " (1 Computer)",
                    $computer_model_1->fields['name'] . " (1 Computer)",
                ],
                'url' => [
                    Computer::getSearchURL() . '?criteria%5B0%5D%5Bfield%5D=40&criteria%5B0%5D%5Bsearchtype%5D=equals&criteria%5B0%5D%5Bvalue%5D=' . $computer_model_2->getID() . '&reset=reset',
                    Computer::getSearchURL() . '?criteria%5B0%5D%5Bfield%5D=40&criteria%5B0%5D%5Bsearchtype%5D=equals&criteria%5B0%5D%5Bvalue%5D=' . $computer_model_1->getID() . '&reset=reset',
                ],
                'unit' => 'g CO₂eq',
            ],
        ];
        $this->assertEquals($expected, $output);
    }

    public function testGetSumPowerPerModel()
    {
        $entities_id = $this->isolateInEntity('glpi', 'glpi');

        $country = $this->getUniqueString();
        $usage_profile = [
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

    public function testGetUsageCarbonEmissionPerMonth()
    {
        $usage_profile = [
            'name' => 'Test laptop usage profile',
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
        $computer_1 = $this->createComputerUsageProfilePowerLocation($usage_profile, 60, PLUGIN_CARBON_TEST_FAKE_ZONE_NAME);
        $infocom = $this->getItem(Infocom::class, [
            'itemtype' => $computer_1->getType(),
            'items_id' => $computer_1->getID(),
            'buy_date' => '2024-01-01',
        ]);

        $start_date = new DateTime('now');
        $start_date->modify('-5 month');
        $date_cursor = new DateTime('2024-02-01');
        $end_date = new DateTime('2024-05-31');
        $emission = new CarbonEmission();
        while ($date_cursor <= $end_date) {
            $emission->add([
                'itemtype'         => $computer_1->getType(),
                'items_id'         => $computer_1->getID(),
                'entities_id'      => $computer_1->fields['entities_id'],
                'types_id'         => $computer_1->fields['computertypes_id'],
                'models_id'        => $computer_1->fields['computermodels_id'],
                'locations_id'     => $computer_1->fields['locations_id'],
                'energy_per_day'   => 1,
                'emission_per_day' => 20,
                'date'             => $date_cursor->format('Y-m-d 00:00:00'),
                'energy_quality'   => 1,
                'emission_quality' => 1,
            ]);
            $date_cursor->add(new DateInterval('P1D'));
        }
        $result = Provider::getUsageCarbonEmissionPerMonth([
        ], [
            'args' => [
                'apply_filters' => [
                    'dates' => [
                        '2024-02-01T00:00:00.000Z',
                        '2024-06-01T00:00:00.000Z',
                    ],
                ],
            ],
            'label' => '',
            'icon' => '',
        ]);
        $expected = [
            0 => [
                'data' => [
                    0 => [
                        'x' => '2024-02',
                        'y' => '580.000',
                    ],
                    1 => [
                        'x' => '2024-03',
                        'y' => '620.000',
                    ],
                    2 => [
                        'x' => '2024-04',
                        'y' => '600.000',
                    ],
                    3 => [
                        'x' => '2024-05',
                        'y' => '620.000',
                    ],
                ],
                'name' => 'Carbon emission (gCO₂eq)',
                'unit' => 'gCO₂eq',
            ],
            1 => [
                'data' => [
                    0 => [
                        'x' => '2024-02',
                        'y' => '29.000',
                    ],
                    1 => [
                        'x' => '2024-03',
                        'y' => '31.000',
                    ],
                    2 => [
                        'x' => '2024-04',
                        'y' => '30.000',
                    ],
                    3 => [
                        'x' => '2024-05',
                        'y' => '31.000',
                    ],
                ],
                'name' => 'Consumed energy (KWh)',
                'unit' => 'KWh',
            ],
        ];

        $this->assertEquals($expected, $result['data']['series']);
    }
}
