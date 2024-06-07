<?php

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