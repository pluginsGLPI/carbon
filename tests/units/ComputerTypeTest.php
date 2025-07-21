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
