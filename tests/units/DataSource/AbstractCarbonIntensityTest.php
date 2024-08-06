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

namespace GlpiPlugin\Carbon\DataSource\Tests;

use Config as GlpiConfig;
use CronTask;
use DateTime;
use DateTimeImmutable;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\CarbonIntensityZone;
use GlpiPlugin\Carbon\DataSource\AbstractCarbonIntensity;
use GlpiPlugin\Carbon\Tests\DbTestCase;

class AbstractCarbonIntensityTest extends DbTestCase
{
    public function sliceDateRangeByMonthProvider()
    {
        yield [
            'start' => new DateTimeImmutable('2020-12-01'),
            'stop'  => new DateTimeImmutable('2020-01-31'),
            'expected' => [
            ]
        ];

        yield [
            'start' => new DateTimeImmutable('2020-01-01'),
            'stop'  => new DateTimeImmutable('2020-02-01'),
            'expected' => [
                [
                    'start' => new DateTimeImmutable('2020-01-01'),
                    'stop'  => new DateTimeImmutable('2020-02-01'),
                ],
            ]
        ];

        yield [
            'start' => new DateTimeImmutable('2020-01-14'),
            'stop'  => new DateTimeImmutable('2020-01-31'),
            'expected' => [
                [
                    'start' => new DateTimeImmutable('2020-01-14'),
                    'stop'  => new DateTimeImmutable('2020-01-31'),
                ],
            ]
        ];

        yield [
            'start' => new DateTimeImmutable('2020-01-01'),
            'stop'  => new DateTimeImmutable('2020-01-14'),
            'expected' => [
                [
                    'start' => new DateTimeImmutable('2020-01-01'),
                    'stop'  => new DateTimeImmutable('2020-01-14'),
                ],
            ]
        ];

        // Bissextile year
        yield [
            'start' => new DateTimeImmutable('2020-01-14'),
            'stop'  => new DateTimeImmutable('2020-03-31'),
            'expected' => [
                [
                    'start' => new DateTimeImmutable('2020-03-01'),
                    'stop'  => new DateTimeImmutable('2020-03-31'),
                ],
                [
                    'start' => new DateTimeImmutable('2020-02-01'),
                    'stop'  => new DateTimeImmutable('2020-03-01'),
                ],
                [
                    'start' => new DateTimeImmutable('2020-01-14'),
                    'stop'  => new DateTimeImmutable('2020-02-01'),
                ],
            ]
        ];

        yield [
            'start' => new DateTimeImmutable('2021-01-14'),
            'stop'  => new DateTimeImmutable('2021-03-27'),
            'expected' => [
                [
                    'start' => new DateTimeImmutable('2021-03-01'),
                    'stop'  => new DateTimeImmutable('2021-03-27'),
                ],
                [
                    'start' => new DateTimeImmutable('2021-02-01'),
                    'stop'  => new DateTimeImmutable('2021-03-01'),
                ],
                [
                    'start' => new DateTimeImmutable('2021-01-14'),
                    'stop'  => new DateTimeImmutable('2021-02-01'),
                ],
            ]
        ];
    }

    /**
     * @dataProvider sliceDateRangeByMonthProvider
     */
    public function testSliceDateRangeByMonth($start, $stop, $expected)
    {
        $stub = $this->getMockForAbstractClass(AbstractCarbonIntensity::class);
        $output = $this->callPrivateMethod($stub, 'sliceDateRangeByMonth', $start, $stop);

        if (count($expected) === 0) {
            $this->assertNull($output->current());
            return;
        }

        foreach ($expected as $slice) {
            $this->assertEquals($slice['start'], $output->current()['start']);
            $this->assertEquals($slice['stop'], $output->current()['stop']);
            $output->next();
        }
    }

    public function sliceDateRangeByDayProvider()
    {
        yield [
            'start' => new DateTimeImmutable('2021-01-29'),
            'stop'  => new DateTimeImmutable('2021-01-14'),
            'expected' => [
            ]
        ];

        yield [
            'start' => new DateTimeImmutable('2021-01-14'),
            'stop'  => new DateTimeImmutable('2021-01-14'),
            'expected' => [
                new DateTimeImmutable('2021-01-14'),
            ]
        ];

        yield [
            'start' => new DateTimeImmutable('2021-01-14'),
            'stop'  => new DateTimeImmutable('2021-01-17'),
            'expected' => [
                new DateTimeImmutable('2021-01-14'),
                new DateTimeImmutable('2021-01-15'),
                new DateTimeImmutable('2021-01-16'),
                new DateTimeImmutable('2021-01-17'),
            ]
        ];
    }

