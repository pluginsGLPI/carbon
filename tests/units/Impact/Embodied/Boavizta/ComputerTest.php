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

namespace GlpiPlugin\Carbon\Impact\Engine\Boavizta\Tests;

use Computer as GlpiComputer;
use ComputerType as GlpiComputerType;
use ComputerModel as GlpiComputerModel;
use DBmysql;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\Impact\Embodied\Boavizta\Computer as BoaviztaComputer;
use GlpiPlugin\Carbon\Tests\Impact\Engine\AbstractEmbodiedImpactTest;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BoaviztaComputer::class)]
class ComputerTest extends AbstractEmbodiedImpactTest
{
    protected static string $itemtype = GlpiComputer::class;
    protected static string $itemtype_type = GlpiComputerType::class;
    protected static string $itemtype_model = GlpiComputerModel::class;

    public function testGetEvaluableQuery()
    {
        /** @var DBmysql $DB */
        global $DB;

        // Test a computer is evaluable
        $glpi_computer_type = $this->createItem(GlpiComputerType::class);
        $computer_type = $this->createItem(ComputerType::class, [
            'computertypes_id' => $glpi_computer_type->getID()
        ]);
        $computer = $this->createItem(GlpiComputer::class, [
            'computertypes_id' => $glpi_computer_type->getID()
        ]);
        $instance = new BoaviztaComputer($computer);
        $request = $instance->getEvaluableQuery([
            'glpi_computers.id' => $computer->getID(),
        ]);
        $this->assertArrayHasKey('SELECT', $request);
        $this->assertArrayHasKey('FROM', $request);
        $this->assertArrayHasKey('LEFT JOIN', $request);
        $this->assertArrayHasKey('WHERE', $request);
        $iterator = $DB->request($request);
        $this->assertEquals(1, $iterator->count());

        // Test a computer is not evaluable
        $glpi_computer_type = $this->createItem(GlpiComputerType::class);
        $computer_type = $this->createItem(ComputerType::class, [
            'computertypes_id' => $glpi_computer_type->getID(),
            'is_ignore' => 1,
        ]);
        $computer = $this->createItem(GlpiComputer::class, [
            'computertypes_id' => $glpi_computer_type->getID()
        ]);
        $instance = new BoaviztaComputer($computer);
        $request = $instance->getEvaluableQuery([
            'glpi_computers.id' => $computer->getID(),
        ]);
        $this->assertArrayHasKey('SELECT', $request);
        $this->assertArrayHasKey('FROM', $request);
        $this->assertArrayHasKey('LEFT JOIN', $request);
        $this->assertArrayHasKey('WHERE', $request);
        $iterator = $DB->request($request);
        $this->assertEquals(0, $iterator->count());
    }
}
