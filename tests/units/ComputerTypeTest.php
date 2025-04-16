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

use ComputerType as GlpiComputerType;
use GlpiPlugin\Carbon\ComputerType;

class ComputerTypeTest extends DbTestCase
{
    public function testUpdatePowerConsumption()
    {
        $glpi_computer_type = $this->getItem(GlpiComputerType::class);

        ComputerType::updatePowerConsumption($glpi_computer_type, 10);
        $instance = new ComputerType();
        $instance->getFromDBByCrit([
            'computertypes_id' => $glpi_computer_type->getID(),
        ]);
        $this->assertFalse($instance->isNewItem());
        $this->assertEquals(10, $instance->fields['power_consumption']);

        ComputerType::updatePowerConsumption($glpi_computer_type, 42);
        // reload the object
        $instance->getFromDBByCrit([
            'computertypes_id' => $glpi_computer_type->getID(),
        ]);
        $this->assertEquals(42, $instance->fields['power_consumption']);
    }

    public function testUpdateCategory()
    {
        $glpi_computer_type = $this->getItem(GlpiComputerType::class);

        ComputerType::updateCategory($glpi_computer_type, ComputerType::CATEGORY_LAPTOP);
        $instance = new ComputerType();
        $instance->getFromDBByCrit([
            'computertypes_id' => $glpi_computer_type->getID(),
        ]);
        $this->assertFalse($instance->isNewItem());
        $this->assertEquals(ComputerType::CATEGORY_LAPTOP, $instance->fields['category']);

        ComputerType::updateCategory($glpi_computer_type, ComputerType::CATEGORY_SERVER);
        // reload the object
        $instance->getFromDBByCrit([
            'computertypes_id' => $glpi_computer_type->getID(),
        ]);
        $this->assertEquals(ComputerType::CATEGORY_SERVER, $instance->fields['category']);
    }
}
