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
use Symfony\Component\DomCrawler\Crawler;

class ComputerTypeTest extends DbTestCase
{
    public function testGetTabNameForItem()
    {
        $glpi_computer_type = $this->getItem(GlpiComputerType::class);
        $instance = new ComputerType();
        $result = $instance->getTabNameForItem($glpi_computer_type);
        $this->assertEquals('Carbon', $result);

        $result = $instance->getTabNameForItem($glpi_computer_type, 1);
        $this->assertEquals('', $result);
    }

    /**
     * @covers GlpiPlugin\Carbon\AbstractType::getOrCreate
     *
     * @return void
     */
    public function testGetOrCreate()
    {
        $computer_type = $this->getItem(GlpiComputerType::class, ['name' => 'Test Computer Type']);
        $instance = new ComputerType();
        $this->callPrivateMethod($instance, 'getOrCreate', $computer_type);
        $this->assertFalse($instance->isNewItem());
    }

    /**
     * @covers GlpiPlugin\Carbon\AbstractType::showForItemType
     *
     * @return void
     */
    public function testShowForItemType()
    {
        $glpi_computer_type = $this->getItem(GlpiComputerType::class);
        $computer_type = $this->getItem(ComputerType::class, [
            'computertypes_id' => $glpi_computer_type->getID(),
        ]);
        $this->login('glpi', 'glpi');
        ob_start(function ($buffer) {
            return $buffer;
        });
        $computer_type->showForItemType($glpi_computer_type);
        $output = ob_get_clean();
        $crawler = new Crawler($output);
        $power = $crawler->filter('input[name="power_consumption"]');
        $this->assertEquals(1, $power->count());
        $power->each(function (Crawler $node) {
            $this->assertEquals(0, $node->attr('value'));
            $this->assertEquals('number', $node->attr('type'));
        });
        $category = $crawler->filter('select[name="category"]');
        $this->assertEquals(1, $category->count());
    }

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
        $crawler = new Crawler($output);
        $input_field = $crawler->filter('input[name="power_consumption"]');
        $this->assertEquals(1, $input_field->count());
        $input_field->each(function (Crawler $node) {
            $this->assertEquals('number', $node->attr('type'));
            $this->assertEquals('power_consumption', $node->attr('name'));
        });
        $button = $crawler->filter('button');
        $this->assertEquals(1, $button->count());
        $button->each(function (Crawler $node) {
            $this->assertEquals('submit', $node->attr('type'));
            $this->assertEquals('Post', $node->attr('value'));
            $this->assertEquals('massiveaction', $node->attr('name'));
        });
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
        $crawler = new Crawler($output);
        $select = $crawler->filter('select[name="category"]');
        $this->assertEquals(1, $select->count());
        $select->each(function (Crawler $node) {
            $this->assertEquals('category', $node->attr('name'));
        });
        $button = $crawler->filter('button');
        $this->assertEquals(1, $button->count());
        $button->each(function (Crawler $node) {
            $this->assertEquals('submit', $node->attr('type'));
            $this->assertEquals('Post', $node->attr('value'));
            $this->assertEquals('massiveaction', $node->attr('name'));
        });
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
