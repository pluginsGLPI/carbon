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

use Computer as GlpiComputer;
use ComputerModel as GlpiComputerModel;
use ComputerType as GlpiComputerType;
use DateInterval;
use DateTime;
use Glpi\Asset\Asset_PeripheralAsset;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\Dashboard\Provider;
use GlpiPlugin\Carbon\Location;
use GlpiPlugin\Carbon\MonitorType;
use GlpiPlugin\Carbon\NetworkEquipmentType;
use GlpiPlugin\Carbon\Source;
use GlpiPlugin\Carbon\Source_Zone;
use GlpiPlugin\Carbon\UsageInfo;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use GlpiPlugin\Carbon\Zone;
use Infocom;
use Location as GlpiLocation;
use Monitor;
use MonitorType as GlpiMonitorType;
use NetworkEquipment;
use NetworkEquipmentType as GlpiNetworkEquipmentType;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Provider::class)]
class ProviderTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->login('glpi', 'glpi');
    }

    protected function handledComputersCountFixture_old(): int
    {
        // Switch to an empty entity
        $entities_id = $this->isolateInEntity('glpi', 'glpi');

        $glpi_computer_type_empty = $this->createItem(GlpiComputerType::class);

        $glpi_computer_type = $this->createItem(GlpiComputerType::class);

        $computer_type_empty = $this->createItem(ComputerType::class, [
            'computertypes_id' => $glpi_computer_type_empty->getID(),
            'power_consumption' => 0,
        ]);

        $computer_type = $this->createItem(ComputerType::class, [
            'computertypes_id' => $glpi_computer_type->getID(),
            'power_consumption' => 90,
        ]);

        $computer_model_empty = $this->createItem(GlpiComputerModel::class, [
            'power_consumption' => 0,
        ]);

        $computer_model = $this->createItem(GlpiComputerModel::class, [
            'power_consumption' => 150,
        ]);

        $glpi_location_empty = $this->createItem(GlpiLocation::class);
        $glpi_location = $this->createItem(GlpiLocation::class);
        $source = $this->createItem(Source::class, [
            'is_carbon_intensity_source' => 1
        ]);
        $zone = $this->createItem(Zone::class);
        $source_zone = $this->createItem(Source_Zone::class, [
            'plugin_carbon_sources_id' => $source->getID(),
            'plugin_carbo_zones_id'    => $zone->getID(),
        ]);
        $carbon_intensity = $this->createItem(CarbonIntensity::class, [
            'plugin_carbon_sources_id' => $source->getID(),
            'plugin_carbon_zones_id' => $zone->getID(),
        ]);
        $location = $this->createItem(Location::class, [
            'locations_id' => $glpi_location->getID(),
            $source_zone::getForeignKeyField() => $source_zone->getID(),
        ]);

        $usage_profile = $this->createItem(ComputerUsageProfile::class);

        $total_count = 0;
        $computers_definition = [
            GlpiComputer::class => [
                [
                    'computermodels_id' => $computer_model_empty->getID(),
                    'computertypes_id'  => $glpi_computer_type_empty->getID(),
                    'locations_id'      => $glpi_location_empty->getID(),
                ],
                [
                    'computermodels_id' => $computer_model->getID(),
                    'computertypes_id'  => $glpi_computer_type_empty->getID(),
                    'locations_id'      => $glpi_location_empty->getID(),
                ],
                [
                    'computermodels_id' => $computer_model_empty->getID(),
                    'computertypes_id'  => $glpi_computer_type->getID(),
                    'locations_id'      => $glpi_location_empty->getID(),
                ],
                [
                    'computermodels_id' => $computer_model->getID(),
                    'computertypes_id'  => $glpi_computer_type->getID(),
                    'locations_id'      => $glpi_location_empty->getID(),
                ],

                [
                    'computermodels_id' => $computer_model_empty->getID(),
                    'computertypes_id'  => $glpi_computer_type_empty->getID(),
                    'locations_id'      => $glpi_location->getID(),
                ],
                [
                    'computermodels_id' => $computer_model->getID(),
                    'computertypes_id'  => $glpi_computer_type_empty->getID(),
                    'locations_id'      => $glpi_location->getID(),
                ],
                [
                    'computermodels_id' => $computer_model_empty->getID(),
                    'computertypes_id'  => $glpi_computer_type->getID(),
                    'locations_id'      => $glpi_location->getID(),
                ],
                [
                    'computermodels_id' => $computer_model->getID(),
                    'computertypes_id'  => $glpi_computer_type->getID(),
                    'locations_id'      => $glpi_location->getID(),
                ],
            ]
        ];
        $computers = $this->createItems($computers_definition);
        $total_count += count($computers[GlpiComputer::class]);

        // computers with a usage profile; 3 of them are complete
        $computers = $this->createItems($computers_definition);
        $total_count += count($computers[GlpiComputer::class]);
        foreach ($computers[GlpiComputer::class] as $computers_id => $computer) {
            $impact = $this->createItem(UsageInfo::class, [
                'itemtype' => GlpiComputer::class,
                'items_id' => $computers_id,
                'plugin_carbon_computerusageprofiles_id' => $usage_profile->getID(),
            ]);
        }

        return $total_count;
    }

    protected function handledComputersCountFixture(): int
    {
        $glpi_computers = [];

        // Handled computer with all requirments
        $glpi_computer = $this->createHistorizableComputer();
        $glpi_computers[] = $glpi_computer;

        // Computer without
        // - fallback carbon intensity
        $glpi_computer = $this->createHistorizableComputer([
            'fallback_' . CarbonIntensity::class,
            '2nd_fallback_' . CarbonIntensity::class,
        ]);
        $glpi_computers[] = $glpi_computer;

        // Computer without
        // - fallback source_zone
        $glpi_computer = $this->createHistorizableComputer([
            'fallback_' . Source_Zone::class,
            'fallback_' . CarbonIntensity::class,
            '2nd_fallback_' . Source_Zone::class,
            '2nd_fallback_' . CarbonIntensity::class,
        ]);
        $glpi_computers[] = $glpi_computer;

        // Computer without
        // - realtime carbon intensity
        $glpi_computer = $this->createHistorizableComputer([CarbonIntensity::class]);
        $glpi_computers[] = $glpi_computer;

        // Computer without
        // - Type power consumption
        $glpi_computer = $this->createHistorizableComputer([ComputerType::class . '_power']);
        $glpi_computers[] = $glpi_computer;

        // Computer without
        // - plugin data for type
        $glpi_computer = $this->createHistorizableComputer([ComputerType::class]);
        $glpi_computers[] = $glpi_computer;

        // Computer without
        // - type
        $glpi_computer = $this->createHistorizableComputer([
            GlpiComputerType::class,
            ComputerType::class
        ]);
        $glpi_computers[] = $glpi_computer;

        // Computer without
        // - model power conssumption
        $glpi_computer = $this->createHistorizableComputer([GlpiComputerModel::class . '_power']);
        $glpi_computers[] = $glpi_computer;

        // Computer without
        // - model power conssumption
        // - type power consumption
        $glpi_computer = $this->createHistorizableComputer([
            ComputerType::class . '_power',
            GlpiComputerModel::class . '_power',
        ]);
        $glpi_computers[] = $glpi_computer;

        // Computer without
        // - model
        // - type
        $glpi_computer = $this->createHistorizableComputer([
            ComputerType::class,
            GlpiComputerModel::class,
        ]);
        $glpi_computers[] = $glpi_computer;

        // Computer without
        // - model
        $glpi_computer = $this->createHistorizableComputer([GlpiComputerModel::class]);
        $glpi_computers[] = $glpi_computer;

        // Computer without
        // - plugin location data
        $glpi_computer = $this->createHistorizableComputer([Location::class]);
        $glpi_computers[] = $glpi_computer;

        // Computer without
        // - any source_zone
        $glpi_computer = $this->createHistorizableComputer([
            Source_Zone::class,
            CarbonIntensity::class,
            'fallback_' . Source_Zone::class,
            'fallback_' . CarbonIntensity::class,
            '2nd_fallback_' . Source_Zone::class,
            '2nd_fallback_' . CarbonIntensity::class,
        ]);
        $glpi_computers[] = $glpi_computer;

        // Computer without
        // - location
        $glpi_computer = $this->createHistorizableComputer([GlpiLocation::class]);
        $glpi_computers[] = $glpi_computer;

        // Computer without
        // - Usage profile
        $glpi_computer = $this->createHistorizableComputer([ComputerUsageProfile::class]);
        $glpi_computers[] = $glpi_computer;

        // Computer
        // - as template
        $glpi_computer = $this->createHistorizableComputer();
        $glpi_computers[] = $glpi_computer;
        $this->assertTrue($glpi_computer->update(['is_template' => 1] + $glpi_computer->fields));

        // Computer
        // - deleted
        $glpi_computer = $this->createHistorizableComputer();
        $glpi_computers[] = $glpi_computer;
        $this->assertTrue($glpi_computer->update(['is_deleted' => 1] + $glpi_computer->fields));

        return count($glpi_computers);
    }

    public function testGetHandledComputersCount()
    {
        $total_count = $this->handledComputersCountFixture();

        // 9 computers fill historization requirements
        $handled_count = Provider::getHandledAssetCount(GlpiComputer::class, true);
        $this->assertEquals(9, $handled_count['number']);
    }

    public function testGetUnhandledComputersCount()
    {
        $total_count = $this->handledComputersCountFixture();

        $unhandled_count = Provider::getHandledAssetCount(GlpiComputer::class, false);
        $deleted_or_template_count = 2;
        $this->assertEquals($total_count - 9 - $deleted_or_template_count, $unhandled_count['number']);
    }

    public function testGetHandledAssetsRatio()
    {
        $total_count = $this->handledComputersCountFixture();
        $result = Provider::getHandledAssetsRatio([GlpiComputer::class]);
        $expected = 60; // This is a percentage
        $this->assertEquals($expected, $result['data'][0]['number']);
    }

    public function testGetSumUsageEmissionsPerModel()
    {
        $entities_id = $this->isolateInEntity('glpi', 'glpi');

        $source = $this->createItem(Source::class);
        $zone = $this->createItem(Zone::class);
        $source_zone = $this->createItem(Source_Zone::class, [
            'plugin_carbon_sources_id' => $source->getID(),
            'plugin_carbo_zones_id'    => $zone->getID(),
        ]);
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = $this->createItem(Location::class, [
            'locations_id' => $glpi_location->getID(),
            $source_zone::getForeignKeyField() => $source_zone->getID(),
        ]);
        $glpi_computer_model_1 = $this->createItem(GlpiComputerModel::class);
        $glpi_computer_model_2 = $this->createItem(GlpiComputerModel::class);
        $glpi_computer_model_3 = $this->createItem(GlpiComputerModel::class);
        $glpi_computer_type_3 = $this->createItem(GlpiComputerType::class);
        $glpi_computer_type  = $this->createItem(GlpiComputerType::class);
        $computer_type_3 = $this->createItem(ComputerType::class, [
            'computertypes_id' => $glpi_computer_type_3->getID(),
            'is_ignore'        => 1,
        ]);
        $computer_1 = $this->createItem(GlpiComputer::class, [
            'computermodels_id' => $glpi_computer_model_1->getID(),
        ]);
        $computer_2 = $this->createItem(GlpiComputer::class, [
            'computermodels_id' => $glpi_computer_model_2->getID(),
        ]);
        $computer_3 = $this->createItem(GlpiComputer::class, [
            'computermodels_id' => $glpi_computer_model_3->getID(),
            'computertypes_id'  => $glpi_computer_type_3->getID()
        ]);

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
                        'types_id'         => $glpi_computer_type->getID(),
                        'models_id'        => $glpi_computer_model_1->getID(),
                        'locations_id'     => $glpi_location->getID(),
                        'energy_per_day'   => 0.5,
                        'emission_per_day' => 1,
                        'date'             => $date->format('Y-m-d'),
                    ], [
                        'itemtype'         => $computer_2::getType(),
                        'items_id'         => $computer_2->getID(),
                        'entities_id'      => $entities_id,
                        'types_id'         => $glpi_computer_type->getID(),
                        'models_id'        => $glpi_computer_model_2->getID(),
                        'locations_id'     => $glpi_location->getID(),
                        'energy_per_day'   => 1,
                        'emission_per_day' => 2,
                        'date'             => $date->format('Y-m-d'),
                    ], [
                        'itemtype'         => $computer_3::getType(),
                        'items_id'         => $computer_3->getID(),
                        'entities_id'      => $entities_id,
                        'types_id'         => $glpi_computer_type->getID(),
                        'models_id'        => $glpi_computer_model_3->getID(),
                        'locations_id'     => $glpi_location->getID(),
                        'energy_per_day'   => 2,
                        'emission_per_day' => 4,
                        'date'             => $date->format('Y-m-d'),
                    ]
                ]
            ];

            $items = $this->createItems($rows);
        }

        $output = Provider::getSumUsageEmissionsPerModel();
        $expected = [
            'data' => [
                'series'   => [
                    8.0,
                    4.0,
                ],
                'labels'   => [
                    $glpi_computer_model_2->fields['name'] . " (1 Computer)",
                    $glpi_computer_model_1->fields['name'] . " (1 Computer)",
                ],
                'url' => [
                    GlpiComputer::getSearchURL() . '?criteria%5B0%5D%5Bfield%5D=40&criteria%5B0%5D%5Bsearchtype%5D=equals&criteria%5B0%5D%5Bvalue%5D=' . $glpi_computer_model_2->getID() . '&reset=reset',
                    GlpiComputer::getSearchURL() . '?criteria%5B0%5D%5Bfield%5D=40&criteria%5B0%5D%5Bsearchtype%5D=equals&criteria%5B0%5D%5Bvalue%5D=' . $glpi_computer_model_1->getID() . '&reset=reset',
                ],
                'unit' => 'g CO₂eq',
            ],
        ];
        $this->assertEquals($expected, $output);
    }

    public function testGetSumEmissionsPerType()
    {
        $entities_id = $this->isolateInEntity('glpi', 'glpi');

        $source = $this->createItem(Source::class);
        $zone = $this->createItem(Zone::class);
        $source_zone = $this->createItem(Source_Zone::class, [
            'plugin_carbon_sources_id' => $source->getID(),
            'plugin_carbo_zones_id'    => $zone->getID(),
        ]);
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = $this->createItem(Location::class, [
            'locations_id' => $glpi_location->getID(),
            $source_zone::getForeignKeyField() => $source_zone->getID(),
        ]);
        $glpi_computer_type_1    = $this->createItem(GlpiComputerType::class);
        $glpi_computer_type_2    = $this->createItem(GlpiComputerType::class);
        $glpi_computer_type_3    = $this->createItem(GlpiComputerType::class);
        $computer_type_3 = $this->createItem(ComputerType::class, [
            'computertypes_id' => $glpi_computer_type_3->getID(),
            'is_ignore'        => 1,
        ]);
        $computer_1 = $this->createItem(GlpiComputer::class, [
            'computertypes_id' => $glpi_computer_type_1->getID(),
        ]);
        $computer_2 = $this->createItem(GlpiComputer::class, [
            'computertypes_id' => $glpi_computer_type_2->getID(),
        ]);
        $computer_3 = $this->createItem(GlpiComputer::class, [
            'computertypes_id'  => $glpi_computer_type_3->getID()
        ]);

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
                        'types_id'         => $glpi_computer_type_1->getID(),
                        'locations_id'     => $glpi_location->getID(),
                        'energy_per_day'   => 0.5,
                        'emission_per_day' => 1,
                        'date'             => $date->format('Y-m-d'),
                    ], [
                        'itemtype'         => $computer_2::getType(),
                        'items_id'         => $computer_2->getID(),
                        'entities_id'      => $entities_id,
                        'types_id'         => $glpi_computer_type_2->getID(),
                        'locations_id'     => $glpi_location->getID(),
                        'energy_per_day'   => 1,
                        'emission_per_day' => 2,
                        'date'             => $date->format('Y-m-d'),
                    ], [
                        'itemtype'         => $computer_3::getType(),
                        'items_id'         => $computer_3->getID(),
                        'entities_id'      => $entities_id,
                        'types_id'         => $glpi_computer_type_3->getID(),
                        'locations_id'     => $glpi_location->getID(),
                        'energy_per_day'   => 2,
                        'emission_per_day' => 4,
                        'date'             => $date->format('Y-m-d'),
                    ]
                ]
            ];

            $items = $this->createItems($rows);
        }

        $output = Provider::getSumUsageEmissionsPerType();
        $expected = [
            'data' => [
                'series'   => [
                    8.0,
                    4.0,
                ],
                'labels'   => [
                    $glpi_computer_type_2->fields['name'] . " (1 Computer)",
                    $glpi_computer_type_1->fields['name'] . " (1 Computer)",
                ],
                'url' => [
                    GlpiComputer::getSearchURL() . '?criteria%5B0%5D%5Bfield%5D=40&criteria%5B0%5D%5Bsearchtype%5D=equals&criteria%5B0%5D%5Bvalue%5D=' . $glpi_computer_type_2->getID() . '&reset=reset',
                    GlpiComputer::getSearchURL() . '?criteria%5B0%5D%5Bfield%5D=40&criteria%5B0%5D%5Bsearchtype%5D=equals&criteria%5B0%5D%5Bvalue%5D=' . $glpi_computer_type_1->getID() . '&reset=reset',
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
        $source = $this->createItem(Source::class, [
            'is_carbon_intensity_source' => 1,
            'fallback_level' => 0
        ]);
        $zone = $this->createItem(Zone::class);
        $source_zone = $this->createItem(Source_Zone::class, [
            $source::getForeignKeyField() => $source->getID(),
            $zone::getForeignKeyField() => $zone->getID(),
        ]);
        $computer_1 = $this->createComputerUsageProfilePowerLocation($usage_profile, 60, $source_zone);
        $computer_2 = $this->createComputerUsageProfilePowerLocation($usage_profile, 60, $source_zone);
        $computer_3 = $this->createComputerUsageProfilePowerLocation($usage_profile, 60, $source_zone);
        $computer_4 = $this->createComputerUsageProfilePowerLocation($usage_profile, 60, $source_zone);

        $computer_model_1 = $this->createItem(GlpiComputerModel::class, [
            'power_consumption' => 10
        ]);
        $computer_model_2 = $this->createItem(GlpiComputerModel::class, [
            'power_consumption' => 40
        ]);

        $computer_1->update([
            'id' => $computer_1->getID(),
            GlpiComputerModel::getForeignKeyField() => $computer_model_1->getID(),
        ]);
        $computer_2->update([
            'id' => $computer_2->getID(),
            GlpiComputerModel::getForeignKeyField() => $computer_model_1->getID(),
        ]);
        $computer_3->update([
            'id' => $computer_3->getID(),
            GlpiComputerModel::getForeignKeyField() => $computer_model_2->getID(),
        ]);
        $computer_4->update([
            'id' => $computer_4->getID(),
            GlpiComputerModel::getForeignKeyField() => $computer_model_2->getID(),
        ]);

        $output = Provider::getSumPowerPerModel();
        $expected = [
            [
                'number' => 20.0,
                'url' => GlpiComputerModel::getFormURLWithID($computer_model_1->getID()),
                'label' => $computer_model_1->fields['name'] . " (2 computers)",
            ], [
                'number' => 80.0,
                'url' => GlpiComputerModel::getFormURLWithID($computer_model_2->getID()),
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
        $source = $this->createItem(Source::class, [
            'is_carbon_intensity_source' => 1,
            'fallback_level' => 0
        ]);
        $zone = $this->createItem(Zone::class);
        $source_zone = $this->createItem(Source_Zone::class, [
            $source::getForeignKeyField() => $source->getID(),
            $zone::getForeignKeyField() => $zone->getID(),
        ]);
        $computer_1 = $this->createComputerUsageProfilePowerLocation($usage_profile, 60, $source_zone);
        $infocom = $this->createItem(Infocom::class, [
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

    public function testGetIgnoredAssetCount()
    {
        // General guideline for this test
        // To avoid repeated calls to isolateInEntity,
        // For a given itemtype, we first test an item which shall be not ignored
        // Then we test an item which shall be ignored

        $entities_id = $this->isolateInEntity('glpi', 'glpi');

        // Test with a computer which must be taken into account
        $source = $this->createItem(Source::class);
        $zone = $this->createItem(Zone::class);
        $source_zone = $this->createItem(Source_Zone::class, [
            'plugin_carbon_sources_id' => $source->getID(),
            'plugin_carbo_zones_id'    => $zone->getID(),
        ]);
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = $this->createItem(Location::class, [
            'locations_id' => $glpi_location->getID(),
            $source_zone::getForeignKeyField() => $source_zone->getID(),
        ]);
        $usage_profile = $this->createItem(ComputerUsageProfile::class);
        $glpi_computer_type = $this->createItem(GlpiComputerType::class);
        $computer_type = $this->createItem(ComputerType::class, [
            'computertypes_id' => $glpi_computer_type->getID(),
            'power_consumption' => 90,
            'is_ignore' => 0,
        ]);
        $computer = $this->createItem(GlpiComputer::class, [
            'locations_id' => $glpi_location->getID(),
            'computertypes_id' => $glpi_computer_type->getID(),
        ]);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID(),
            'buy_date' => '2024-01-01',
        ]);
        $impact = $this->createItem(UsageInfo::class, [
            'itemtype' => GlpiComputer::class,
            'items_id' => $computer->getID(),
            'plugin_carbon_computerusageprofiles_id' => $usage_profile->getID(),
        ]);
        $handled_count = Provider::getIgnoredAssetCount(GlpiComputer::class);
        $this->assertEquals(0, $handled_count['number']);

        // Test with a computer which must be ignored
        $source = $this->createItem(Source::class);
        $zone = $this->createItem(Zone::class);
        $source_zone = $this->createItem(Source_Zone::class, [
            'plugin_carbon_sources_id' => $source->getID(),
            'plugin_carbo_zones_id'    => $zone->getID(),
        ]);
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = $this->createItem(Location::class, [
            'locations_id' => $glpi_location->getID(),
            $source_zone::getForeignKeyField() => $source_zone->getID(),
        ]);
        $usage_profile = $this->createItem(ComputerUsageProfile::class);
        $glpi_computer_type = $this->createItem(GlpiComputerType::class);
        $computer_type = $this->createItem(ComputerType::class, [
            'computertypes_id' => $glpi_computer_type->getID(),
            'power_consumption' => 90,
            'is_ignore' => 1,
        ]);
        $computer = $this->createItem(GlpiComputer::class, [
            'locations_id' => $glpi_location->getID(),
            'computertypes_id' => $glpi_computer_type->getID(),
        ]);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID(),
            'buy_date' => '2024-01-01',
        ]);
        $impact = $this->createItem(UsageInfo::class, [
            'itemtype' => GlpiComputer::class,
            'items_id' => $computer->getID(),
            'plugin_carbon_computerusageprofiles_id' => $usage_profile->getID(),
        ]);
        $handled_count = Provider::getIgnoredAssetCount(GlpiComputer::class);
        $this->assertEquals(1, $handled_count['number']);

        // Test with a monitor which must be taken into account
        $source = $this->createItem(Source::class);
        $zone = $this->createItem(Zone::class);
        $source_zone = $this->createItem(Source_Zone::class, [
            'plugin_carbon_sources_id' => $source->getID(),
            'plugin_carbo_zones_id'    => $zone->getID(),
        ]);
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = $this->createItem(Location::class, [
            'locations_id' => $glpi_location->getID(),
            $source_zone::getForeignKeyField() => $source_zone->getID(),
        ]);
        $usage_profile = $this->createItem(ComputerUsageProfile::class);
        $glpi_computer_type = $this->createItem(GlpiComputerType::class);
        $computer_type = $this->createItem(ComputerType::class, [
            'computertypes_id' => $glpi_computer_type->getID(),
            'power_consumption' => 90,
            'is_ignore' => 0,
        ]);
        $computer = $this->createItem(GlpiComputer::class, [
            'locations_id' => $glpi_location->getID(),
            'computertypes_id' => $glpi_computer_type->getID(),
        ]);
        $glpi_monitor_type = $this->createItem(GlpiMonitorType::class);
        $monitor_type = $this->createItem(MonitorType::class, [
            'monitortypes_id' => $glpi_monitor_type->getID(),
            'power_consumption' => 50,
            'is_ignore' => 0,
        ]);
        $monitor = $this->createItem(Monitor::class, [
            'locations_id' => $glpi_location->getID(),
            'monitortypes_id' => $glpi_monitor_type->getID(),
        ]);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype' => $monitor->getType(),
            'items_id' => $monitor->getID(),
            'buy_date' => '2024-01-01',
        ]);
        $asset_peripheralasset = $this->createItem(Asset_PeripheralAsset::class, [
            'itemtype_asset' => $computer->getType(),
            'items_id_asset' => $computer->getID(),
            'itemtype_peripheral' => $monitor->getType(),
            'items_id_peripheral' => $monitor->getID(),
        ]);
        $handled_count = Provider::getIgnoredAssetCount(Monitor::class);
        $this->assertEquals(0, $handled_count['number']);

        // Test with a monitor which must be ignored
        $source = $this->createItem(Source::class);
        $zone = $this->createItem(Zone::class);
        $source_zone = $this->createItem(Source_Zone::class, [
            'plugin_carbon_sources_id' => $source->getID(),
            'plugin_carbo_zones_id'    => $zone->getID(),
        ]);
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = $this->createItem(Location::class, [
            'locations_id' => $glpi_location->getID(),
            $source_zone::getForeignKeyField() => $source_zone->getID(),
        ]);
        $usage_profile = $this->createItem(ComputerUsageProfile::class);
        $glpi_computer_type = $this->createItem(GlpiComputerType::class);
        $computer_type = $this->createItem(ComputerType::class, [
            'computertypes_id' => $glpi_computer_type->getID(),
            'power_consumption' => 90,
            'is_ignore' => 0,
        ]);
        $computer = $this->createItem(GlpiComputer::class, [
            'locations_id' => $glpi_location->getID(),
            'computertypes_id' => $glpi_computer_type->getID(),
        ]);
        $glpi_monitor_type = $this->createItem(GlpiMonitorType::class);
        $monitor_type = $this->createItem(MonitorType::class, [
            'monitortypes_id' => $glpi_monitor_type->getID(),
            'power_consumption' => 50,
            'is_ignore' => 1,
        ]);
        $monitor = $this->createItem(Monitor::class, [
            'locations_id' => $glpi_location->getID(),
            'monitortypes_id' => $glpi_monitor_type->getID(),
        ]);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype' => $monitor->getType(),
            'items_id' => $monitor->getID(),
            'buy_date' => '2024-01-01',
        ]);
        $asset_peripheralasset = $this->createItem(Asset_PeripheralAsset::class, [
            'itemtype_asset' => $computer->getType(),
            'items_id_asset' => $computer->getID(),
            'itemtype_peripheral' => $monitor->getType(),
            'items_id_peripheral' => $monitor->getID(),
        ]);
        $handled_count = Provider::getIgnoredAssetCount(Monitor::class);
        $this->assertEquals(1, $handled_count['number']);

        // Test with a network equipment which must be taken into account
        $source = $this->createItem(Source::class);
        $zone = $this->createItem(Zone::class);
        $source_zone = $this->createItem(Source_Zone::class, [
            'plugin_carbon_sources_id' => $source->getID(),
            'plugin_carbo_zones_id'    => $zone->getID(),
        ]);
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = $this->createItem(Location::class, [
            'locations_id' => $glpi_location->getID(),
            $source_zone::getForeignKeyField() => $source_zone->getID(),
        ]);
        $glpi_network_equipment_type = $this->createItem(GlpiNetworkEquipmentType::class);
        $network_equipment_type = $this->createItem(NetworkEquipmentType::class, [
            'networkequipmenttypes_id' => $glpi_network_equipment_type->getID(),
            'power_consumption' => 90,
            'is_ignore' => 0,
        ]);
        $network_equipment = $this->createItem(NetworkEquipment::class, [
            'locations_id' => $glpi_location->getID(),
            'networkequipmenttypes_id' => $glpi_computer_type->getID(),
        ]);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype' => $network_equipment->getType(),
            'items_id' => $network_equipment->getID(),
            'buy_date' => '2024-01-01',
        ]);
        $handled_count = Provider::getIgnoredAssetCount(NetworkEquipment::class);
        $this->assertEquals(0, $handled_count['number']);

        // Test with a network equipment which must be ignored
        $source = $this->createItem(Source::class);
        $zone = $this->createItem(Zone::class);
        $source_zone = $this->createItem(Source_Zone::class, [
            'plugin_carbon_sources_id' => $source->getID(),
            'plugin_carbo_zones_id'    => $zone->getID(),
        ]);
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = $this->createItem(Location::class, [
            'locations_id' => $glpi_location->getID(),
            $source_zone::getForeignKeyField() => $source_zone->getID(),
        ]);
        $glpi_network_equipment_type = $this->createItem(GlpiNetworkEquipmentType::class);
        $network_equipment_type = $this->createItem(NetworkEquipmentType::class, [
            'networkequipmenttypes_id' => $glpi_network_equipment_type->getID(),
            'power_consumption' => 90,
            'is_ignore' => 1,
        ]);
        $network_equipment = $this->createItem(NetworkEquipment::class, [
            'locations_id' => $glpi_location->getID(),
            'networkequipmenttypes_id' => $glpi_network_equipment_type->getID(),
        ]);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype' => $network_equipment->getType(),
            'items_id' => $network_equipment->getID(),
            'buy_date' => '2024-01-01',
        ]);
        $handled_count = Provider::getIgnoredAssetCount(NetworkEquipment::class);
        $this->assertEquals(1, $handled_count['number']);
    }
}
