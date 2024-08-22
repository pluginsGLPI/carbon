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
use GlpiPlugin\Carbon\Tests\DbTestCase;
use GlpiPlugin\Carbon\Toolbox;

class ToolboxTest extends DbTestCase
{
    public function testGetOldestAssetDate()
    {
        $toolbox = new Toolbox();
        $output = $toolbox->getOldestAssetDate();
        $expected = null;
        $this->assertEquals($expected, $output);

        $expected = new DateTime('1980-01-01 00:00:00');
        $this->getItem(Computer::class, [
            'date_creation' => $expected->format('Y-m-d H:i:s'),
        ]);
        $output = $toolbox->getOldestAssetDate();
        $this->assertEquals($expected, $output);
    }

    public function testGetDefaultCarbonIntensityDownloadDate()
    {
        $toolbox = new Toolbox();
        $output = $toolbox->getDefaultCarbonIntensityDownloadDate();
        $expected = new DateTime('1 year ago');
        $expected->setDate($expected->format('Y'), 1, 1);
        $expected->setTime(0, 0, 0);
        $expected->modify('-1 month');
        $this->assertEquals($expected, $output);
    }
}
