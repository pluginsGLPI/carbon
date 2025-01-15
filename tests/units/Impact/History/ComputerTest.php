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

namespace GlpiPlugin\Carbon\Impact\History\Tests;

use Computer as GlpiComputer;
use GlpiPlugin\Carbon\Impact\History\Computer;
use GlpiPlugin\Carbon\Tests\Impact\History\CommonAsset;
use Location;
use ComputerModel;
use ComputerType as GlpiComputerType;
use DateTime;
use Infocom;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\EnvironmentalImpact;

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
        $this->login('glpi', 'glpi');
        $entities_id = $this->isolateInEntity('glpi', 'glpi');

        $model_power = 55;
        $location = $this->getItem(Location::class, [
            'country' => PLUGIN_CARBON_TEST_FAKE_ZONE_NAME,
        ]);
        $model = $this->getItem(ComputerModel::class, ['power_consumption' => $model_power]);
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
        $impact = $this->getItem(EnvironmentalImpact::class, [
            $usage_profile->getForeignKeyField() => $usage_profile->getID(),
            'computers_id' => $asset->getID(),
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
        $this->assertEquals(8, $count);

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

        // Values from the fake carbon intensities added in global fixtures
        $expected = [
            [
                'date'             => '2024-02-01 00:00:00',
                'energy_per_day'   => 0.495,
                'emission_per_day' => 14.245,
            ],[
                'date' => '2024-02-02 00:00:00',
                'energy_per_day'   => 0.495,
                'emission_per_day' => 14.025,
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
                'emission_per_day' => 14.41,
            ], [
                'date' => '2024-02-06 00:00:00',
                'energy_per_day'   => 0.495,
                'emission_per_day' => 15.895,
            ], [
                'date' => '2024-02-07 00:00:00',
                'energy_per_day'   => 0.495,
                'emission_per_day' => 12.98,
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
        $impact = $this->getItem(EnvironmentalImpact::class, [
            $usage_profile->getForeignKeyField() => $usage_profile->getID(),
            'computers_id' => $id,
        ]);

        // Add a model
        $model = $this->getItem(ComputerModel::class);
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
        $this->assertFalse($history->canHistorize($id));

        // add a type
        $type = $this->getItem(GlpiComputerType::class);
        $computer->update([
            'id' => $id,
            'computertypes_id' => $type->getID(),
        ]);
        $this->assertFalse($history->canHistorize($id));

        // add a type power consumption
        $power_consumption = $this->getItem(ComputerType::class, [
            GlpiComputerType::getForeignKeyField() => $type->getID(),
        ]);
        $this->assertTrue($history->canHistorize($id));

        // Set a type power consumption
        $power_consumption->update([
            'id' => $power_consumption->getID(),
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
}
