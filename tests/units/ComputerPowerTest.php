<?php
namespace GlpiPlugin\Carbon\Tests;

use Computer;
use GlpiPlugin\Carbon\ComputerPower;
use GlpiPlugin\Carbon\ComputerType;
use ComputerModel as GlpiComputerModel;
use ComputerType as GlpiComputerType;

class ComputerPowerTest extends DbTestCase
{
    public function testGetPower()
    {
        $computer = $this->getItem(Computer::class);

        // Test a computer without any type or model
        $power = ComputerPower::getPower($computer->getID());
        $this->assertEquals(0, $power);

        $expected = 42;
        $glpiComputerType = $this->getItem(GlpiComputerType::class);
        $carbonComputerType = $this->getItem(ComputerType::class, [
            GlpiComputerType::getForeignKeyField() => $glpiComputerType->getID(),
            'power_consumption'                    => $expected,
        ]);
        $computer->update([
            'id'                                   => $computer->getID(),
            GlpiComputerType::getForeignKeyField() => $glpiComputerType->getID(),
        ]);

        // Test a computer with a type
        $power = ComputerPower::getPower($computer->getID());
        $this->assertEquals($expected, $power);

        $expected = 128;
        $glpiComputerModel = $this->getItem(GlpiComputerModel::class, [
            'power_consumption' => $expected,
        ]);
        $computer->update([
            'id'                                    => $computer->getID(),
            GlpiComputerModel::getForeignKeyField() => $glpiComputerModel->getID(),
        ]);

        // Test a computer with a model
        $power = ComputerPower::getPower($computer->getID());
        $this->assertEquals($expected, $power);
    }
}