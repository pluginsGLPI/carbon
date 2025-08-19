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

use GlpiPlugin\Carbon\NetworkEquipmentType;
use MassiveAction;
use NetworkEquipmentType as GlpiNetworkEquipmentType;
use Symfony\Component\DomCrawler\Crawler;

class NetworkEquipmentTypeTest extends DbTestCase
{
    /**
     * @covers GlpiPlugin\Carbon\NetworkEquipmentType::updatePowerConsumption
     *
     * @return void
     */
    public function testUpdatePowerConsumption()
    {
        $glpi_networkequipment_type = $this->getItem(GlpiNetworkEquipmentType::class);

        NetworkEquipmentType::updatePowerConsumption($glpi_networkequipment_type, 10);
        $instance = new NetworkEquipmentType();
        $instance->getFromDBByCrit([
            'networkequipmenttypes_id' => $glpi_networkequipment_type->getID(),
        ]);
        $this->assertFalse($instance->isNewItem());
        $this->assertEquals(10, $instance->fields['power_consumption']);

        NetworkEquipmentType::updatePowerConsumption($glpi_networkequipment_type, 42);
        // reload the object
        $instance->getFromDBByCrit([
            'networkequipmenttypes_id' => $glpi_networkequipment_type->getID(),
        ]);
        $this->assertEquals(42, $instance->fields['power_consumption']);
    }

    /**
     * @covers GlpiPlugin\Carbon\NetworkEquipmentType::showMassiveActionsSubForm
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
            NetworkEquipmentType::class => $this->getItem(GlpiNetworkEquipmentType::class)
        ]);
        ob_start(function ($buffer) {
            return $buffer;
        });
        $result = NetworkEquipmentType::showMassiveActionsSubForm($massive_action);
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
            NetworkEquipmentType::class => $this->getItem(GlpiNetworkEquipmentType::class)
        ]);
        ob_start(function ($buffer) {
            return $buffer;
        });
        $result = NetworkEquipmentType::showMassiveActionsSubForm($massive_action);
        $output = ob_get_clean();
        $this->assertEquals('', $output);
        $this->assertFalse($result);
    }

    /**
     * @covers GlpiPlugin\Carbon\NetworkEquipmentType::processMassiveActionsForOneItemtype
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
        $glpi_networkequipment_type = $this->getItem(GlpiNetworkEquipmentType::class);
        $networkequipment_type = $this->getItem(NetworkEquipmentType::class, [
            'networkequipmenttypes_id' => $glpi_networkequipment_type->getID(),
        ]);
        $massive_action->POST = [
            'power_consumption' => 55,
        ];
        NetworkEquipmentType::processMassiveActionsForOneItemtype($massive_action, $glpi_networkequipment_type, [$glpi_networkequipment_type->getID()]);
        $networkequipment_type->getFromDBByCrit(['networkequipmenttypes_id' => $glpi_networkequipment_type->getID()]);
        $this->assertEquals(55, $networkequipment_type->fields['power_consumption']);

        $massive_action->POST = [
            'power_consumption' => 25,
        ];
        NetworkEquipmentType::processMassiveActionsForOneItemtype($massive_action, $glpi_networkequipment_type, [$glpi_networkequipment_type->getID()]);
        $networkequipment_type->getFromDB($networkequipment_type->getID());
        $this->assertEquals(25, $networkequipment_type->fields['power_consumption']);
    }
}
