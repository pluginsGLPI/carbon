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
use DateInterval;
use DateTimeImmutable;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use GlpiPlugin\Carbon\CarbonEmission;

class CarbonEmissionTest extends DbTestCase
{
    public function testFindGaps()
    {
        $instance = new CarbonEmission();
        $itemtype = Computer::class;
        $start_date = '2019-01-01 00:00:00';
        $stop_date  = '2023-12-31 00:00:00';
        $asset = $this->getItem($itemtype, [
            'date_creation' => $start_date,
        ]);
        $emission_start = new DateTime('2020-01-01 00:00:00');
        $emission_length = new DateInterval('P1Y');
        $this->createCarbonEmissionData(
            $asset,
            $emission_start,
            $emission_length,
            20,
            12
        );

        $start_date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $start_date);
        $stop_date =  DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $stop_date);
        $gaps = $instance->findGaps($itemtype, $asset->getID(), $start_date, $stop_date);
        $expected = [
            [
                'start' => strtotime('2019-01-01 00:00:00'),
                'end'   => strtotime('2019-12-31 00:00:00'),
            ],
            [
                'start' => strtotime('2021-01-01 00:00:00'),
                'end'   => strtotime('2023-12-31 00:00:00'),
            ],
        ];
        $this->assertEquals($expected, $gaps);

        // Create a gap
        $gap_start = '2020-04-05 00:00:00';
        $gap_end   = '2020-04-09 00:00:00';
        $carbon_emission = new CarbonEmission();
        $carbon_emission->deleteByCriteria([
            ['date' => ['>=', $gap_start]],
            ['date' => ['<=', $gap_end]],
        ]);

        $gaps = $instance->findGaps($itemtype, $asset->getID(), $start_date, $stop_date);
        $expected = [
            [
                'start' => strtotime('2019-01-01 00:00:00'),
                'end'   => strtotime('2019-12-31 00:00:00'),
            ],
            [
                'start' => strtotime('2020-04-05 00:00:00'),
                'end'   => strtotime('2020-04-09 00:00:00'),
            ],
            [
                'start' => strtotime('2021-01-01 00:00:00'),
                'end'   => strtotime('2023-12-31 00:00:00'),
            ],
        ];
        $this->assertEquals($expected, $gaps);

        // Create an other gap
        $gap_start_2 = '2020-06-20 00:00:00';
        $gap_end_2   = '2020-06-26 00:00:00';
        $carbon_emission = new CarbonEmission();
        $carbon_emission->deleteByCriteria([
            ['date' => ['>=', $gap_start_2]],
            ['date' => ['<=', $gap_end_2]],
        ]);
        $gaps = $instance->findGaps($itemtype, $asset->getID(), $start_date, $stop_date);
        $expected = [
            [
                'start' => strtotime('2019-01-01 00:00:00'),
                'end'   => strtotime('2019-12-31 00:00:00'),
            ],
            [
                'start' => strtotime('2020-04-05 00:00:00'),
                'end'   => strtotime('2020-04-09 00:00:00'),
            ],
            [
                'start' => strtotime('2020-06-20 00:00:00'),
                'end'   => strtotime('2020-06-26 00:00:00'),
            ],
            [
                'start' => strtotime('2021-01-01 00:00:00'),
                'end'   => strtotime('2023-12-31 00:00:00'),
            ],
        ];
        $this->assertEquals($expected, $gaps);
    }
}
