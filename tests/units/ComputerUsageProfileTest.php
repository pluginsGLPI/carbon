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

use Computer as GlpiComputer;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\Tests\DbTestCase;

class ComputerUsageProfileTest extends DbTestCase
{
    /**
     * @covers GlpiPlugin\Carbon\ComputerUsageProfile::prepareInputForAdd
     * @covers GlpiPlugin\Carbon\ComputerUsageProfile::inputIntegrityCheck
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
     * @covers GlpiPlugin\Carbon\ComputerUsageProfile::prepareInputForUpdate
     * @covers GlpiPlugin\Carbon\ComputerUsageProfile::inputIntegrityCheck
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

    public function testAssignToItem()
    {
        $computer = $this->getItem(GlpiComputer::class, ['name' => 'Test Computer']);
        $usage_profile = $this->getItem(ComputerUsageProfile::class, ['name' => 'Test Usage Profile']);

        $result = ComputerUsageProfile::assignToItem($computer, $usage_profile->getID());
        $this->assertTrue($result);

        $usage_profile = $this->getItem(ComputerUsageProfile::class, ['name' => 'Test Usage Profile 2']);
        $result = ComputerUsageProfile::assignToItem($computer, $usage_profile->getID());
        $this->assertTrue($result);
    }
}
