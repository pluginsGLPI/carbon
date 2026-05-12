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

use Computer as GlpiComputer;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\Source;
use GlpiPlugin\Carbon\Source_Zone;
use GlpiPlugin\Carbon\Toolbox;
use GlpiPlugin\Carbon\Zone;
use Infocom;
use Location;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Toolbox::class)]
class ToolboxTest extends DbTestCase
{
    public function test_getOldestAssetDate_returns_null_when_no_asset_is_in_db()
    {
        $toolbox = new Toolbox();
        $output = $toolbox->getOldestAssetDate();
        $expected = null;
        $this->assertEquals($expected, $output);
    }

    public function test_getOldestAssetDate_returns_null_when_no_asset_has_inventory_entry_date()
    {
        $toolbox = new Toolbox();
        $computer = $this->createItem(GlpiComputer::class, [
            'date_creation' => null,
        ]);
        $output = $toolbox->getLatestAssetDate();
        $expected = null;
        $this->assertEquals($expected, $output);
    }

    public function test_getOldestAssetDate_returns_date_creation_when_asset_has_a_date_creation()
    {
        $toolbox = new Toolbox();
        $expected = new DateTime('1980-01-01 00:00:00');
        $computer = $this->createItem(GlpiComputer::class, [
            'date_creation' => $expected->format('Y-m-d H:i:s'),
        ]);
        $output = $toolbox->getOldestAssetDate();
        $this->assertEquals($expected, $output);
    }

