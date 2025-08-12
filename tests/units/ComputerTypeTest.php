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

use Computer;
use ComputerType as GlpiComputerType;
use GlpiPlugin\Carbon\ComputerType;
use MassiveAction;

class ComputerTypeTest extends DbTestCase
{
    /**
     * @covers GlpiPlugin\Carbon\ComputerType::updatePowerConsumption
     *
     * @return void
     */
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

    /**
     * @covers GlpiPlugin\Carbon\ComputerType::updateCategory
     *
     * @return void
     */
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

    public function testShowMassiveActionsSubForm()
    {
        // Test power consumption update form
        $massive_action = $this->getMockBuilder(MassiveAction::class)
            ->disableOriginalConstructor()
            ->getMock();
        $massive_action->method('getAction')->willReturn('MassUpdatePower');
        $massive_action->method('getItems')->willReturn([
            ComputerType::class => $this->getItem(GlpiComputerType::class)
        ]);
        ob_start(function ($buffer) {
            return $buffer;
        });
        $result = ComputerType::showMassiveActionsSubForm($massive_action);
        $output = ob_get_clean();
        $this->assertStringContainsString('<input type="number" name="power_consumption" class="form-control" />', $output);
        $this->assertStringContainsString('<button type=\'submit\' value=\'Post\' name="massiveaction" class="btn">', $output);
        $this->assertTrue($result);

        // Test category update form
        $massive_action = $this->getMockBuilder(MassiveAction::class)
            ->disableOriginalConstructor()
            ->getMock();
        $massive_action->method('getAction')->willReturn('MassUpdateCategory');
        $massive_action->method('getItems')->willReturn([
            ComputerType::class => $this->getItem(GlpiComputerType::class)
        ]);
        ob_start(function ($buffer) {
            return $buffer;
        });
        $result = ComputerType::showMassiveActionsSubForm($massive_action);
        $output = ob_get_clean();
        $pattern = preg_quote('<select name=\'category\' id=\'dropdown_category');
        $pattern .= '\d{0,10}';
        $pattern .= preg_quote('\' class="form-select" size=\'1\'>');
        $this->assertMatchesRegularExpression('#' . $pattern . '#', $output);
        $this->assertStringContainsString('<button type=\'submit\' value=\'Post\' name="massiveaction" class="btn">', $output);
        $this->assertTrue($result);

        // Test invalid action
        $massive_action = $this->getMockBuilder(MassiveAction::class)
            ->disableOriginalConstructor()
            ->getMock();
        $massive_action->method('getAction')->willReturn('');
        $massive_action->method('getItems')->willReturn([
            ComputerType::class => $this->getItem(GlpiComputerType::class)
        ]);
        ob_start(function ($buffer) {
            return $buffer;
        });
        $result = ComputerType::showMassiveActionsSubForm($massive_action);
        $output = ob_get_clean();
        $this->assertEquals('', $output);
        $this->assertFalse($result);
    }

    /**
     * @covers GlpiPlugin\Carbon\ComputerType::processMassiveActionsForOneItemtype
     *
     * @return void
     */
    public function testProcessMassiveActionForOneItemtype()
    {
        // Test create power consumption
        $massive_action = $this->getMockBuilder(MassiveAction::class)
            ->disableOriginalConstructor()
            ->getMock();
        $massive_action->method('getAction')->willReturn('MassUpdatePower');
        $glpi_computer_type = $this->getItem(GlpiComputerType::class);
        $massive_action->POST = [
            'power_consumption' => 55,
        ];
        ComputerType::processMassiveActionsForOneItemtype($massive_action, $glpi_computer_type, [$glpi_computer_type->getID()]);
        $computer_type = new ComputerType();
        $computer_type->getFromDBByCrit(['computertypes_id' => $glpi_computer_type->getID()]);
        $this->assertEquals(55, $computer_type->fields['power_consumption']);

        // Test update power consumption
        $massive_action->POST = [
            'power_consumption' => 25,
        ];
        ComputerType::processMassiveActionsForOneItemtype($massive_action, $glpi_computer_type, [$glpi_computer_type->getID()]);
        $computer_type->getFromDB($computer_type->getID());
        $this->assertEquals(25, $computer_type->fields['power_consumption']);

        // Test update category
        $massive_action = $this->getMockBuilder(MassiveAction::class)
            ->disableOriginalConstructor()
            ->getMock();
        $massive_action->method('getAction')->willReturn('MassUpdateCategory');
        $glpi_computer_type = $this->getItem(GlpiComputerType::class);
        $massive_action->POST = [
            'category' => ComputerType::CATEGORY_SERVER,
        ];
        ComputerType::processMassiveActionsForOneItemtype($massive_action, $glpi_computer_type, [$glpi_computer_type->getID()]);
        $computer_type->getFromDBByCrit(['computertypes_id' => $glpi_computer_type->getID()]);
        $this->assertEquals(ComputerType::CATEGORY_SERVER, $computer_type->fields['category']);

        $massive_action->POST = [
            'category' => ComputerType::CATEGORY_TABLET,
        ];
        ComputerType::processMassiveActionsForOneItemtype($massive_action, $glpi_computer_type, [$glpi_computer_type->getID()]);
        $computer_type->getFromDB($computer_type->getID());
        $this->assertEquals(ComputerType::CATEGORY_TABLET, $computer_type->fields['category']);
    }

    public function testGetSpecificValueToDisplay()
    {
        $result = ComputerType::getSpecificValueToDisplay('category', ['category' => null]);
        $this->assertEquals('', $result);

        $result = ComputerType::getSpecificValueToDisplay('category', ['category' => ComputerType::CATEGORY_SMARTPHONE]);
        $this->assertEquals('Smartphone', $result);
    }

    public function testGetSpecificValueToSelect()
    {
        $result = ComputerType::getSpecificValueToSelect('', 'category', ComputerType::CATEGORY_LAPTOP);
        $this->stringStartsWith('<select ', $result);
        $this->stringContains('name=\'category\'', $result);
    }
}
