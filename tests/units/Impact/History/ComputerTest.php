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

namespace GlpiPlugin\Carbon\Impact\History\Tests;

use CommonDBTM;
use Computer as GlpiComputer;
use DateInterval;
use GlpiPlugin\Carbon\Impact\History\Computer;
use GlpiPlugin\Carbon\Tests\Impact\History\CommonAsset;
use Location;
use ComputerModel as GlpiComputerModel;
use ComputerType as GlpiComputerType;
use DateTime;
use Infocom;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\UsageInfo;
use GlpiPlugin\Carbon\Zone;

/**
 * @covers \GlpiPlugin\Carbon\Impact\History\Computer
 */
class ComputerTest extends CommonAsset
{
    protected string $history_type = \GlpiPlugin\Carbon\Impact\History\Computer::class;
    protected string $asset_type = GlpiComputer::class;

    public function testGetEngine()
    {
        $asset = new GlpiComputer();
        $engine = Computer::getEngine($asset);
        $this->assertInstanceOf(\GlpiPlugin\Carbon\Engine\V1\Computer::class, $engine);
    }

    public function testEvaluateItem()
    {
        /** @var DBmysql $DB */
        global $DB;

        // Check DBMS version as this tests does not works on minimal DBMS requirement of GLPI 10.0
        $db_version_full = $DB->getVersion();
        // Isolate version number
        $db_version = preg_replace('/[^0-9.]/', '', $db_version_full);
        // Check if is MariaDB
        $min_version = '8.0';
        if (strpos($db_version_full, 'MariaDB') !== false) {
            $min_version = '10.2';
        }
        if (version_compare($db_version, $min_version, '<') || version_compare($db_version, $min_version, '<')) {
            $this->markTestSkipped('Test requires MySQL 8.0 or MariaDB 10.2');
        }

        $this->login('glpi', 'glpi');
        $entities_id = $this->isolateInEntity('glpi', 'glpi');

        $model_power = 55;
        $location = $this->getItem(Location::class, [
            'state' => 'Quebec',
        ]);
        $model = $this->getItem(GlpiComputerModel::class, ['power_consumption' => $model_power]);
        $glpi_type = $this->getItem(GlpiComputerType::class);
        $type = $this->getItem(ComputerType::class, [
            GlpiComputerType::getForeignKeyField() => $glpi_type->getID(),
        ]);
        $asset = $this->getItem(GlpiComputer::class, [
            'computertypes_id'  => $glpi_type->getID(),
            'computermodels_id' => $model->getID(),
            'locations_id'      => $location->getID(),
            'date_creation'     => '2024-01-01',
            'date_mod'          => null,
        ]);
        $usage_profile = $this->getItem(ComputerUsageProfile::class, [
            'time_start'   => '09:00:00',
            'time_stop'    => '18:00:00',
            'day_1'        => '1',
            'day_2'        => '1',
            'day_3'        => '1',
            'day_4'        => '1',
            'day_5'        => '1',
            'day_6'        => '0',
            'day_7'        => '0',
        ]);
        $impact = $this->getItem(UsageInfo::class, [
            $usage_profile->getForeignKeyField() => $usage_profile->getID(),
            'itemtype' => $asset->getType(),
            'items_id' => $asset->getID(),
        ]);

        $history = new Computer();
        $start_date = '2024-02-01 00:00:00';
        $end_date =   '2024-02-08 00:00:00';

        $count = $history->evaluateItem(
            $asset->getID(),
            new DateTime($start_date),
            new DateTime($end_date)
        );

        // Days interval is [$start_date, $end_date[
        $this->assertEquals(7, $count);

        $carbon_emission = new CarbonEmission();
        $emissions = $carbon_emission->find([
            ['date' => ['>=', $start_date]],
            ['date' =>  ['<', $end_date]],
            'itemtype' => $asset->getType(),
            'items_id' => $asset->getID(),
        ], [
            'date ASC',
        ]);
        $this->assertEquals(7, count($emissions));

        $expected = [
            [
                'date'             => '2024-02-01 00:00:00',
                'energy_per_day'   => 0.495,
                'emission_per_day' => 234.138,
            ],[
                'date' => '2024-02-02 00:00:00',
                'energy_per_day'   => 0.495,
                'emission_per_day' => 234.138,
            ], [
                'date' => '2024-02-03 00:00:00',
                'energy_per_day'   => 0,
                'emission_per_day' => 0,
            ], [
                'date' => '2024-02-04 00:00:00',
                'energy_per_day'   => 0,
                'emission_per_day' => 0,
            ], [
                'date' => '2024-02-05 00:00:00',
                'energy_per_day'   => 0.495,
                'emission_per_day' => 234.138,
            ], [
                'date' => '2024-02-06 00:00:00',
                'energy_per_day'   => 0.495,
                'emission_per_day' => 234.138,
            ], [
                'date' => '2024-02-07 00:00:00',
                'energy_per_day'   => 0.495,
                'emission_per_day' => 234.138,
            ],
        ];
        foreach ($emissions as $emission) {
            $expected_row = array_shift($expected);
            $emission = array_intersect_key($emission, $expected_row);
            $this->assertEquals($expected_row, $emission);
        }
    }

