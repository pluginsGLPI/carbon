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

use GlpiPlugin\Carbon\MonitorType;
use MassiveAction;
use MonitorType as GlpiMonitorType;
use Symfony\Component\DomCrawler\Crawler;

class MonitorTypeTest extends DbTestCase
{
    public function testGetTabNameForItem()
    {
        $glpi_monitor_type = $this->getItem(GlpiMonitorType::class);
        $instance = new MonitorType();
        $result = $instance->getTabNameForItem($glpi_monitor_type);
        $this->assertEquals('Carbon', $result);

        $result = $instance->getTabNameForItem($glpi_monitor_type, 1);
        $this->assertEquals('', $result);
    }

    /**
     * @covers GlpiPlugin\Carbon\AbstractType::getOrCreate
     *
     * @return void
     */
    public function testGetOrCreate()
    {
        $computer_type = $this->getItem(GlpiMonitorType::class, ['name' => 'Test Computer Type']);
        $instance = new MonitorType();
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
        $glpi_monitor_type = $this->getItem(GlpiMonitorType::class);
        $monitor_type = $this->getItem(MonitorType::class, [
            'monitortypes_id' => $glpi_monitor_type->getID(),
        ]);
        $this->login('glpi', 'glpi');
        ob_start(function ($buffer) {
            return $buffer;
        });
        $monitor_type->showForItemType($glpi_monitor_type);
        $output = ob_get_clean();
        $crawler = new Crawler($output);
        $power = $crawler->filter('input[name="power_consumption"]');
        $this->assertEquals(1, $power->count());
        $power->each(function (Crawler $node) {
            $this->assertEquals(0, $node->attr('value'));
            $this->assertEquals('number', $node->attr('type'));
        });
    }

    /**
     * @covers GlpiPlugin\Carbon\MonitorType::updatePowerConsumption
     *
     * @return void
     */
    public function testUpdatePowerConsumption()
    {
        $glpi_monitor_type = $this->getItem(GlpiMonitorType::class);

        MonitorType::updatePowerConsumption($glpi_monitor_type, 10);
        $instance = new MonitorType();
        $instance->getFromDBByCrit([
            'monitortypes_id' => $glpi_monitor_type->getID(),
        ]);
        $this->assertFalse($instance->isNewItem());
        $this->assertEquals(10, $instance->fields['power_consumption']);

        MonitorType::updatePowerConsumption($glpi_monitor_type, 42);
        // reload the object
        $instance->getFromDBByCrit([
            'monitortypes_id' => $glpi_monitor_type->getID(),
        ]);
        $this->assertEquals(42, $instance->fields['power_consumption']);
    }

    /**
     * @covers GlpiPlugin\Carbon\MonitorType::showMassiveActionsSubForm
     *
     * @return void
     */
    public function testShowMassiveActionsSubForm()
    {
        $massive_action = $this->getMockBuilder(MassiveAction::class)
            ->disableOriginalConstructor()
            ->getMock();
        $massive_action->method('getAction')->willReturn('MassUpdatePower');
        $massive_action->method('getItems')->willReturn([
            MonitorType::class => $this->getItem(GlpiMonitorType::class)
        ]);
        ob_start(function ($buffer) {
            return $buffer;
        });
        $result = MonitorType::showMassiveActionsSubForm($massive_action);
        $output = ob_get_clean();
        $crawler = new Crawler($output);
        $input = $crawler->filter('input');
        $this->assertEquals(1, $input->count());
        $input->each(function (Crawler $node) {
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

        $massive_action = $this->getMockBuilder(MassiveAction::class)
            ->disableOriginalConstructor()
            ->getMock();
        $massive_action->method('getAction')->willReturn('');
        $massive_action->method('getItems')->willReturn([
            MonitorType::class => $this->getItem(GlpiMonitorType::class)
        ]);
        ob_start(function ($buffer) {
            return $buffer;
        });
        $result = MonitorType::showMassiveActionsSubForm($massive_action);
        $output = ob_get_clean();
        $this->assertEquals('', $output);
        $this->assertFalse($result);
    }

    /**
     * @covers GlpiPlugin\Carbon\MonitorType::processMassiveActionsForOneItemtype
     *
     * @return void
     */
    public function testProcessMassiveActionForOneItemtype()
    {
        // Test update power consumption
        $massive_action = $this->getMockBuilder(MassiveAction::class)
            ->disableOriginalConstructor()
            ->getMock();
        $massive_action->method('getAction')->willReturn('MassUpdatePower');
        $glpi_monitor_type = $this->getItem(GlpiMonitorType::class);
        $massive_action->POST = [
            'power_consumption' => 55,
        ];
        MonitorType::processMassiveActionsForOneItemtype($massive_action, $glpi_monitor_type, [$glpi_monitor_type->getID()]);
        $monitor_type = new MonitorType();
        $monitor_type->getFromDBByCrit(['monitortypes_id' => $glpi_monitor_type->getID()]);
        $this->assertEquals(55, $monitor_type->fields['power_consumption']);

        $massive_action->POST = [
            'power_consumption' => 25,
        ];
        MonitorType::processMassiveActionsForOneItemtype($massive_action, $glpi_monitor_type, [$glpi_monitor_type->getID()]);
        $monitor_type->getFromDB($monitor_type->getID());
        $this->assertEquals(25, $monitor_type->fields['power_consumption']);
    }
}
