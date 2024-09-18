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
use DateTime;
use DateTimeImmutable;
use GlpiPlugin\Carbon\CarbonIntensityZone;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use GlpiPlugin\Carbon\Toolbox;
use Infocom;
use Location;

class ToolboxTest extends DbTestCase
{
    public function testGetOldestAssetDate()
    {
        $toolbox = new Toolbox();
        $output = $toolbox->getOldestAssetDate();
        $expected = null;
        $this->assertEquals($expected, $output);

        $computer = $this->getItem(Computer::class);
        $output = $toolbox->getLatestAssetDate();
        $expected = null;
        $this->assertEquals($expected, $output);

        $expected = new DateTime('1980-01-01 00:00:00');
        $computer = $this->getItem(Computer::class, [
            'date_creation' => $expected->format('Y-m-d H:i:s'),
        ]);
        $output = $toolbox->getOldestAssetDate();
        $this->assertEquals($expected, $output);

        $expected = new DateTime('2000-01-01 00:00:00');
        $infocom = $this->getItem(Infocom::class, [
            'itemtype'    => $computer->getType(),
            'items_id'    => $computer->getID(),
            'entities_id' => $computer->fields['entities_id'],
            'use_date'    => $expected->format('Y-m-d H:i:s'),
        ]);
        $output = $toolbox->getOldestAssetDate();
        $this->assertEquals($expected, $output);

        $success = $infocom->update([
            'id' => $infocom->getID(),
            'buy_date' => '1999-01-01 00:00:00'
        ]);
        $this->assertTrue($success);
        $output = $toolbox->getOldestAssetDate();
        $this->assertEquals($expected, $output);
    }

    public function testGetLatestAssetDate()
    {
        $toolbox = new Toolbox();
        $output = $toolbox->getLatestAssetDate();
        $expected = null;
        $this->assertEquals($expected, $output);

        $computer = $this->getItem(Computer::class);
        $output = $toolbox->getLatestAssetDate();
        $expected = null;
        $this->assertEquals($expected, $output);


        $expected = new DateTime('2024-06-15 00:00:00');
        $infocom = $this->getItem(Infocom::class, [
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID(),
            'decommission_date' => $expected->format('Y-m-d H:i:s'),
        ]);
        $output = $toolbox->getLatestAssetDate();
        $this->assertEquals($expected, $output);
    }

    public function testGetDefaultCarbonIntensityDownloadDate()
    {
        $instance = new Toolbox();
        $output = $instance->getDefaultCarbonIntensityDownloadDate();
        $expected = new DateTime('1 year ago');
        $expected->setDate($expected->format('Y'), 1, 1);
        $expected->setTime(0, 0, 0);
        $expected->modify('-1 month');
        $this->assertEquals($expected, $output);
    }

    public function testYearToLastMonth()
    {
        $end = new DateTimeImmutable('2023-04-09 13:45:17');
        $instance = new Toolbox();
        $output = $instance->yearToLastMonth($end);

        $expected = [
            new DateTime('2022-04-01 00:00:00'),
            new DateTime('2023-03-31 00:00:00'),
        ];
        $this->assertEquals($expected, $output);
    }

    public function testIsLocationExistsForZone()
    {
        $output = Toolbox::isLocationExistForZone('foo');
        $this->assertFalse($output);

        $this->getItem(CarbonIntensityZone::class, [
            'name' => 'foo',
        ]);
        $this->getItem(Location::class, [
            'country' => 'foo'
        ]);
        $output = Toolbox::isLocationExistForZone('foo');
        $this->assertTrue($output);
    }
}