    public function testCanHistorize()
    {
        $computer = $this->getItem(GlpiComputer::class);
        $id = $computer->getID();

        // Check we cannot historize an empty item
        $history = new Computer();
        $this->assertFalse($history->canHistorize($id));

        // Add empty info on the asset
        $management = $this->getItem(Infocom::class, [
            'itemtype' => $computer->getType(),
            'items_id' => $id,
        ]);
        $this->assertFalse($history->canHistorize($id));

        // Add a date of inventory entry
        $management->update([
            'id' => $management->getID(),
            'use_date' => '2020-01-01',
        ]);
        $this->assertFalse($history->canHistorize($id));

        // Add an empty location
        $location = $this->getItem(Location::class);
        $computer->update([
            'id' => $id,
            'locations_id' => $location->getID(),
        ]);
        $this->assertFalse($history->canHistorize($id));

        // Add a country to the location
        $location->update([
            'id' => $location->getID(),
            'country' => 'France',
        ]);
        $this->assertFalse($history->canHistorize($id));

        // Add a usage profile
        $usage_profile = $this->getItem(ComputerUsageProfile::class);
        $this->assertFalse($history->canHistorize($id));
        $impact = $this->getItem(UsageInfo::class, [
            $usage_profile->getForeignKeyField() => $usage_profile->getID(),
            'itemtype' => $computer->getType(),
            'items_id' => $id,
        ]);
        $this->assertFalse($history->canHistorize($id));

        // Add a model
        $model = $this->getItem(GlpiComputerModel::class);
        $computer->update([
            'id' => $id,
            'computermodels_id' => $model->getID(),
        ]);
        $this->assertFalse($history->canHistorize($id));

        // Add a power consumption to the model
        $model->update([
            'id' => $model->getID(),
            'power_consumption' => 55,
        ]);
        $this->assertTrue($history->canHistorize($id));

        // add a type
        $type = $this->getItem(GlpiComputerType::class);
        $computer->update([
            'id' => $id,
            'computertypes_id' => $type->getID(),
        ]);
        $this->assertTrue($history->canHistorize($id));

        // Remove power consumption on model
        $model->update([
            'id' => $model->getID(),
            'power_consumption' => 0,
        ]);
        $this->assertFalse($history->canHistorize($id));

        // add a type power consumption
        $power_consumption = $this->getItem(ComputerType::class, [
            GlpiComputerType::getForeignKeyField() => $type->getID(),
        ]);
        $this->assertFalse($history->canHistorize($id));

        // Set a type power consumption
        $power_consumption->update([
            'id' => $power_consumption->getID(),
            'power_consumption' => 55,
        ]);
        $this->assertTrue($history->canHistorize($id));

        // Add a power consumption to the model (both model and type have power consumption)
        $model->update([
            'id' => $model->getID(),
            'power_consumption' => 55,
        ]);
        $this->assertTrue($history->canHistorize($id));

        // *** test blocking conditions ***

        // Put the asset in the trash bin
        $computer->update([
            'id' => $id,
            'is_deleted' => 1,
        ]);
        $this->assertFalse($history->canHistorize($id));

        // Restore the asset
        $computer->update([
            'id' => $id,
            'is_deleted' => 0,
        ]);

        // Transform the asset into a template
        $computer->update([
            'id' => $id,
            'is_template' => 1,
        ]);
        $this->assertFalse($history->canHistorize($id));

        // Restore the asset
        $computer->update([
            'id' => $id,
            'is_template' => 0,
        ]);
        $this->assertTrue($history->canHistorize($id));
    }

    public function testEmptyComputerIsNotHistorizable()
    {
        $history = new Computer();

        $computer = $this->getItem(GlpiComputer::class);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => false,
            'has_state_or_country'        => false,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_usage_profile'           => false,
            'has_category'                => false,
            'has_inventory_entry_date'    => false,
        ];

