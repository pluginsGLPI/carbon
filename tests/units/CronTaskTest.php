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

use GlpiPlugin\Carbon\CronTask;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use GlpiPlugin\Carbon\CarbonIntensity;
use CronTask as GlpiCronTask;
use GlpiPlugin\Carbon\DataSource\CarbonIntensityInterface;

class CronTaskTest extends DbTestCase
{
    public function downloadSourceProvider()
    {
        $data_source1 = $this->createStub(CarbonIntensityInterface::class);
        $data_source1->method('getZones')->willReturn(['test_zone']);
        $intensity1 = $this->createStub(CarbonIntensity::class);
        $intensity1->method('downloadOneZone')->willReturn(0);
        yield 'download empty data' => [
            'data_source' => $data_source1,
            'intensity' => $intensity1,
            'expected' => 0,
        ];

        $data_source2 = $this->createStub(CarbonIntensityInterface::class);
        $data_source2->method('getZones')->willReturn(['test_zone']);
        $intensity2 = $this->createStub(CarbonIntensity::class);
        $intensity2->method('downloadOneZone')->willReturn(1024);
        yield 'download complete' => [
            'data_source' => $data_source2,
            'intensity' => $intensity2,
            'expected' => 1,
        ];

        $data_source3 = $this->createStub(CarbonIntensityInterface::class);
        $data_source3->method('getZones')->willReturn(['test_zone']);
        $intensity3 = $this->createStub(CarbonIntensity::class);
        $intensity3->method('downloadOneZone')->willReturn(-5);
        yield 'download incomplete' => [
            'data_source' => $data_source3,
            'intensity' => $intensity3,
            'expected' => -1,
        ];
    }

    /**
     * @dataProvider downloadSourceProvider
     *
     * @return void
     */
    public function testDownloadCarbonIntensityFromSource($data_source, $intensity, $expected)
    {
        $cron_task = new CronTask();
        $glpi_cron_task = new GlpiCronTask();
        $glpi_cron_task->fields['param'] = 1000;
        $output = $this->callPrivateMethod($cron_task, 'downloadCarbonIntensityFromSource', $glpi_cron_task, $data_source, $intensity);

        $this->assertEquals($expected, $output);
    }
}