    public function test_getOldestAssetDate_returns_use_date_when_asset_has_a_use_date()
    {
        $toolbox = new Toolbox();
        $expected = new DateTime('2000-01-01 00:00:00');
        $computer = $this->createItem(GlpiComputer::class, [
            'date_creation' => null,
        ]);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype'    => $computer->getType(),
            'items_id'    => $computer->getID(),
            'entities_id' => $computer->fields['entities_id'],
            'use_date'    => $expected->format('Y-m-d H:i:s'),
        ]);
        $output = $toolbox->getOldestAssetDate();
        $this->assertEquals($expected, $output);
    }

    public function test_getOldestAssetDate_returns_use_date_when_asset_has_a_use_date_and_buy_date()
    {
        $toolbox = new Toolbox();
        $computer = $this->createItem(GlpiComputer::class, [
            'date_creation' => null,
        ]);
        $expected = new DateTime('2000-01-01 00:00:00');
        $infocom = $this->createItem(Infocom::class, [
            'itemtype'    => $computer->getType(),
            'items_id'    => $computer->getID(),
            'entities_id' => $computer->fields['entities_id'],
            'use_date'    => $expected->format('Y-m-d H:i:s'),
            'buy_date'    => '1999-01-01 00:00:00',
        ]);
        $output = $toolbox->getOldestAssetDate();
        $this->assertEquals($expected, $output);
    }

    public function test_getOldestAssetDate_returns_use_date_when_asset_has_a_use_date_and_buy_date_and_delivery_date()
    {
        $toolbox = new Toolbox();
        $computer = $this->createItem(GlpiComputer::class, [
            'date_creation' => null,
        ]);
        $expected = new DateTime('2000-01-01 00:00:00');
        $infocom = $this->createItem(Infocom::class, [
            'itemtype'      => $computer->getType(),
            'items_id'      => $computer->getID(),
            'entities_id'   => $computer->fields['entities_id'],
            'use_date'      => $expected->format('Y-m-d H:i:s'),
            'buy_date'      => '1999-01-01 00:00:00',
            'delivery_date' => '1998-01-01 00:00:00',
        ]);
        $output = $toolbox->getOldestAssetDate();
        $this->assertEquals($expected, $output);
    }

    public function test_getLatestAssetDate_returns_null_when_no_asset_is_in_db()
    {
        $toolbox = new Toolbox();
        $output = $toolbox->getLatestAssetDate();
        $expected = null;
        $this->assertEquals($expected, $output);
    }

    public function test_getLatestAssetDate_returns_null_when_no_asset_has_decommission_date()
    {
        // Without infocom object
        $toolbox = new Toolbox();
        $computer = $this->createItem(GlpiComputer::class);
        $output = $toolbox->getLatestAssetDate();
        $expected = null;
        $this->assertEquals($expected, $output);

        // With infocom object without decommission date
        $infocom = $this->createItem(Infocom::class, [
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID(),
        ]);
        $output = $toolbox->getLatestAssetDate();
        $expected = null;
        $this->assertEquals($expected, $output);
    }

    public function test_getLatestAssetDate_returns_decommission_date_when_asset_has_decommission_date()
    {
        $toolbox = new Toolbox();
        $expected = new DateTime('2024-06-15 00:00:00');
        $computer = $this->createItem(GlpiComputer::class);
        $infocom = $this->createItem(Infocom::class, [
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
            new DateTime('2023-04-01 00:00:00'),
        ];
        $this->assertEquals($expected, $output);

        // Test leap year case (2024 is a leap year)
        $output = $instance->yearToLastMonth(new DateTimeImmutable('2025-03-06 12:43:34'));
        $expected = [
            new DateTime('2024-03-01 00:00:00'),
            new DateTime('2025-03-01 00:00:00'),
        ];
        $this->assertEquals($expected, $output);
    }

    public function testIsLocationExistsForZone()
    {
        $output = Toolbox::isLocationExistForZone('foo');
        $this->assertFalse($output);

        $this->createItem(Zone::class, [
            'name' => 'foo',
        ]);
        $this->createItem(Location::class, [
            'country' => 'foo',
        ]);
        $output = Toolbox::isLocationExistForZone('foo');
        $this->assertTrue($output);
    }

    public function testGetGwpUsageImpactClasses()
    {
        $output = Toolbox::getGwpUsageImpactClasses();
        $this->assertIsArray($output);
        $this->assertEquals([
            'GlpiPlugin\\Carbon\\Impact\\History\\Computer',
            'GlpiPlugin\\Carbon\\Impact\\History\\Monitor',
            'GlpiPlugin\\Carbon\\Impact\\History\\NetworkEquipment',
        ], $output);
    }

    public function testGetUsageImpactClasses()
    {
        $output = Toolbox::getUsageImpactClasses();
        $this->assertIsArray($output);
        $this->assertEquals([
            'GlpiPlugin\\Carbon\\Impact\\Usage\\Boavizta\\Computer',
            'GlpiPlugin\\Carbon\\Impact\\Usage\\Boavizta\\Monitor',
            // 'GlpiPlugin\\Carbon\\Impact\\Usage\\Boavizta\\NetworkEquipment',
        ], $output);
    }

    public function testGetEmbodiedImpactClasses()
    {
        $output = Toolbox::getEmbodiedImpactClasses();
        $this->assertIsArray($output);
        // TODO: implement the 2 commented out classes
        $this->assertEquals([
            'GlpiPlugin\\Carbon\\Impact\\Embodied\\Boavizta\\Computer',
            'GlpiPlugin\\Carbon\\Impact\\Embodied\\Boavizta\\Monitor',
            // 'GlpiPlugin\\Carbon\\Impact\\Embodied\\Boavizta\\NetworkEquipment',
        ], $output);
    }

    public function testDateIntervalToMySQLInterval()
    {
        $interval = new DateInterval('P3Y');
        $result = Toolbox::dateIntervalToMySQLInterval($interval);
        $this->assertEquals('INTERVAL 3 YEAR', $result);

        $interval = new DateInterval('P2M');
        $result = Toolbox::dateIntervalToMySQLInterval($interval);
        $this->assertEquals('INTERVAL 2 MONTH', $result);

        $interval = new DateInterval('P12D');
        $result = Toolbox::dateIntervalToMySQLInterval($interval);
        $this->assertEquals('INTERVAL 12 DAY', $result);

        $interval = new DateInterval('PT5H');
        $result = Toolbox::dateIntervalToMySQLInterval($interval);
        $this->assertEquals('INTERVAL 5 HOUR', $result);

        $interval = new DateInterval('PT34M');
        $result = Toolbox::dateIntervalToMySQLInterval($interval);
        $this->assertEquals('INTERVAL 34 MINUTE', $result);

        $interval = new DateInterval('PT14S');
        $result = Toolbox::dateIntervalToMySQLInterval($interval);
        $this->assertEquals('INTERVAL 14 SECOND', $result);

        $interval = new DateInterval('P3YT40M');
        $result = Toolbox::dateIntervalToMySQLInterval($interval);
        $this->assertEquals('INTERVAL 3 YEAR + INTERVAL 40 MINUTE', $result);
    }

    public function test_FindTemporalGapsInTable_returns_the_whole_interval_when_no_rows()
    {
        $table = getTableForItemType(CarbonIntensity::class);
        $source = $this->createItem(Source::class);
        $zone   = $this->createItem(Zone::class);
        $criteria = [
            getForeignKeyFieldForItemType(Source::class) => $source->getID(),
            getForeignKeyFieldForItemType(Zone::class)   => $zone->getID(),
        ];
        $source_zone = $this->createItem(Source_Zone::class, $criteria);

        // Test when no record exists in the requested interval
        $result = Toolbox::findTemporalGapsInTable(
            $table,
            new DateTime('2020-01-01 00:00:00'),
            new DateInterval('PT1H'),
            new DateTime('2020-06-01 00:00:00'),
            $criteria
        );
        $result = iterator_to_array($result);
        $expected = [
            [
                'start' => '2020-01-01 00:00:00',
                'end'  =>  '2020-06-01 00:00:00',
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    public function test_FindTemporalGapsInTable_returns_interval_after_rows_when__rows_are_on_the_beginning()
    {
        // Test when there is a record matching the beginning of the interval,
        // but none matching the end
        $table = getTableForItemType(CarbonIntensity::class);
        $source = $this->createItem(Source::class);
        $zone   = $this->createItem(Zone::class);
        $criteria = [
            getForeignKeyFieldForItemType(Source::class) => $source->getID(),
            getForeignKeyFieldForItemType(Zone::class)   => $zone->getID(),
        ];
        $this->createItem(CarbonIntensity::class, [
            'date' => '2020-01-01 00:00:00',
        ] + $criteria);
        $result = Toolbox::findTemporalGapsInTable(
            $table,
            new DateTime('2020-01-01 00:00:00'),
            new DateInterval('PT1H'),
            new DateTime('2020-06-01 00:00:00'),
            $criteria
        );
        $result = iterator_to_array($result);
        $expected = [
            [
                'start' => '2020-01-01 01:00:00',
                'end'  =>  '2020-06-01 00:00:00',
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    public function test_FindTemporalGapsInTable_returns_interval_before_rows_when_rows_are_on_the_end()
    {
        $table = getTableForItemType(CarbonIntensity::class);
        $source = $this->createItem(Source::class);
        $zone   = $this->createItem(Zone::class);
        $criteria = [
            getForeignKeyFieldForItemType(Source::class) => $source->getID(),
            getForeignKeyFieldForItemType(Zone::class)   => $zone->getID(),
        ];
        $this->createItem(CarbonIntensity::class, [
            'date' => '2020-01-01 00:00:00',
        ] + $criteria);
        // Test when there is a record matching the end of the interval,
        // but none matching the beginning
        $result = Toolbox::findTemporalGapsInTable(
            $table,
            new DateTime('2019-01-01 00:00:00'),
            new DateInterval('PT1H'),
            new DateTime('2020-01-01 00:00:00'),
            $criteria
        );
        $result = iterator_to_array($result);
        $expected = [
            [
                'start' => '2019-01-01 00:00:00',
                'end'  =>  '2020-01-01 00:00:00',
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    public function test_FindTemporalGapsInTable_returns_two_intervals_when_row_is_in_tne_middle()
    {
        $table = getTableForItemType(CarbonIntensity::class);
        $source = $this->createItem(Source::class);
        $zone   = $this->createItem(Zone::class);
        $criteria = [
            getForeignKeyFieldForItemType(Source::class) => $source->getID(),
            getForeignKeyFieldForItemType(Zone::class)   => $zone->getID(),
        ];
        $this->createItem(CarbonIntensity::class, [
            'date' => '2020-01-01 00:00:00',
        ] + $criteria);
        // Test when there is a record in the requested interval
        $result = Toolbox::findTemporalGapsInTable(
            $table,
            new DateTime('2019-01-01 00:00:00'),
            new DateInterval('PT1H'),
            new DateTime('2021-01-01 00:00:00'),
            $criteria
        );
        $result = iterator_to_array($result);
        $expected = [
            [
                'start' => '2019-01-01 00:00:00',
                'end'  =>  '2020-01-01 00:00:00',
            ],
            [
                'start' => '2020-01-01 01:00:00',
                'end'  =>  '2021-01-01 00:00:00',
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    public function test_FindTemporalGapsInTable_returns_three_intervals_when_non_consecutive_rows_are_in_the_middle()
    {
        $table = getTableForItemType(CarbonIntensity::class);
        $source = $this->createItem(Source::class);
        $zone   = $this->createItem(Zone::class);
        $criteria = [
            getForeignKeyFieldForItemType(Source::class) => $source->getID(),
            getForeignKeyFieldForItemType(Zone::class)   => $zone->getID(),
        ];
        // Test when there is are 2 non consecutive records in the requesterd interval
        $this->createItem(CarbonIntensity::class, [
            'date' => '2020-01-01 00:00:00',
        ] + $criteria);
        $this->createItem(CarbonIntensity::class, [
            'date' => '2020-06-01 00:00:00',
        ] + $criteria);
        $result = Toolbox::findTemporalGapsInTable(
            $table,
            new DateTime('2019-01-01 00:00:00'),
            new DateInterval('PT1H'),
            new DateTime('2021-01-01 00:00:00'),
            $criteria
        );
        $result = iterator_to_array($result);
        $expected = [
            [
                'start' => '2019-01-01 00:00:00',
                'end'  =>  '2020-01-01 00:00:00',
            ],
            [
                'start' => '2020-01-01 01:00:00',
                'end'  =>  '2020-06-01 00:00:00',
            ],
            [
                'start' => '2020-06-01 01:00:00',
                'end'  =>  '2021-01-01 00:00:00',
            ],
        ];
        $this->assertEquals($expected, $result);

    }

    public function test_FindTemporalGapsInTable_returns_two_intervals_when_consecutive_rows_are_in_the_middle()
    {
        $table = getTableForItemType(CarbonIntensity::class);
        $source = $this->createItem(Source::class);
        $zone   = $this->createItem(Zone::class);
        $criteria = [
            getForeignKeyFieldForItemType(Source::class) => $source->getID(),
            getForeignKeyFieldForItemType(Zone::class)   => $zone->getID(),
        ];
        // Test when there is are 2 consecutive records
        // in 2 separated groups in the requesterd interval
        $this->createItem(CarbonIntensity::class, [
            'date' => '2020-01-01 00:00:00',
        ] + $criteria);
        $this->createItem(CarbonIntensity::class, [
            'date' => '2020-01-01 01:00:00',
        ] + $criteria);
        $result = Toolbox::findTemporalGapsInTable(
            $table,
            new DateTime('2019-01-01 00:00:00'),
            new DateInterval('PT1H'),
            new DateTime('2021-01-01 00:00:00'),
            $criteria
        );
        $result = iterator_to_array($result);
        $expected = [
            [
                'start' => '2019-01-01 00:00:00',
                'end'  =>  '2020-01-01 00:00:00',
            ],
            [
                'start' => '2020-01-01 02:00:00',
                'end'  =>  '2021-01-01 00:00:00',
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    public function test_getInfocomLifespanInMonth_returns_interval_when_all_requirements_are_met()
    {
        $glpi_computer = $this->createItem(GlpiComputer::class, [
            'date_creation' => '2025-02-03 11:00:00',
        ]);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype' => get_class($glpi_computer),
            'items_id' => $glpi_computer->getID(),
            'decommission_date' => '2026-02-03 11:00:00',
        ]);

        $result = Toolbox::getInfocomLifespanInMonth($infocom);
        $this->assertSame(12, $result);
    }
    public function test_getInfocomLifespanInMonth_returns_null_when_no_start_date()
    {
        $glpi_computer = $this->createItem(GlpiComputer::class, [
            'date_creation' => '2025-02-03 11:00:00',
        ]);
        $this->updateItem($glpi_computer, ['date_creation' => null]);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype' => get_class($glpi_computer),
            'items_id' => $glpi_computer->getID(),
            'decommission_date' => '2026-02-03 11:00:00',
        ]);

        $result = Toolbox::getInfocomLifespanInMonth($infocom);
        $this->assertNull($result);
    }

    public function test_getInfocomLifespanInMonth_returns_null_when_start_date_of_asset()
    {
        $glpi_computer = $this->createItem(GlpiComputer::class, [
            'date_creation' => '2025-02-03 11:00:00',
        ]);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype' => get_class($glpi_computer),
            'items_id' => $glpi_computer->getID(),
            'decommission_date' => '2026-02-03 11:00:00',
        ]);

        $result = Toolbox::getInfocomLifespanInMonth($infocom);
        $this->assertSame(12, $result);
    }

    public function test_getInfocomLifespanInMonth_returns_interval_from_use_date_over_creation_date()
    {
        $glpi_computer = $this->createItem(GlpiComputer::class, [
            'date_creation' => '2024-02-03 11:00:00',
        ]);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype' => get_class($glpi_computer),
            'items_id' => $glpi_computer->getID(),
            'use_date' => '2025-02-03',
            'decommission_date' => '2026-02-03 11:00:00',
        ]);

        $result = Toolbox::getInfocomLifespanInMonth($infocom);
        $this->assertSame(12, $result);
    }

    public function test_getInfocomLifespanInMonth_returns_interval_from_delivery_date_over_creation_date_and_use_date()
    {
        $glpi_computer = $this->createItem(GlpiComputer::class, [
            'date_creation' => '2024-02-03 11:00:00',
        ]);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype' => get_class($glpi_computer),
            'items_id' => $glpi_computer->getID(),
            'use_date' => '2025-02-03',
            'delivery_date' => '2023-02-03',
            'decommission_date' => '2026-02-03 11:00:00',
        ]);

        $result = Toolbox::getInfocomLifespanInMonth($infocom);
        $this->assertSame(36, $result);
    }

    public function test_getInfocomLifespanInMonth_returns_interval_from_buy_date_over_creation_date_and_delivery_date()
    {
        $glpi_computer = $this->createItem(GlpiComputer::class, [
            'date_creation' => '2024-02-03 11:00:00',
        ]);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype' => get_class($glpi_computer),
            'items_id' => $glpi_computer->getID(),
            'delivery_date' => '2025-02-03',
            'use_date' => '2023-02-03',
            'buy_date' => '2022-02-03',
            'decommission_date' => '2026-02-03 11:00:00',
        ]);

        $result = Toolbox::getInfocomLifespanInMonth($infocom);
        $this->assertSame(48, $result);
    }
}
