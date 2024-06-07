<?php
namespace GlpiPlugin\Carbon\Tests;

use Computer;
use GlpiPlugin\Carbon\ComputerPower;
use GlpiPlugin\Carbon\ComputerType;
use ComputerModel as GlpiComputerModel;
use ComputerType as GlpiComputerType;

class ComputerPowerTest extends DbTestCase
{
    const MODEL_NO_TYPE_POWER = 1;
    const NO_MODEL_TYPE_POWER = 2;
    const MODEL_TYPE_POWER = 3;

    private function computerSetModelWithPower(Computer $computer, int $power)
    {
        $glpi_computer_model = $this->getItem(GlpiComputerModel::class, [
            'power_consumption' => $power,
        ]);
        $computer->update([
            'id'                                    => $computer->getID(),
            GlpiComputerModel::getForeignKeyField() => $glpi_computer_model->getID(),
        ]);
    }

    private function computerSetTypeWithPower(Computer $computer, int $power)
    {
        $glpi_computer_type = $this->getItem(GlpiComputerType::class);
        $carbonComputerType = $this->getItem(ComputerType::class, [
            GlpiComputerType::getForeignKeyField() => $glpi_computer_type->getID(),
            'power_consumption'                    => $power,
        ]);
        $computer->update([
            'id'                                   => $computer->getID(),
            GlpiComputerType::getForeignKeyField() => $glpi_computer_type->getID(),
        ]);
    }

    public function computerPowerProvider() : \Generator
    {
        // computer with no model and no type
        $computer_no_model_no_type = $this->getItem(Computer::class);
        yield 'Computer with no model and no type' => [$computer_no_model_no_type, 0];

        // computer with a model and no type
        $computer_model_no_type = $this->getItem(Computer::class);
        $this->computerSetModelWithPower($computer_model_no_type, self::MODEL_NO_TYPE_POWER);
        yield 'Computer with a model and no type' => [$computer_model_no_type, self::MODEL_NO_TYPE_POWER];

        // computer with no model and a type
        $computer_no_model_type = $this->getItem(Computer::class);
        $this->computerSetTypeWithPower($computer_no_model_type, self::NO_MODEL_TYPE_POWER);
        yield 'Computer with no model and a type' => [$computer_no_model_type, self::NO_MODEL_TYPE_POWER];

        // computer with a model and a type: model have priority
        $computer_model_type = $this->getItem(Computer::class);
        $this->computerSetModelWithPower($computer_model_type, self::MODEL_TYPE_POWER);
        $this->computerSetTypeWithPower($computer_model_type, 0);
        yield 'Computer with a model and a type' => [$computer_model_type, self::MODEL_TYPE_POWER];
   }

   /**
    * @dataProvider computerPowerProvider
    */
    public function testGetPower(Computer $computer, int $expected_power)
    {
        $actual_power = ComputerPower::getPower($computer->getID());
        $this->assertEquals($expected_power, $actual_power);
    }
}