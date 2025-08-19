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
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\CarbonIntensitySource_Zone;
use GlpiPlugin\Carbon\Zone;
use Log;
use Session;

class CarbonIntensitySourceTest extends DbTestCase
{
    /**
     * @covers \GlpiPlugin\Carbon\CarbonIntensitySource::getTypeName
     *
     * @return void
     */
    public function testGetTypeName()
    {
        $result = CarbonIntensitySource::getTypeName(1);
        $this->assertEquals('Carbon intensity source', $result);

        $result = CarbonIntensitySource::getTypeName(Session::getPluralNumber());
        $this->assertEquals('Carbon intensity sources', $result);
    }

    /**
     * @covers \GlpiPlugin\Carbon\CarbonIntensitySource::canCreate
     *
     * @return void
     */
    public function testCanCreate()
    {
        $this->login('glpi', 'glpi');
        $result = CarbonIntensitySource::canCreate();
        $this->assertFalse($result);
    }

    /**
     * @covers \GlpiPlugin\Carbon\CarbonIntensitySource::canUpdate
     *
     * @return void
     */
    public function testCanUpdate()
    {
        $this->login('glpi', 'glpi');
        $result = CarbonIntensitySource::canUpdate();
        $this->assertFalse($result);
    }

    /**
     * @covers \GlpiPlugin\Carbon\CarbonIntensitySource::canDelete
     *
     * @return void
     */
    public function testCanDelete()
    {
        $this->login('glpi', 'glpi');
        $result = CarbonIntensitySource::canDelete();
        $this->assertFalse($result);
    }

    /**
     * @covers \GlpiPlugin\Carbon\CarbonIntensitySource::canPurge
     *
     * @return void
     */
    public function testCanPurge()
    {
        $this->login('glpi', 'glpi');
        $result = CarbonIntensitySource::canPurge();
        $this->assertFalse($result);
    }

    /**
     * @covers \GlpiPlugin\Carbon\CarbonIntensitySource::defineTabs
     *
     * @return void
     */
    public function testDefineTabs()
    {
        $this->login('glpi', 'glpi');
        $instance = new CarbonIntensitySource();
        $result = $instance->defineTabs();
        $expected = [
            CarbonIntensitySource::class . '$main' => 'Carbon intensity source',
            Zone::class . '$1' => 'Carbon intensity zones',
            Log::class . '$1' => 'Historical',
        ];
        $this->assertEquals($expected, $result);
    }

    public function testGetTabNameForItem()
    {
        $this->login('glpi', 'glpi');
        $item = $this->getItem(Zone::class);
        $instance = new CarbonIntensitySource();
        $result = $instance->getTabNameForItem($item);
        $expected = 'Carbon intensity sources';
        $this->assertEquals($expected, $result);

        $result = $instance->getTabNameForItem($item, 1);
        $expected = '';
        $this->assertEquals($expected, $result);

        $item = $this->getItem(Computer::class);
        $result = $instance->getTabNameForItem($item);
        $expected = '';
        $this->assertEquals($expected, $result);
    }

    /**
     * @covers \GlpiPlugin\Carbon\CarbonIntensitySource::displayTabContentForItem
     *
     * @return void
     */
    public function testDisplayTabContentForItem()
    {
        $this->login('glpi', 'glpi');
        $item = $this->getItem(Computer::class);
        ob_start(function ($buffer) {
            return $buffer;
        });
        $result = CarbonIntensitySource::displayTabContentForItem($item);
        $output = ob_get_clean();
        $this->assertEquals('', $output);
        $this->assertTrue($result);

        $item = $this->getItem(Zone::class);
        $source = $this->getItem(CarbonIntensitySource::class);
        $source_zone = $this->getItem(CarbonIntensitySource_Zone::class, [
            $item::getForeignKeyField() => $item->getID(),
            $source::getForeignKeyField() => $source->getID()
        ]);
        ob_start(function ($buffer) {
            return $buffer;
        });
        $result = CarbonIntensitySource::displayTabContentForItem($item);
        $output = ob_get_clean();
        $this->assertNotEquals('', $output);
        $this->assertTrue($result);
    }
}
