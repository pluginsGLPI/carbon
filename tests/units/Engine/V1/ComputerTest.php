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

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use DateInterval;
use CommonDBTM;
use Computer as GlpiComputer;
use ComputerModel as GlpiComputerModel;
use ComputerType as GlpiComputerType;
use Location;
use Plugin;
use GlpiPlugin\Carbon\Engine\V1\Computer;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\EnvironnementalImpact;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\CarbonIntensityZone;
use GlpiPlugin\Carbon\Tests\DbTestCase;

class ComputerTest extends DbTestCase
{
    const TEST_LAPTOP_USAGE_PROFILE = [
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
    const TEST_LAPTOP_POWER = 40 /* Watt */;
    const TEST_LAPTOP_ENERGY_PER_DAY = 0.320 /* kWh */;

    const TEST_SERVER_USAGE_PROFILE = [
        'name' => 'Test server usage profile',
        'average_load' => 50,
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
    const TEST_SERVER_ENERGY_PER_DAY = 3.450 /* kWh */;

    const TEST_CARBON_INTENSITY_1 = 1.0 /* gCO2/kWh */;
    const TEST_CARBON_INTENSITY_2 = 2.0 /* gCO2/kWh */;

    const TEST_CARBON_INTENSITY_SOURCE = 'Test source';

    const TEST_DATE_1 = '1999-12-02 12:00:00';

    /**
     * The delta for comparison of computed emission with expected value,
     * as == for float must not be used because of float representation.
     */
    const EPSILON = 0.001;

    public function computerUsageProfileProvider(): \Generator
    {
        $laptop_glpi_computer = $this->createComputerUsageProfile(self::TEST_LAPTOP_USAGE_PROFILE);
        yield 'Computer with laptop usage profile' => [
            new Computer($laptop_glpi_computer->getID()),
            self::TEST_LAPTOP_USAGE_PROFILE,
        ];

        $server_glpi_computer = $this->createComputerUsageProfile(self::TEST_SERVER_USAGE_PROFILE);
        yield 'Computer with server usage profile' => [
            new Computer($server_glpi_computer->getID()),
            self::TEST_SERVER_USAGE_PROFILE,
        ];
    }

    /**
     * @dataProvider computerUsageProfileProvider
     */
    public function testUsageProfile(Computer $computer, array $usage_profile_params)
    {
        $usage_profile = $computer->getUsageProfile();
        $this->assertNotNull($usage_profile);

        foreach ($usage_profile_params as $k => $v) {
            $this->assertEquals($usage_profile[$k], $v);
        }
    }

    /**
     * @dataProvider computerUsageProfileProvider
     */
    public function testUsageDay(Computer $computer, array $usage_profile_params)
    {
        $sunday = new DateTime('2023-12-31 00:00:00', new DateTimeZone('UTC'));
        $emission = $computer->getCarbonEmissionPerDay($sunday);

        $day_7 = $usage_profile_params['day_7'];
        if ($day_7 == 0) {
            $this->assertTrue($emission == 0.0);
        } else {
            $this->assertTrue(is_null($emission) || $emission != 0.0);
        }
    }

    public function computerUsageProfilePowerProvider(): \Generator
    {
        $laptop_glpi_computer = $this->createComputerUsageProfilePower(self::TEST_LAPTOP_USAGE_PROFILE, self::TEST_LAPTOP_POWER);
        yield 'Computer with laptop usage profile and type' => [
            new Computer($laptop_glpi_computer->getID()),
            self::TEST_LAPTOP_ENERGY_PER_DAY,
        ];

        $server_glpi_computer = $this->createComputerUsageProfilePower(self::TEST_SERVER_USAGE_PROFILE, self::TEST_SERVER_POWER);
        yield 'Computer with server usage profile and type' => [
            new Computer($server_glpi_computer->getID()),
            self::TEST_SERVER_ENERGY_PER_DAY,
        ];
    }

    /**
     * @dataProvider computerUsageProfilePowerProvider
     */
    public function testEnergy(Computer $computer, float $expected_energy)
    {
        $monday = new DateTime('2024-01-01 00:00:00', new DateTimeZone('UTC'));
        $this->assertEquals($computer->getEnergyPerDay($monday), $expected_energy);
    }

    public function computerCarbonIntensityProvider(): \Generator
    {
        $country = $this->getUniqueString();
        $day_1 = DateTime::createFromFormat('Y-m-d H:i:s', self::TEST_DATE_1, new DateTimeZone('UTC'));
        $this->createCarbonIntensityData($country, self::TEST_CARBON_INTENSITY_SOURCE, $day_1, self::TEST_CARBON_INTENSITY_1);

        $laptop_glpi_computer = $this->createComputerUsageProfilePowerLocation(self::TEST_LAPTOP_USAGE_PROFILE, self::TEST_LAPTOP_POWER, $country);

        yield 'Computer with laptop usage profile and type' => [
            new Computer($laptop_glpi_computer->getID()),
            $day_1,
            self::TEST_LAPTOP_ENERGY_PER_DAY * self::TEST_CARBON_INTENSITY_1,
        ];

        $server_glpi_computer = $this->createComputerUsageProfilePowerLocation(self::TEST_SERVER_USAGE_PROFILE, self::TEST_SERVER_POWER, $country);

        yield 'Computer with server usage profile and type' => [
            new Computer($server_glpi_computer->getID()),
            $day_1,
            self::TEST_SERVER_ENERGY_PER_DAY * self::TEST_CARBON_INTENSITY_1,
        ];
    }

    /**
     *
     * @dataProvider computerCarbonIntensityProvider
     */
    public function testEmission(Computer $computer, DateTime $day, float $expected_emission)
    {
        $emission = $computer->getCarbonEmissionPerDay($day);
        $this->assertEqualsWithDelta($expected_emission, $emission, self::EPSILON);
    }
}
