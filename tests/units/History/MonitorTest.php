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

namespace GlpiPlugin\Carbon\History\Tests;

use Computer as GlpiComputer;
use ComputerModel;
use Computer_Item;
use Monitor as GlpiMonitor;
use GlpiPlugin\Carbon\History\Monitor;
use GlpiPlugin\Carbon\Tests\History\CommonAsset;
use Location;
use DateTime;
use MonitorModel;
use MonitorType as GlpiMonitorType;
use ComputerType as GlpiComputerType;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\MonitorType;
use GlpiPlugin\Carbon\EnvironnementalImpact;

/**
 * @covers \GlpiPlugin\Carbon\History\NetworkEquipment
 */
class MonitorTest extends CommonAsset
{
    protected string $history_type = \GlpiPlugin\Carbon\History\Monitor::class;
    protected string $asset_type = GlpiMonitor::class;

    public function testGetEngine()
    {
        $asset = new GlpiMonitor();
        $engine = Monitor::getEngine($asset);
        $this->assertInstanceOf(\GlpiPlugin\Carbon\Engine\V1\Monitor::class, $engine);
    }


    public function testHistorizeItem()
    {
        global $DB;
        $this->login('glpi', 'glpi');
        $entities_id = $this->isolateInEntity('glpi', 'glpi');

        $model_power = 55;
        $location = $this->getItem(Location::class, [
            'country' => PLUGIN_CARBON_TEST_FAKE_ZONE_NAME,
        ]);

        $computer_model_power = 80;
        $computer_model = $this->getItem(ComputerModel::class, ['power_consumption' => $computer_model_power]);
        $glpi_computer_type = $this->getItem(GlpiComputerType::class);
        $computer_type = $this->getItem(ComputerType::class, [
            GlpiComputerType::getForeignKeyField() => $glpi_computer_type->getID(),
        ]);
        $computer = $this->getItem(GlpiComputer::class, [
            'computertypes_id'  => $glpi_computer_type->getID(),
            'computermodels_id' => $computer_model->getID(),
            'locations_id'      => $location->getID(),
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
        $impact = $this->getItem(EnvironnementalImpact::class, [
            $usage_profile->getForeignKeyField() => $usage_profile->getID(),
            'computers_id' => $computer->getID(),
        ]);

        $model = $this->getItem(MonitorModel::class, ['power_consumption' => $model_power]);
        $glpi_type = $this->getItem(GlpiMonitorType::class);
        $type = $this->getItem(MonitorType::class, [
            GlpiMonitorType::getForeignKeyField() => $glpi_type->getID(),
        ]);
        $asset = $this->getItem(GlpiMonitor::class, [
            'monitortypes_id'   => $glpi_type->getID(),
            'monitormodels_id'  => $model->getID(),
            'locations_id'      => $location->getID(),
            'date_creation'     => '2024-01-01',
            'date_mod'          => null,
        ]);
        $computer_asset = $this->getItem(Computer_Item::class, [
            'computers_id' => $computer->getID(),
            'itemtype' => $asset->getType(),
            'items_id' => $asset->getID(),
        ]);

        $history = new Monitor();
        $start_date = '2024-02-01 00:00:00';
        $end_date =   '2024-02-08 00:00:00';
        $count = $history->historizeItem(
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
}
