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
        $success = $computer->update([
            'id'                                    => $computer->getID(),
            GlpiComputerModel::getForeignKeyField() => $glpi_computer_model->getID(),
        ]);
        $this->assertTrue($success);
    }

    private function computerSetTypeWithPower(Computer $computer, int $power)
    {
        $glpi_computer_type = $this->getItem(GlpiComputerType::class);
        $carbonComputerType = $this->getItem(ComputerType::class, [
            GlpiComputerType::getForeignKeyField() => $glpi_computer_type->getID(),
            'power_consumption'                    => $power,
        ]);
        $success = $computer->update([
            'id'                                   => $computer->getID(),
            GlpiComputerType::getForeignKeyField() => $glpi_computer_type->getID(),
        ]);
        $this->assertTrue($success);
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