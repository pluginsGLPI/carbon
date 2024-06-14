<?php

/**
 * -------------------------------------------------------------------------
 * carbon plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2024 Teclib' and contributors.
 * @license   MIT https://opensource.org/licenses/mit-license.php
 * @link      https://github.com/pluginsGLPI/carbon
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

        $computer = $this->getItem(Computer::class);
        $success = $inventory->addItem($computer->getType(), $computer->getId());
        $this->assertTrue($success);
    }

    public function testAddItemByCrit()
    {
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