        $result = $history->getHistorizableDiagnosis($computer);
        $this->assertEquals($expected, $result);
        $result = $history->canHistorize($computer->getID());
        $this->assertFalse($result);
    }

    public function testComputerWithEmptyInfocomIsNotHistorizable()
    {
        $history = new Computer();

        $computer = $this->getItem(GlpiComputer::class);
        $management = $this->getItem(Infocom::class, [
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID(),
        ]);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => false,
            'has_state_or_country'        => false,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_usage_profile'           => false,
            'has_category'                => false,
            'has_inventory_entry_date'    => false,
        ];
        $result = $history->getHistorizableDiagnosis($computer);
        $this->assertEquals($expected, $result);
        $result = $history->canHistorize($computer->getID());
        $this->assertFalse($result);
    }

    public function testComputerWithEntryDateIsNotHistorizable()
    {
        $history = new Computer();

        $computer = $this->getItem(GlpiComputer::class);
        $management = $this->getItem(Infocom::class, [
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID(),
            'use_date' => '2020-01-01',
        ]);

        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => false,
            'has_state_or_country'        => false,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_usage_profile'           => false,
            'has_category'                => false,
            'has_inventory_entry_date'    => true,
        ];
        $result = $history->getHistorizableDiagnosis($computer);
        $this->assertEquals($expected, $result);
        $result = $history->canHistorize($computer->getID());
        $this->assertFalse($result);
    }

    public function testComputerWithEmptyLocationIsNotHistorizable()
    {
        $history = new Computer();

        $location = $this->getItem(Location::class);
        $computer = $this->getItem(GlpiComputer::class, [
            'locations_id' => $location->getID(),
        ]);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => true,
            'has_state_or_country'        => false,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_usage_profile'           => false,
            'has_category'                => false,
            'has_inventory_entry_date'    => false,
        ];
        $result = $history->getHistorizableDiagnosis($computer);
        $this->assertEquals($expected, $result);
        $result = $history->canHistorize($computer->getID());
        $this->assertFalse($result);
    }

    public function testComputerWithLocationAndCountryIsNotHistorizable()
    {
        $history = new Computer();

        $location = $this->getItem(Location::class, [
            'country' => 'France'
        ]);
        $computer = $this->getItem(GlpiComputer::class, [
            'locations_id' => $location->getID(),
        ]);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => true,
            'has_state_or_country'        => true,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_usage_profile'           => false,
            'has_category'                => false,
            'has_inventory_entry_date'    => false,
        ];
        $result = $history->getHistorizableDiagnosis($computer);
        $this->assertEquals($expected, $result);
        $result = $history->canHistorize($computer->getID());
        $this->assertFalse($result);
    }

    public function testComputerWithLocationAndStateIsNotHistorizable()
    {
        $history = new Computer();

        $location = $this->getItem(Location::class, [
            'state' => 'Quebec'
        ]);
        $computer = $this->getItem(GlpiComputer::class, [
            'locations_id' => $location->getID(),
        ]);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => true,
            'has_state_or_country'        => true,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_usage_profile'           => false,
            'has_category'                => false,
            'has_inventory_entry_date'    => false,
        ];
        $result = $history->getHistorizableDiagnosis($computer);
        $this->assertEquals($expected, $result);
        $result = $history->canHistorize($computer->getID());
        $this->assertFalse($result);
    }

    public function testComputerWithUsageProfileIsNotHistorizable()
    {
        $history = new Computer();

        $computer = $this->getItem(GlpiComputer::class);
        $usage_profile = $this->getItem(ComputerUsageProfile::class);
        $impact = $this->getItem(UsageInfo::class, [
            $usage_profile->getForeignKeyField() => $usage_profile->getID(),
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID(),
        ]);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => false,
            'has_state_or_country'        => false,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_usage_profile'           => true,
            'has_category'                => false,
            'has_inventory_entry_date'    => false,
        ];
        $result = $history->getHistorizableDiagnosis($computer);
        $this->assertEquals($expected, $result);
        $result = $history->canHistorize($computer->getID());
        $this->assertFalse($result);
    }

    public function testComputerWithEmptyModelIsNotHistorizable()
    {
        $history = new Computer();

        $model = $this->getItem(GlpiComputerModel::class);
        $computer = $this->getItem(GlpiComputer::class, [
            'computermodels_id' => $model->getID(),
        ]);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => false,
            'has_state_or_country'        => false,
            'has_model'                   => true,
            'has_model_power_consumption' => false,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_usage_profile'           => false,
            'has_category'                => false,
            'has_inventory_entry_date'    => false,
        ];
        $result = $history->getHistorizableDiagnosis($computer);
        $this->assertEquals($expected, $result);
        $result = $history->canHistorize($computer->getID());
        $this->assertFalse($result);
    }

    public function testComputerWithModelIsNotHistorizable()
    {
        $history = new Computer();

        $model = $this->getItem(GlpiComputerModel::class, [
            'power_consumption' => 55,
        ]);
        $computer = $this->getItem(GlpiComputer::class, [
            'computermodels_id' => $model->getID(),
        ]);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => false,
            'has_state_or_country'        => false,
            'has_model'                   => true,
            'has_model_power_consumption' => true,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_usage_profile'           => false,
            'has_category'                => false,
            'has_inventory_entry_date'    => false,
        ];
        $result = $history->getHistorizableDiagnosis($computer);
        $this->assertEquals($expected, $result);
        $result = $history->canHistorize($computer->getID());
        $this->assertFalse($result);
    }

    public function testComputerWithEmptyTypeIsNotHistorizable()
    {
        $history = new Computer();

        $glpi_computer_type = $this->getItem(GlpiComputerType::class);
        $computer = $this->getItem(GlpiComputer::class, [
            'computertypes_id' => $glpi_computer_type->getID(),
        ]);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => false,
            'has_state_or_country'        => false,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => true,
            'has_type_power_consumption'  => false,
            'has_usage_profile'           => false,
            'has_category'                => false,
            'has_inventory_entry_date'    => false,
        ];
        $result = $history->getHistorizableDiagnosis($computer);
        $this->assertEquals($expected, $result);
        $result = $history->canHistorize($computer->getID());
        $this->assertFalse($result);
    }

    public function testComputerWithTypeIsNotHistorizable()
    {
        $history = new Computer();

        $glpi_computer_type = $this->getItem(GlpiComputerType::class);
        $computer_type = $this->getItem(ComputerType::class, [
            'power_consumption' => 55,
            'computertypes_id' => $glpi_computer_type->getID(),
        ]);
        $computer = $this->getItem(GlpiComputer::class, [
            'computertypes_id' => $glpi_computer_type->getID(),
        ]);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => false,
            'has_state_or_country'        => false,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => true,
            'has_type_power_consumption'  => true,
            'has_usage_profile'           => false,
            'has_category'                => false,
            'has_inventory_entry_date'    => false,
        ];
        $result = $history->getHistorizableDiagnosis($computer);
        $this->assertEquals($expected, $result);
        $result = $history->canHistorize($computer->getID());
        $this->assertFalse($result);
    }

    public function testComputerWithCategoryIsNotHistorizable()
    {
        $history = new Computer();

        $glpi_computer_type = $this->getItem(GlpiComputerType::class);
        $computer_type = $this->getItem(ComputerType::class, [
            'category' => ComputerType::CATEGORY_DESKTOP,
            'computertypes_id' => $glpi_computer_type->getID(),
        ]);
        $computer = $this->getItem(GlpiComputer::class, [
            'computertypes_id' => $glpi_computer_type->getID(),
        ]);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => false,
            'has_state_or_country'        => false,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => true,
            'has_type_power_consumption'  => false,
            'has_usage_profile'           => false,
            'has_category'                => true,
            'has_inventory_entry_date'    => false,
        ];
        $result = $history->getHistorizableDiagnosis($computer);
        $this->assertEquals($expected, $result);
        $result = $history->canHistorize($computer->getID());
        $this->assertFalse($result);
    }

    public function testComputerWithEverythingIsHistorizable()
    {
        $history = new Computer();

        $location = $this->getItem(Location::class, [
            'country' => 'France'
        ]);
        $glpi_computer_type = $this->getItem(GlpiComputerType::class);
        $computer_type = $this->getItem(ComputerType::class, [
            'power_consumption' => 55,
            'computertypes_id' => $glpi_computer_type->getID(),
        ]);
        $computer = $this->getItem(GlpiComputer::class, [
            'locations_id' => $location->getID(),
            'computertypes_id' => $glpi_computer_type->getID(),
        ]);
        $management = $this->getItem(Infocom::class, [
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID(),
            'use_date' => '2020-01-01',
        ]);
        $usage_profile = $this->getItem(ComputerUsageProfile::class);
        $impact = $this->getItem(UsageInfo::class, [
            $usage_profile->getForeignKeyField() => $usage_profile->getID(),
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID(),
        ]);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => true,
            'has_state_or_country'        => true,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => true,
            'has_type_power_consumption'  => true,
            'has_usage_profile'           => true,
            'has_category'                => false,
            'has_inventory_entry_date'    => true,
        ];
        $result = $history->getHistorizableDiagnosis($computer);
        $this->assertEquals($expected, $result);
        $result = $history->canHistorize($computer->getID());
        $this->assertTrue($result);
    }
}
