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
use DbUtils;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\EmbodiedImpact;
use GlpiPlugin\Carbon\UsageInfo;

class HookTest extends DbTestCase
{
    public function testCarbonEmissionsArePuegedOnAssetPurge()
    {
        $computer = $this->createItem(Computer::class);
        $carbon_emission = $this->createItem(CarbonEmission::class, [
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID()
        ]);
        $embodied_impact = $this->createItem(EmbodiedImpact::class, [
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID()
        ]);
        $usage_info = $this->createItem(UsageInfo::class, [
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID()
        ]);

        // Data must remain in DB after a delete
        $computer->delete($computer->fields);
        $count = (new DbUtils())->countElementsInTable($carbon_emission::getTable(), [
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID()
        ]);
        $this->assertEquals(1, $count);
        $count = (new DbUtils())->countElementsInTable($embodied_impact::getTable(), [
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID()
        ]);
        $this->assertEquals(1, $count);

        // Data must be dropped fron DB after a purge
        $computer->delete($computer->fields, true);
        $count = (new DbUtils())->countElementsInTable($carbon_emission::getTable(), [
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID()
        ]);
        $this->assertEquals(0, $count);
        $count = (new DbUtils())->countElementsInTable($embodied_impact::getTable(), [
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID()
        ]);
        $this->assertEquals(0, $count);
    }

    public function testCarbonAssetTypeIsPurgedOnAssetTypePurge()
    {
        $computer_type = $this->createItem(GlpiComputerType::class);
        $carbon_computer_type = $this->createItem(ComputerType::class, [
            'computertypes_id' => $computer_type->getID(),
        ]);

        $computer_type->delete($computer_type->fields, 1);
        $count = (new DbUtils())->countElementsInTable($carbon_computer_type::getTable(), [
            'computertypes_id' => $computer_type->getID()
        ]);
        $this->assertEquals(0, $count);
    }
}