    /**
     * @dataProvider sliceDateRangeByDayProvider
     */
    public function testSliceDateRangeByDay($start, $stop, $expected)
    {
        $stub = $this->getMockForAbstractClass(AbstractCarbonIntensity::class);
        $output = $this->callPrivateMethod($stub, 'sliceDateRangeByDay', $start, $stop);

        if (count($expected) === 0) {
            $this->assertNull($output->current());
            return;
        }

        foreach ($expected as $slice) {
            $this->assertEquals($slice, $output->current());
            $output->next();
        }
    }

    // public function testIsDownloadComplete()
    // {
    //     $stub = $this->getMockForAbstractClass(AbstractCarbonIntensity::class);
    //     $stub->method('getZones')->willReturn(['FR', 'DE']);
    //     $stub->method('fetchRange')->willReturn([
    //         'source' => 'test_source',
    //         'FR' => [],
    //         'DE' => [],
    //     ]);
    //     $source = new CarbonIntensitySource();
    //     $source->getFromDBByCrit(['name' => 'test_source']);
    //     if ($source->isNewItem()) {
    //         $source = $this->getItem(CarbonIntensitySource::class, ['name' => 'test_source']);
    //     }

    //     $zone = new CarbonIntensityZone();
    //     $zone->getFromDBByCrit(['name' => 'FR']);
    //     if ($zone->isNewItem()) {
    //         $zone = $this->getItem(CarbonIntensityZone::class, ['name' => 'FR']);
    //     }
    //     $zone = new CarbonIntensityZone();
    //     $zone->getFromDBByCrit(['name' => 'DE']);
    //     if ($zone->isNewItem()) {
    //         $zone = $this->getItem(CarbonIntensityZone::class, ['name' => 'DE']);
    //     }

    //     $glpi_config = new GlpiConfig();
    //     $glpi_config->deleteByCriteria([
    //         'context' => 'carbon',
    //         'name'    => ['LIKE', 'download_test_complete_%'],
    //     ]);

    //     $output = $stub->isDownloadComplete();
    //     $this->assertFalse($output);
    //     $cron_task = new CronTask();
    //     $cron_task->fields['param'] = '';
    //     $output = $stub->cronDownload($cron_task);
    //     $this->assertEquals(0, $output);
    //     $output = $stub->isDownloadComplete();
    //     $this->assertTrue($output);
    // }

    public function testFullDownload()
    {
        $zone = new CarbonIntensityZone();
        $zone->getFromDBByCrit(['name' => 'FR']);
        if ($zone->isNewItem()) {
            $zone = $this->getItem(CarbonIntensityZone::class, ['name' => 'FR']);
        }
        $intensity = $this->createStub(CarbonIntensity::class);

        // 4 calls to fetchRange                                example
        // Current month (1 to today)                           2024-07-01 to 2024-07-19
        // month - 1 (1 to last day of month)                   2024-06-01 to 2024-06-30
        // month - 2 (1 to last day of month)                   2024-05-01 to 2024-05-31
        // month - 3 (same day as today to last day of month)   2024-04-19 to 2024-04-30
        $intensity->expects($this->exactly(4))->method('save');

        $instance = $this->getMockForAbstractClass(AbstractCarbonIntensity::class);
        $instance->method('fetchRange')->willReturn(['FR' => []]);
        $start_date = new DateTime('3 months ago');
        $start_date->setTime(0, 0, 0);
        $start_date = DateTimeImmutable::createFromMutable($start_date);
        $stop_date = new DateTimeImmutable('2 days ago');
        $instance->fullDownload('FR', $start_date, $stop_date, $intensity);
    }

    public function testIncrementalDownload()
    {
        $zone = new CarbonIntensityZone();
        $zone->getFromDBByCrit(['name' => 'FR']);
        if ($zone->isNewItem()) {
            $zone = $this->getItem(CarbonIntensityZone::class, ['name' => 'FR']);
        }
        $intensity = $this->createStub(CarbonIntensity::class);

        // 4 calls to fetchRange [3 days ago; today]
        $intensity->expects($this->exactly(4))->method('save');

        $instance = $this->getMockForAbstractClass(AbstractCarbonIntensity::class);
        $instance->method('fetchDay')->willReturn(['FR' => []]);
        $start_date = new DateTime('3 days ago');
        $start_date->setTime(0, 0, 0);
        $start_date = DateTimeImmutable::createFromMutable($start_date);
        $instance->incrementalDownload('FR', $start_date, $intensity);
    }
}
