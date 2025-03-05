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

namespace GlpiPlugin\Carbon\Tests;

use Computer;
use GlpiPlugin\Carbon\Zone;
use Location;

class ZoneTest extends DbTestCase
{
    public function testGetByLocation()
    {
        $output = Zone::getByLocation(new Location());
        $this->assertNull($output);

        $this->getItem(Location::class);
        $this->assertNull($output);

        $location = $this->getItem(Location::class, [
            'country' => 'foo'
        ]);
        $output = Zone::getByLocation($location);
        $this->assertNull($output);

        $zone = $this->getItem(Zone::class, [
            'name' => 'foo'
        ]);
        $output = Zone::getByLocation($location);
        $this->assertEquals($output->getID(), $zone->getID());
    }

    public function testGetByAsset()
    {
        $output = Zone::getByAsset(new Computer());
        $this->assertNull($output);

        $location = $this->getItem(Location::class, [
            'country' => 'foo'
        ]);
        $computer = $this->getItem(Computer::class, [
            'locations_id' => $location->getID(),
        ]);
        $output = Zone::getByAsset($computer);
        $this->assertNull($output);

        $zone = $this->getItem(Zone::class, [
            'name' => 'foo'
        ]);
        $output = Zone::getByAsset($computer);
        $this->assertEquals($output->getID(), $zone->getID());
    }
}
