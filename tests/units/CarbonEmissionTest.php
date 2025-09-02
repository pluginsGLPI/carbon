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
use DateTime;
use DateInterval;
use DateTimeImmutable;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use GlpiPlugin\Carbon\CarbonEmission;
use PHPUnit\Framework\Attributes\DataProvider;
use User;

class CarbonEmissionTest extends DbTestCase
{
    public static function findGapsProvider(): array
    {
        return [
            ['UTC'],
            ['Europe/Paris'],
        ];
    }

    #[DataProvider('findGapsProvider')]
    public function testFindGaps($timezone)
    {
        $user_id = User::getIdByName('glpi');
        $user = new User();
        $user->update([
            'id' => $user_id,
            'timezone' => $timezone,
        ]);
        $this->login('glpi', 'glpi');
        $this->DBVersionCheck();

        $instance = new CarbonEmission();
        $itemtype = Computer::class;
        $start_date = '2019-01-01 00:00:00';
        $stop_date  = '2023-12-31 00:00:00';
        $asset = $this->createItem($itemtype, [
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
                'start' => '2019-01-01 00:00:00',
                'end'   => '2020-01-01 00:00:00',
            ],
            [
                'start' => '2021-01-01 00:00:00',
                'end'   => '2023-12-31 00:00:00',
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
                'start' => '2019-01-01 00:00:00',
                'end'   => '2020-01-01 00:00:00',
            ],
            [
                'start' => '2020-04-05 00:00:00',
                'end'   => '2020-04-10 00:00:00',
            ],
            [
                'start' => '2021-01-01 00:00:00',
                'end'   => '2023-12-31 00:00:00',
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
                'start' => '2019-01-01 00:00:00',
                'end'   => '2020-01-01 00:00:00',
            ],
            [
                'start' => '2020-04-05 00:00:00',
                'end'   => '2020-04-10 00:00:00',
            ],
            [
                'start' => '2020-06-20 00:00:00',
                'end'   => '2020-06-27 00:00:00',
            ],
            [
                'start' => '2021-01-01 00:00:00',
                'end'   => '2023-12-31 00:00:00',
            ],
        ];
        $this->assertEquals($expected, $gaps);
    }
}
