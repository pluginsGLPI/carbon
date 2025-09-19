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
use GlpiPlugin\Carbon\Source;
use GlpiPlugin\Carbon\Source_Zone;
use GlpiPlugin\Carbon\Zone;
use Log;
use Session;

class SourceTest extends DbTestCase
{
    /**
     * #CoversMethod \GlpiPlugin\Carbon\Source::getTypeName
     */
    public function testGetTypeName()
    {
        $result = Source::getTypeName(1);
        $this->assertEquals('Carbon intensity source', $result);

        $result = Source::getTypeName(Session::getPluralNumber());
        $this->assertEquals('Carbon intensity sources', $result);
    }

    /**
     * #CoversMethod \GlpiPlugin\Carbon\Source::canCreate
     */
    public function testCanCreate()
    {
        $this->login('glpi', 'glpi');
        $result = Source::canCreate();
        $this->assertFalse($result);
    }

    /**
     * #CoversMethod \GlpiPlugin\Carbon\Source::canUpdate
     */
    public function testCanUpdate()
    {
        $this->login('glpi', 'glpi');
        $result = Source::canUpdate();
        $this->assertFalse($result);
    }

    /**
     * #CoversMethod \GlpiPlugin\Carbon\Source::canDelete
     */
    public function testCanDelete()
    {
        $this->login('glpi', 'glpi');
        $result = Source::canDelete();
        $this->assertFalse($result);
    }

    /**
     * #CoversMethod \GlpiPlugin\Carbon\Source::canPurge
     */
    public function testCanPurge()
    {
        $this->login('glpi', 'glpi');
        $result = Source::canPurge();
        $this->assertFalse($result);
    }

    /**
     * #CoversMethod \GlpiPlugin\Carbon\Source::defineTabs
     */
    public function testDefineTabs()
    {
        $this->login('glpi', 'glpi');
        $instance = new Source();
        $result = $instance->defineTabs();
        $this->assertStringContainsString('Carbon intensity source', $result[Source::class . '$main']);
        $this->assertStringContainsString('Carbon intensity zones', $result[Zone::class . '$1']);
        $this->assertStringContainsString('Historical', $result[Log::class . '$1']);
    }

    public function testGetTabNameForItem()
    {
        $this->login('glpi', 'glpi');
        $item = $this->createItem(Zone::class);
        $instance = new Source();
        $result = $instance->getTabNameForItem($item);
        $expected = 'Carbon intensity sources';
        $this->assertStringContainsString($expected, $result);

        $result = $instance->getTabNameForItem($item, 1);
        $expected = '';
        $this->assertEquals($expected, $result);

        $item = $this->createItem(Computer::class);
        $result = $instance->getTabNameForItem($item);
        $expected = '';
        $this->assertEquals($expected, $result);
    }

    /**
     * #CoversMethod \GlpiPlugin\Carbon\Source::displayTabContentForItem
     */
    public function testDisplayTabContentForItem()
    {
        $this->login('glpi', 'glpi');
        $item = $this->createItem(Computer::class);
        ob_start(function ($buffer) {
            return $buffer;
        });
        $result = Source::displayTabContentForItem($item);
        $output = ob_get_clean();
        $this->assertEquals('', $output);
        $this->assertTrue($result);

        $item = $this->createItem(Zone::class);
        $source = $this->createItem(Source::class);
        $source_zone = $this->createItem(Source_Zone::class, [
            $item::getForeignKeyField() => $item->getID(),
            $source::getForeignKeyField() => $source->getID()
        ]);
        ob_start(function ($buffer) {
            return $buffer;
        });
        $result = Source::displayTabContentForItem($item);
        $output = ob_get_clean();
        $this->assertNotEquals('', $output);
        $this->assertTrue($result);
    }
}
