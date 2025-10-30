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

namespace GlpiPlugin\Carbon\Engine\V1\Tests;

use GlpiPlugin\Carbon\Engine\V1\Inventory;
use Computer;
use Plugin;
use GlpiPlugin\Carbon\Tests\DbTestCase;

class InventoryTest extends DbTestCase
{
    public function testAddItem()
    {
        $inventory = new Inventory();

        // Unsupported item
        $success = $inventory->addItem(Plugin::class, 1);
        $this->assertFalse($success);

        $computer = $this->createItem(Computer::class);
        $success = $inventory->addItem($computer->getType(), $computer->getId());
        $this->assertTrue($success);
    }

    public function testAddItemByCrit()
    {
        $this->login('glpi', 'glpi');
        $entities_id = $this->isolateInEntity('glpi', 'glpi');

        $computers = $this->getItems([
            Computer::class => [
                [],
                [],
            ],
        ]);

        $inventory = new Inventory();
        $success = $inventory->addItemsByCrit(Computer::class, [
            'entities_id' => $entities_id,
        ]);

        $this->assertTrue($success);
        foreach ($computers[Computer::class] as $key => $item) {
            $this->assertTrue($inventory->hasItem(Computer::class, $key));
        }
    }
}
