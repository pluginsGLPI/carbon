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

namespace GlpiPlugin\Carbon\DataSource\Tests;

use DateTime;
use DateTimeImmutable;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\Zone;
use GlpiPlugin\Carbon\DataSource\AbstractCarbonIntensity;
use GlpiPlugin\Carbon\Tests\DbTestCase;

class AbstractCarbonIntensityTest extends DbTestCase
{
    public function sliceDateRangeByMonthProvider()
    {
        yield [
            new DateTimeImmutable('2020-12-01'),
            new DateTimeImmutable('2020-01-31'),
            [
            ]
        ];

        yield [
            new DateTimeImmutable('2020-01-01'),
            new DateTimeImmutable('2020-02-01'),
            [
                [
                    'start' => new DateTimeImmutable('2020-01-01'),
                    'stop'  => new DateTimeImmutable('2020-02-01'),
                ],
            ]
        ];

        yield [
            new DateTimeImmutable('2020-01-14'),
            new DateTimeImmutable('2020-01-31'),
            [
                [
                    'start' => new DateTimeImmutable('2020-01-14'),
                    'stop'  => new DateTimeImmutable('2020-01-31'),
                ],
            ]
        ];

        yield [
            new DateTimeImmutable('2020-01-01'),
            new DateTimeImmutable('2020-01-14'),
            [
                [
                    'start' => new DateTimeImmutable('2020-01-01'),
                    'stop'  => new DateTimeImmutable('2020-01-14'),
                ],
            ]
        ];

        // Bissextile year
        yield [
            new DateTimeImmutable('2020-01-14'),
            new DateTimeImmutable('2020-03-31'),
            [
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
            new DateTimeImmutable('2021-01-14'),
            new DateTimeImmutable('2021-03-27'),
            [
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

    public function testSliceDateRangeByMonth()
    {
        foreach ($this->sliceDateRangeByMonthProvider() as $data) {
            list ($start, $stop, $expected) = $data;
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
    }

    public function sliceDateRangeByDayProvider()
    {
        yield [
            new DateTimeImmutable('2021-01-29'),
            new DateTimeImmutable('2021-01-14'),
            [
            ]
        ];

        yield [
            new DateTimeImmutable('2021-01-14'),
            new DateTimeImmutable('2021-01-14'),
            [
                new DateTimeImmutable('2021-01-14'),
            ]
        ];

        yield [
            new DateTimeImmutable('2021-01-14'),
            new DateTimeImmutable('2021-01-17'),
            [
                new DateTimeImmutable('2021-01-14'),
                new DateTimeImmutable('2021-01-15'),
                new DateTimeImmutable('2021-01-16'),
                new DateTimeImmutable('2021-01-17'),
            ]
        ];
    }

    public function testSliceDateRangeByDay()
    {
        foreach ($this->sliceDateRangeByDayProvider() as $data) {
            list ($start, $stop, $expected) = $data;
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

    //     $zone = new Zone();
    //     $zone->getFromDBByCrit(['name' => 'FR']);
    //     if ($zone->isNewItem()) {
    //         $zone = $this->getItem(Zone::class, ['name' => 'FR']);
    //     }
    //     $zone = new Zone();
    //     $zone->getFromDBByCrit(['name' => 'DE']);
    //     if ($zone->isNewItem()) {
    //         $zone = $this->getItem(Zone::class, ['name' => 'DE']);
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
        $zone = new Zone();
        $zone->getFromDBByCrit(['name' => 'FR']);
        if ($zone->isNewItem()) {
            $zone = $this->getItem(Zone::class, ['name' => 'FR']);
        }
        $intensity = $this->createStub(CarbonIntensity::class);

        $start_date = new DateTime('3 months ago');
        $start_date->setTime(0, 0, 0);
        $start_date = DateTimeImmutable::createFromMutable($start_date);
        $stop_date = new DateTimeImmutable('2 days ago');

        // 4 calls to fetchRange                                example
        // Current month (1 to today)                           2024-07-01 to 2024-07-19
        // month - 1 (1 to last day of month)                   2024-06-01 to 2024-06-30
        // month - 2 (1 to last day of month)                   2024-05-01 to 2024-05-31
        // month - 3 (same day as today to last day of month)   2024-04-19 to 2024-04-30
        // Warning : current month may be ignored if (now - 2 days) results to a date in the previous month
        // This may happen if (day of month) < 2
        $count = $stop_date->format('m') - $start_date->format('m') + 1;
        $count = (($stop_date->format('Y')  - $start_date->format('Y')) * 12)
            + ($stop_date->format('m') - $start_date->format('m') ) + 1;
        $intensity->expects($this->exactly($count))->method('save');

        $instance = $this->getMockForAbstractClass(AbstractCarbonIntensity::class);
        $instance->method('fetchRange')->willReturn(['FR' => []]);
        $instance->fullDownload('FR', $start_date, $stop_date, $intensity);
    }

    public function testIncrementalDownload()
    {
        $zone = new Zone();
        $zone->getFromDBByCrit(['name' => 'FR']);
        if ($zone->isNewItem()) {
            $zone = $this->getItem(Zone::class, ['name' => 'FR']);
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
