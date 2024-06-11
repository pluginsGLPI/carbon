<?php

namespace GlpiPlugin\Carbon\Engine\V1\Tests;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use DateInterval;
use CommonDBTM;
use Computer as GlpiComputer;
use ComputerModel as GlpiComputerModel;
use ComputerType as GlpiComputerType;
use Plugin;
use GlpiPlugin\Carbon\Engine\V1\Computer;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\EnvironnementalImpact;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\CarbonIntensityZone;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use GlpiPlugin\Carbon\Tests\CommonTestCase;

class ComputerTest extends CommonTestCase
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

    const TEST_SERVER_PROFILE = [
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

    public function computerWithUsageProfileProvider() : array
    {
        $usage_profile = $this->getItem(ComputerUsageProfile::class, self::TEST_LAPTOP_USAGE_PROFILE);
        $laptop_glpi_computer = $this->getItem(GlpiComputer::class);
        $impact = $this->getItem(EnvironnementalImpact::class, [
            'computers_id' => $laptop_glpi_computer->getId(),
            'plugin_carbon_computerusageprofiles_id' => $usage_profile->getID(),
        ]);

        $usage_profile = $this->getItem(ComputerUsageProfile::class, self::TEST_SERVER_PROFILE);
        $server_glpi_computer = $this->getItem(GlpiComputer::class);
        $impact = $this->getItem(EnvironnementalImpact::class, [
            'computers_id' => $server_glpi_computer->getId(),
            'plugin_carbon_computerusageprofiles_id' => $usage_profile->getID(),
        ]);

        return [
            'Computer with laptop usage profile' => [
                new Computer($laptop_glpi_computer->getID()),
                self::TEST_LAPTOP_USAGE_PROFILE,
            ],
            'Computer with server usage profile' => [
                new Computer($server_glpi_computer->getID()),
                self::TEST_SERVER_PROFILE,
            ],
        ];
    }

    /**
     * @dataProvider computerWithUsageProfileProvider
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
     * @dataProvider computerWithUsageProfileProvider
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

    public function computerWithUsageProfileAndModelProvider() : array
    {
        $usage_profile = $this->getItem(ComputerUsageProfile::class, self::TEST_LAPTOP_USAGE_PROFILE);
        $laptop_glpi_computer = $this->getItem(GlpiComputer::class, [ 'name' => 'Test laptop']);
        $impact = $this->getItem(EnvironnementalImpact::class, [
            'computers_id' => $laptop_glpi_computer->getId(),
            'plugin_carbon_computerusageprofiles_id' => $usage_profile->getID(),
        ]);
        $glpiComputerType = $this->getItem(GlpiComputerType::class);
        $carbonComputerType = $this->getItem(ComputerType::class, [
            GlpiComputerType::getForeignKeyField() => $glpiComputerType->getID(),
            'power_consumption'                    => self::TEST_LAPTOP_POWER,
        ]);
        $laptop_glpi_computer->update([
            'id'                                   => $laptop_glpi_computer->getID(),
            GlpiComputerType::getForeignKeyField() => $glpiComputerType->getID(),
        ]);
        // $glpiComputerModel = $this->getItem(GlpiComputerModel::class, [
        //     'name' => 'Test laptop model',
        //     'power_consumption' => self::TEST_LAPTOP_POWER,
        // ]);
        // $laptop_glpi_computer->update([
        //     'id'                                    => $laptop_glpi_computer->getID(),
        //     GlpiComputerModel::getForeignKeyField() => $glpiComputerModel->getID(),
        // ]);

        $usage_profile = $this->getItem(ComputerUsageProfile::class, self::TEST_SERVER_PROFILE);
        $server_glpi_computer = $this->getItem(GlpiComputer::class, [ 'name' => 'Test server']);
        $impact = $this->getItem(EnvironnementalImpact::class, [
            'computers_id' => $server_glpi_computer->getId(),
            'plugin_carbon_computerusageprofiles_id' => $usage_profile->getID(),
        ]);
        $glpiComputerType = $this->getItem(GlpiComputerType::class);
        $carbonComputerType = $this->getItem(ComputerType::class, [
            GlpiComputerType::getForeignKeyField() => $glpiComputerType->getID(),
            'power_consumption'                    => self::TEST_SERVER_POWER,
        ]);
        $server_glpi_computer->update([
            'id'                                   => $server_glpi_computer->getID(),
            GlpiComputerType::getForeignKeyField() => $glpiComputerType->getID(),
        ]);
        // $glpiComputerModel = $this->getItem(GlpiComputerModel::class, [
        //     'name' => 'Test server model',
        //     'power_consumption' => self::TEST_SERVER_POWER,
        // ]);
        // $server_glpi_computer->update([
        //     'id'                                    => $server_glpi_computer->getID(),
        //     GlpiComputerModel::getForeignKeyField() => $glpiComputerModel->getID(),
        // ]);

        return [
            'Computer with laptop usage profile and model' => [
                new Computer($laptop_glpi_computer->getID()),
                0.320,
            ],
            'Computer with server usage profile and model' => [
                new Computer($server_glpi_computer->getID()),
                3.450,
            ],
        ];
    }

    /**
     * @dataProvider computerWithUsageProfileAndModelProvider
     */
    public function testEnergy(Computer $computer, float $expected_power)
    {
        $monday = new DateTime('2024-01-01 00:00:00', new DateTimeZone('UTC'));
        $this->assertEquals($computer->getEnergyPerDay($monday), $expected_power);
    }

    private function createObjectIfNotExist(string $class_name, array $crit) : CommonDBTM
    {
        $obj = new $class_name();
        $ret = $obj->getFromDBByCrit($crit);
        if (!$ret) {
            $obj = $this->getItem($class_name, $crit);
        }
        return $obj;
    }

    private function createCarbonIntensityData(string $zone_name, string $source_name, DateTime $begin_date)
    {
        $zone = $this->createObjectIfNotExist(CarbonIntensityZone::class, [ 'name' => $zone_name ]);

        $source = $this->createObjectIfNotExist(CarbonIntensitySource::class, [ 'name' => $source_name ]);

        $end_date = clone $begin_date;
        $end_date->add(new DateInterval('P2D'));
        $one_hour = new DateInterval('PT1H');
        while ($begin_date < $end_date) {
            $crit = [
                'emission_date' => $begin_date->format('Y-m-d H:i:s'),
                'plugin_carbon_carbonintensitysources_id' => $source->getID(),
                'plugin_carbon_carbonintensityzones_id' => $zone->getID(),
                'intensity' => 1.0,
            ];
            $emission = $this->createObjectIfNotExist(CarbonIntensity::class, $crit);
            $begin_date->add($one_hour);
        }
    }

    /**
     * @group WIP
     */
    public function testCarbonIntensity()
    {
        $this->createCarbonIntensityData('France', 'RTE', new Datetime('1999-12-01 12:00:00', new DateTimeZone('UTC')));

        $this->assertTrue(true);
    }

    /**
     * @group WIP2
     */
    public function testCarbonEmission()
    {
        $computer = new Computer(1);

        $emissions = $computer->getCarbonEmissionPerDay(new Datetime('1999-12-02', new DateTimeZone('UTC')));
        print_r($emissions);

        $this->assertTrue(true);
    }

}