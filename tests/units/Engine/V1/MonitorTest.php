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
use GlpiPlugin\Carbon\UsageInfo;
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
        $computer = $this->createItem(GlpiComputer::class);
        $profile  = $this->createItem(ComputerUsageProfile::class);
        $impact   = $this->createItem(UsageInfo::class, [
            'itemtype' => GlpiComputer::class,
            'items_id' => $computer->getID(),
            'plugin_carbon_computerusageprofiles_id' => $profile->getID(),
        ]);
        $monitor = $this->createItem(GlpiMonitor::class);
        $computer_item = $this->createItem(Computer_Item::class, [
            'computers_id' => $computer->getID(),
            'itemtype'     => $monitor->getType(),
            'items_id'     => $monitor->getID(),
        ]);

        $engine = new Monitor($monitor);
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
        $model = $this->createItem(static::$model_class, ['power_consumption' => 20]);
        $item = $this->createItem(static::$itemtype_class, [
            static::$model_class::getForeignKeyField() => $model->getID(),
        ]);
        $computer_item = $this->createItem(Computer_Item::class, [
            'computers_id' => $computer->getID(),
            'itemtype' => $item->getType(),
            'items_id' => $item->getID(),
        ]);
        $engine = new static::$engine_class($item);
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
        $model = $this->createItem(static::$model_class, ['power_consumption' => 20]);
        $item = $this->createItem(static::$itemtype_class, [
            static::$model_class::getForeignKeyField() => $model->getID(),
        ]);
        $computer_item = $this->createItem(Computer_Item::class, [
            'computers_id' => $computer->getID(),
            'itemtype' => $item->getType(),
            'items_id' => $item->getID(),
        ]);
        $engine = new static::$engine_class($item);
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
        $glpi_monitor_type = $this->createItem(GlpiMonitorType::class);
        $monitory_type = $this->createItem(MonitorType::class, [
            'monitortypes_id'   => $glpi_monitor_type->getID(),
            'power_consumption' => 31,
        ]);
        $monitor = $this->createItem(GlpiMonitor::class, [
            'locations_id' => $computer->fields['locations_id'],
            'monitortypes_id' => $glpi_monitor_type->getID(),
        ]);
        $computer_item = $this->createItem(Computer_Item::class, [
            'computers_id' => $computer->getID(),
            'itemtype'     => $monitor->getType(),
            'items_id'     => $monitor->getID(),
        ]);

        /* 23 hours * 31 Watts */
        $engine = new Monitor($monitor);
        $expected_emission = 8.0 * 31 / 1000 * $intensity; // in CO2eq/KWh
        yield 'Monitor' => [
            $engine,
            $thursday,
            $zone,
            $expected_emission,
        ];
    }
}
