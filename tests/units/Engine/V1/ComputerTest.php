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

use DateTime;
use Computer as GlpiComputer;
use ComputerModel as GlpiComputerModel;
use ComputerType as GlpiComputerType;
use GlpiPlugin\Carbon\Engine\V1\Computer;
use GlpiPlugin\Carbon\Zone;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\Tests\Engine\V1\EngineTestCase;

class ComputerTest extends EngineTestCase
{
    protected static string $engine_class = Computer::class;
    protected static string $itemtype_class = GlpiComputer::class;
    protected static string $glpi_type_class = GlpiComputerType::class;
    protected static string $type_class = ComputerType::class;
    protected static string $model_class = GlpiComputerModel::class;

    const TEST_LAPTOP_USAGE_PROFILE = [
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
    const TEST_LAPTOP_POWER = 40 /* Watt */;
    // This computer is up 8 hours per day, from 09:00 to 17:00
    const TEST_LAPTOP_ENERGY_PER_DAY = (self::TEST_LAPTOP_POWER * 8 /* hours */) / 1000.0  /* kWh */;

    const TEST_SERVER_USAGE_PROFILE = [
        'name' => 'Test server usage profile',
        'time_start' => "00:00:00",
        'time_stop' => "23:00:00",
        'day_1' => 1,
        'day_2' => 1,
        'day_3' => 1,
        'day_4' => 1,
        'day_5' => 1,
        'day_6' => 1,
        'day_7' => 1,
    ];
    const TEST_SERVER_POWER = 150 /* Watt */;
    // This computer is up 24 hours per day, from 00:00 to 00:00 (next day)
    const TEST_SERVER_ENERGY_PER_DAY = (self::TEST_SERVER_POWER * 23 /* hours */) / 1000.0  /* kWh */;

    const TEST_CARBON_INTENSITY_THURSDAY = 1.0 /* gCO2/kWh */;
    const TEST_CARBON_INTENSITY_SATURDAY = 2.0 /* gCO2/kWh */;

    const TEST_CARBON_INTENSITY_SOURCE = 'Test source';

    // Thursday, December 2, 1999
    const TEST_DATE_THURSDAY = '1999-12-02 12:00:00';
    // Saturday, December 4, 1999
    const TEST_DATE_SATURDAY = '1999-12-04 12:00:00';

    const MODEL_NO_TYPE_POWER = 1;
    const NO_MODEL_TYPE_POWER = 2;
    const MODEL_TYPE_POWER = 3;

    /**
     * The delta for comparison of computed emission with expected value,
     * as == for float must not be used because of float representation.
     */
    const EPSILON = 0.001;

    public function computerUsageProfileProvider(): \Generator
    {
        $laptop_glpi_computer = $this->createComputerUsageProfile(self::TEST_LAPTOP_USAGE_PROFILE);
        yield 'Computer with laptop usage profile' => [
            new Computer($laptop_glpi_computer),
            self::TEST_LAPTOP_USAGE_PROFILE,
        ];

        $country = $this->getUniqueString();
        $server_glpi_computer = $this->createComputerUsageProfilePowerLocation(self::TEST_SERVER_USAGE_PROFILE, 150, $country);
        yield 'Computer with server usage profile' => [
            new Computer($server_glpi_computer),
            self::TEST_SERVER_USAGE_PROFILE,
        ];
    }


    public function testGetUsageProfile()
    {
        foreach ($this->computerUsageProfileProvider() as $data) {
            list ($computer, $usage_profile_params) = $data;
            $usage_profile = $computer->getUsageProfile();
            $this->assertNotNull($usage_profile);

            foreach ($usage_profile_params as $k => $v) {
                $this->assertEquals($usage_profile->fields[$k], $v);
            }
        }
    }

    public function getEnergyPerDayProvider(): \Generator
    {
        $laptop_glpi_computer = $this->createComputerUsageProfilePower(self::TEST_LAPTOP_USAGE_PROFILE, self::TEST_LAPTOP_POWER);
        yield 'Computer with laptop usage profile and type' => [
            new Computer($laptop_glpi_computer),
            new DateTime('2024-01-01 00:00:00'),
            self::TEST_LAPTOP_ENERGY_PER_DAY,
        ];

        $server_glpi_computer = $this->createComputerUsageProfilePower(self::TEST_SERVER_USAGE_PROFILE, self::TEST_SERVER_POWER);
        yield 'Computer with server usage profile and type' => [
            new Computer($server_glpi_computer),
            new DateTime('2024-01-01 00:00:00'),
            self::TEST_SERVER_ENERGY_PER_DAY,
        ];
    }

    public function getCarbonEmissionPerDateProvider(): \Generator
    {
        $country = $this->getUniqueString();
        $thursday = DateTime::createFromFormat('Y-m-d H:i:s', self::TEST_DATE_THURSDAY);
        $this->createCarbonIntensityData($country, self::TEST_CARBON_INTENSITY_SOURCE, $thursday, self::TEST_CARBON_INTENSITY_THURSDAY);
        $saturday = DateTime::createFromFormat('Y-m-d H:i:s', self::TEST_DATE_SATURDAY);
        $this->createCarbonIntensityData($country, self::TEST_CARBON_INTENSITY_SOURCE, $saturday, self::TEST_CARBON_INTENSITY_SATURDAY);
        $zone = new Zone();
        $zone->getFromDBByCrit(['name' => $country]);

        $laptop_glpi_computer = $this->createComputerUsageProfilePowerLocation(self::TEST_LAPTOP_USAGE_PROFILE, self::TEST_LAPTOP_POWER, $country);
        $laptop_computer = new Computer($laptop_glpi_computer);

        yield 'Computer with laptop usage profile and type on a Thursday' => [
            $laptop_computer,
            $thursday,
            $zone,
            self::TEST_LAPTOP_ENERGY_PER_DAY * self::TEST_CARBON_INTENSITY_THURSDAY,
        ];

        $profile = [
            'name' => 'Test laptop usage profile',
            'time_start' => "09:30:00",
            'time_stop' => "17:00:00",
            'day_1' => 1,
            'day_2' => 1,
            'day_3' => 1,
            'day_4' => 1,
            'day_5' => 1,
            'day_6' => 0,
            'day_7' => 0,
        ];
        $laptop_glpi_computer_2 = $this->createComputerUsageProfilePowerLocation($profile, self::TEST_LAPTOP_POWER, $country);
        yield 'Computer with laptop usage profile starting at half hour' => [
            new Computer($laptop_glpi_computer_2),
            $thursday,
            $zone,
            self::TEST_LAPTOP_POWER * 7.5 / 1000,
        ];

        $profile = [
            'name' => 'Test laptop usage profile',
            'time_start' => "09:00:00",
            'time_stop' => "17:15:00",
            'day_1' => 1,
            'day_2' => 1,
            'day_3' => 1,
            'day_4' => 1,
            'day_5' => 1,
            'day_6' => 0,
            'day_7' => 0,
        ];
        $laptop_glpi_computer_2 = $this->createComputerUsageProfilePowerLocation($profile, self::TEST_LAPTOP_POWER, $country);
        yield 'Computer with laptop usage profile ending at quarter hour' => [
            new Computer($laptop_glpi_computer_2),
            $thursday,
            $zone,
            self::TEST_LAPTOP_POWER * 8.25 / 1000,
        ];

        $profile = [
            'name' => 'Test laptop usage profile',
            'time_start' => "09:15:00",
            'time_stop' => "09:45:00",
            'day_1' => 1,
            'day_2' => 1,
            'day_3' => 1,
            'day_4' => 1,
            'day_5' => 1,
            'day_6' => 0,
            'day_7' => 0,
        ];
        $laptop_glpi_computer_3 = $this->createComputerUsageProfilePowerLocation($profile, self::TEST_LAPTOP_POWER, $country);
        yield 'Computer with laptop usage profile a few minutes in a single hour' => [
            new Computer($laptop_glpi_computer_3),
            $thursday,
            $zone,
            self::TEST_LAPTOP_POWER * 0.5 / 1000,
        ];

        $server_glpi_computer = $this->createComputerUsageProfilePowerLocation(self::TEST_SERVER_USAGE_PROFILE, self::TEST_SERVER_POWER, $country);
        $server_computer = new Computer($server_glpi_computer);
        yield 'Computer with server usage profile and type on a Thursday' => [
            $server_computer,
            $thursday,
            $zone,
            self::TEST_SERVER_ENERGY_PER_DAY * self::TEST_CARBON_INTENSITY_THURSDAY,
        ];

        yield 'Computer with laptop usage profile and type on a Saturday' => [
            $laptop_computer,
            $saturday,
            $zone,
            0.0,
        ];

        yield 'Computer with server usage profile and type on a Saturday' => [
            $server_computer,
            $saturday,
            $zone,
            self::TEST_SERVER_ENERGY_PER_DAY * self::TEST_CARBON_INTENSITY_SATURDAY,
        ];
    }



    private function computerSetModelWithPower(GlpiComputer $computer, int $power)
    {
        $glpi_computer_model = $this->createItem(GlpiComputerModel::class, [
            'power_consumption' => $power,
        ]);
        $success = $computer->update([
            'id'                                    => $computer->getID(),
            GlpiComputerModel::getForeignKeyField() => $glpi_computer_model->getID(),
        ]);
        $this->assertTrue($success);
    }

    private function computerSetTypeWithPower(GlpiComputer $computer, int $power)
    {
        $glpi_computer_type = $this->createItem(GlpiComputerType::class);
        $carbonComputerType = $this->createItem(ComputerType::class, [
            GlpiComputerType::getForeignKeyField() => $glpi_computer_type->getID(),
            'power_consumption'                    => $power,
        ]);
        $success = $computer->update([
            'id'                                   => $computer->getID(),
            GlpiComputerType::getForeignKeyField() => $glpi_computer_type->getID(),
        ]);
        $this->assertTrue($success);
    }

    public function getPowerProvider(): \Generator
    {
        // computer with no model and no type
        $computer_no_model_no_type = $this->createItem(GlpiComputer::class);
        $engine = new Computer($computer_no_model_no_type);
        yield 'Computer with no model and no type' => [$engine, 0];

        // computer with a model and no type
        $computer_model_no_type = $this->createItem(GlpiComputer::class);
        $this->computerSetModelWithPower($computer_model_no_type, self::MODEL_NO_TYPE_POWER);
        $engine = new Computer($computer_model_no_type);
        yield 'Computer with a model and no type' => [$engine, self::MODEL_NO_TYPE_POWER];

        // computer with no model and a type
        $computer_no_model_type = $this->createItem(GlpiComputer::class);
        $this->computerSetTypeWithPower($computer_no_model_type, self::NO_MODEL_TYPE_POWER);
        $engine = new Computer($computer_no_model_type);
        yield 'Computer with no model and a type' => [$engine, self::NO_MODEL_TYPE_POWER];

        // computer with a model and a type: model have priority
        $computer_model_type = $this->createItem(GlpiComputer::class);
        $this->computerSetModelWithPower($computer_model_type, self::MODEL_TYPE_POWER);
        $this->computerSetTypeWithPower($computer_model_type, 0);
        $engine = new Computer($computer_model_type);
        yield 'Computer with a model and a type' => [$engine, self::MODEL_TYPE_POWER];
    }
}
