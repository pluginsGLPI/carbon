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

namespace GlpiPlugin\Carbon\DataSource\CarbonIntensity\Tests;

use DateTimeImmutable;
use GlpiPlugin\Carbon\DataSource\CarbonIntensity\AbstractClient;
use GlpiPlugin\Carbon\Tests\DbTestCase;

class AbstractClientTest extends DbTestCase
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
            $stub = $this->getMockBuilder(AbstractClient::class)->getMock();
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
            $stub = $this->getMockBuilder(AbstractClient::class)->getMock();
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
}
