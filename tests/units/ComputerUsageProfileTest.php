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

use CommonDBTM;
use Computer as GlpiComputer;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use MassiveAction;
use Symfony\Component\DomCrawler\Crawler;
use Ticket;

class ComputerUsageProfileTest extends DbTestCase
{
    /**
     * #CoversMethod GlpiPlugin\Carbon\ComputerUsageProfile::canView
     *
     * @return void
     */
    public function testCanView()
    {
        $this->logout();
        $result = ComputerUsageProfile::canView();
        $this->assertFalse($result);

        $this->login('glpi', 'glpi');
        $result = ComputerUsageProfile::canView();
        $this->assertTrue($result);
    }

    /**
     * #CoversMethod GlpiPlugin\Carbon\ComputerUsageProfile::prepareInputForAdd
     * #CoversMethod GlpiPlugin\Carbon\ComputerUsageProfile::inputIntegrityCheck
     *
     * @return void
     */
    public function testPrepareInputForAdd()
    {
        $instance = new ComputerUsageProfile();
        $input = [
            'time_start' => 'invalid',
        ];
        $result = $instance->prepareInputForAdd($input);
        $expected = [];
        $this->assertEquals($expected, $result);

        $input = [
            'time_stop' => 'invalid',
        ];
        $result = $instance->prepareInputForAdd($input);
        $expected = [];
        $this->assertEquals($expected, $result);

        $input = [
            'time_start' => '09:00:00',
        ];
        $result = $instance->prepareInputForAdd($input);
        $expected = [
            'time_start' => '09:00:00',
        ];
        $this->assertEquals($expected, $result);

        $input = [
            'time_stop' => '17:00:00',
        ];
        $result = $instance->prepareInputForAdd($input);
        $expected = [
            'time_stop' => '17:00:00',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * #CoversMethod GlpiPlugin\Carbon\ComputerUsageProfile::prepareInputForUpdate
     * #CoversMethod GlpiPlugin\Carbon\ComputerUsageProfile::inputIntegrityCheck
     *
     * @return void
     */
    public function testPrepareInputForUpdate()
    {
        $instance = new ComputerUsageProfile();
        $input = [
            'time_start' => 'invalid',
        ];
        $result = $instance->prepareInputForUpdate($input);
        $expected = [];
        $this->assertEquals($expected, $result);

        $input = [
            'time_stop' => 'invalid',
        ];
        $result = $instance->prepareInputForUpdate($input);
        $expected = [];
        $this->assertEquals($expected, $result);

        $input = [
            'time_start' => '09:00:00',
        ];
        $result = $instance->prepareInputForUpdate($input);
        $expected = [
            'time_start' => '09:00:00',
        ];
        $this->assertEquals($expected, $result);

        $input = [
            'time_stop' => '17:00:00',
        ];
        $result = $instance->prepareInputForUpdate($input);
        $expected = [
            'time_stop' => '17:00:00',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * #CoversMethod GlpiPlugin\Carbon\ComputerUsageProfile::assignToItem
     *
     * @return void
     */
    public function testAssignToItem()
    {
        $invalid_item = new class extends CommonDBTM {
        };
        /** @var ComputerUsageProfile $usage_profile */
        $usage_profile = $this->createItem(ComputerUsageProfile::class, ['name' => 'Test Usage Profile']);
        $result = $usage_profile->assignToItem($invalid_item);
        $this->assertFalse($result);

        $computer = $this->createItem(GlpiComputer::class, ['name' => 'Test Computer']);
        /** @var ComputerUsageProfile $usage_profile */
        $usage_profile = $this->createItem(ComputerUsageProfile::class, ['name' => 'Test Usage Profile']);

        $result = $usage_profile->assignToItem($computer);
        $this->assertTrue($result);

        /** @var ComputerUsageProfile $usage_profile */
        $usage_profile = $this->createItem(ComputerUsageProfile::class, ['name' => 'Test Usage Profile 2']);
        $result = $usage_profile->assignToItem($computer);
        $this->assertTrue($result);
    }

    public function testShowMassiveActionsSubForm()
    {
        // Test power consumption update form
        $massive_action = $this->getMockBuilder(MassiveAction::class)
            ->disableOriginalConstructor()
            ->getMock();
        $massive_action->method('getAction')->willReturn('MassAssociateItems');
        $massive_action->method('getItems')->willReturn([
            GlpiComputer::class => $this->createItem(GlpiComputer::class)
        ]);
        ob_start(function ($buffer) {
            return $buffer;
        });
        $result = ComputerUsageProfile::showMassiveActionsSubForm($massive_action);
        $output = ob_get_clean();
        $crawler = new Crawler($output);
        $selector = $crawler->filter('select[name="plugin_carbon_computerusageprofiles_id"]');
        $this->assertEquals(1, $selector->count());
        $button = $crawler->filter('button[name="massiveaction"]');
        $this->assertEquals(1, $button->count());
    }

    public function testProcessMassiveActionsForOneItemtype()
    {
        // Test with invalid usage profile
        $computer = $this->createItem(GlpiComputer::class);
        $massive_action = $this->getMockBuilder(MassiveAction::class)
            ->disableOriginalConstructor()
            ->getMock();
        $massive_action->method('getAction')->willReturn('MassAssociateItems');
        $massive_action->expects($this->once())->method('itemDone')->with(
            GlpiComputer::class,
            $computer->getID(),
            MassiveAction::ACTION_KO
        );
        $usage_profile_fk = ComputerUsageProfile::getForeignKeyField();
        $usage_profile = $this->createItem(ComputerUsageProfile::class);
        $massive_action->POST[$usage_profile_fk] = -1;
        ComputerUsageProfile::processMassiveActionsForOneItemtype(
            $massive_action,
            new GlpiComputer(),
            [
                $computer->getID() => $computer->getID(),
            ]
        );

        // Test with invalid and valid computer
        $computer_1 = new GlpiComputer();
        $computer_2 = $this->createItem(GlpiComputer::class);
        $massive_action = $this->getMockBuilder(MassiveAction::class)
            ->disableOriginalConstructor()
            ->getMock();
        $massive_action->method('getAction')->willReturn('MassAssociateItems');
        $matcher = $this->exactly(2);
        $expected_args = [
            1 => [GlpiComputer::class, $computer_1->getID(), MassiveAction::ACTION_KO],
            2 => [GlpiComputer::class, $computer_2->getID(), MassiveAction::ACTION_OK],
        ];
        $massive_action->expects($matcher)->method('itemDone')->willReturnCallback(
            function (...$parameters) use ($matcher, $expected_args) {
                // TODO: With PHPUnit 10 getInvocationCount becomes numberOfInvocations
                switch ($matcher->numberOfInvocations()) {
                    case 1:
                        $this->assertEquals($expected_args[1], $parameters);
                        break;
                    case 2:
                        $this->assertEquals($expected_args[2], $parameters);
                        break;
                }
            }
        );
        $usage_profile_fk = ComputerUsageProfile::getForeignKeyField();
        $usage_profile = $this->createItem(ComputerUsageProfile::class);
        $massive_action->POST[$usage_profile_fk] = $usage_profile->getID();
        ComputerUsageProfile::processMassiveActionsForOneItemtype(
            $massive_action,
            new GlpiComputer(),
            [
                $computer_1->getID() => $computer_1->getID(),
                $computer_2->getID() => $computer_2->getID(),
            ]
        );
    }

    public function testShowForm()
    {
        $this->login('glpi', 'glpi');
        $instance = $this->createItem(ComputerUsageProfile::class);
        ob_start(function ($in) {
            return $in;
        });
        $instance->showForm($instance->getID());
        $output = ob_get_clean();

        $crawler = new Crawler($output);
        $name_field = $crawler->filter('input[name="name"]');
        $this->assertEquals(1, $name_field->count());
        $start_time_field = $crawler->filter('select[name="time_start"]');
        $this->assertEquals(1, $start_time_field->count());
        $end_time_field = $crawler->filter('select[name="time_stop"]');
        $this->assertEquals(1, $end_time_field->count());
        for ($i = 1; $i <= 7; $i++) {
            $field = $crawler->filter('input[name="day_' . $i . '"]');
            // 2 inputs : checked and unchecked, one of them is hidden
            $this->assertEquals(2, $field->count());
        }
    }
}
