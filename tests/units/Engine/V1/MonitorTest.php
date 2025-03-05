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

namespace GlpiPlugin\Carbon\Engine\V1\Tests;

use Computer as GlpiComputer;
use Computer_Item;
use DateTime;
use GlpiPlugin\Carbon\Zone;
use GlpiPlugin\Carbon\Tests\Engine\V1\EngineTestCase;
use Monitor as GlpiMonitor;
use MonitorType as GlpiMonitorType;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\Engine\V1\Monitor;
use GlpiPlugin\Carbon\EnvironmentalImpact;
use GlpiPlugin\Carbon\MonitorType;
use GlpiPlugin\Carbon\Engine\V1\EngineInterface;
use MonitorModel;

class MonitorTest extends EngineTestCase
{
    protected static string $engine_class = Monitor::class;
    protected static string $itemtype_class = GlpiMonitor::class;
    protected static string $glpi_type_class = GlpiMonitorType::class;
    protected static string $type_class = MonitorType::class;
    protected static string $model_class = MonitorModel::class;

    /**
     * The delta for comparison of computed emission with expected value,
     * as == for float must not be used because of float representation.
     */
    const EPSILON = 0.001;

    public function testGetUsageProfile()
    {
        $computer = $this->getItem(GlpiComputer::class);
        $profile  = $this->getItem(ComputerUsageProfile::class);
        $impact   = $this->getItem(EnvironmentalImpact::class, [
            'computers_id' => $computer->getID(),
            'plugin_carbon_computerusageprofiles_id' => $profile->getID(),
        ]);
        $monitor = $this->getItem(GlpiMonitor::class);
        $computer_item = $this->getItem(Computer_Item::class, [
            'computers_id' => $computer->getID(),
            'itemtype'     => $monitor->getType(),
            'items_id'     => $monitor->getID(),
        ]);

        $engine = new Monitor($monitor->getID());
        $output = $engine->getUsageProfile();

        $this->assertEquals($output->getID(), $profile->getID());
    }

    public function getEnergyPerDayProvider(): \Generator
    {
        $profile = [
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
        $computer = $this->createComputerUsageProfile($profile);
        $model = $this->getItem(static::$model_class, ['power_consumption' => 20]);
        $item = $this->getItem(static::$itemtype_class, [
            static::$model_class::getForeignKeyField() => $model->getID(),
        ]);
        $computer_item = $this->getItem(Computer_Item::class, [
            'computers_id' => $computer->getID(),
            'itemtype' => $item->getType(),
            'items_id' => $item->getID(),
        ]);
        $engine = new static::$engine_class($item->getID());
        yield 'item in worked day' => [
            $engine,
            new DateTime('2024-01-01 00:00:00'),
            20 * 8 / 1000,
        ];

        $profile = [
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
        $computer = $this->createComputerUsageProfile($profile);
        $model = $this->getItem(static::$model_class, ['power_consumption' => 20]);
        $item = $this->getItem(static::$itemtype_class, [
            static::$model_class::getForeignKeyField() => $model->getID(),
        ]);
        $computer_item = $this->getItem(Computer_Item::class, [
            'computers_id' => $computer->getID(),
            'itemtype' => $item->getType(),
            'items_id' => $item->getID(),
        ]);
        $engine = new static::$engine_class($item->getID());
        yield 'item in week end' => [
            $engine,
            new DateTime('2024-01-06 00:00:00'),
            0,
        ];
    }

    public function getCarbonEmissionPerDateProvider(): \Generator
    {
        $country = $this->getUniqueString();
        $thursday = DateTime::createFromFormat('Y-m-d H:i:s', '1999-12-02 12:00:00');
        $intensity = 1;
        $this->createCarbonIntensityData($country, $this->getUniqueString(), $thursday, $intensity);
        $zone = new Zone();
        $zone->getFromDBByCrit(['name' => $country]);

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
        $computer = $this->createComputerUsageProfilePowerLocation($usage_profile, 40, $country);
        $glpi_monitor_type = $this->getItem(GlpiMonitorType::class);
        $monitory_type = $this->getItem(MonitorType::class, [
            'monitortypes_id'   => $glpi_monitor_type->getID(),
            'power_consumption' => 31,
        ]);
        $monitor = $this->getItem(GlpiMonitor::class, [
            'locations_id' => $computer->fields['locations_id'],
            'monitortypes_id' => $glpi_monitor_type->getID(),
        ]);
        $computer_item = $this->getItem(Computer_Item::class, [
            'computers_id' => $computer->getID(),
            'itemtype'     => $monitor->getType(),
            'items_id'     => $monitor->getID(),
        ]);

        /* 23 hours * 31 Watts */
        $engine = new Monitor($monitor->getID());
        $expected_emission = 8.0 * 31 / 1000 * $intensity; // in CO2eq/KWh
        yield 'Monitor' => [
            $engine,
            $thursday,
            $zone,
            $expected_emission,
        ];
    }
}
