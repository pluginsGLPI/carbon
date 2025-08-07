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
use DateTimeImmutable;
use DBmysql;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use GlpiPlugin\Carbon\Zone;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\CarbonIntensitySource_Zone;
use GlpiPlugin\Carbon\DataSource\AbstractCarbonIntensity;
use Infocom;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\Output;

class CarbonIntensityTest extends DbTestCase
{
    /**
     * @covers GlpiPlugin\Carbon\CarbonIntensity::getKnownDatesQuery
     * @covers GlpiPlugin\Carbon\CarbonIntensity::getLastKnownDate
     *
     * @return void
     */
    public function testGetLastKnownDate()
    {
        $instance = new CarbonIntensity();
        $result = $instance->getLastKnownDate('foo', 'bar');
        $this->assertNull($result);

        $zone = $this->getItem(Zone::class, [
            'name' => 'foo',
        ]);
        $source = $this->getItem(CarbonIntensitySource::class, [
            'name' => 'bar'
        ]);
        $source_zone = $this->getItem(CarbonIntensitySource_Zone::class, [
            $source::getForeignKeyField() => $source->getID(),
            $zone::getForeignKeyField() => $zone->getID()
        ]);
        $result = $instance->getLastKnownDate('foo', 'bar');
        $this->assertNull($result);

        $intensity = $this->getItem(CarbonIntensity::class, [
            'date' => '2023-02-01 00:00:00',
            $source::getForeignKeyField() => $source->getID(),
            $zone::getForeignKeyField() => $zone->getID(),
            'intensity' => 255,
            'data_quality' => 2,
        ]);
        $expected = '2024-02-01 00:00:00';
        $intensity = $this->getItem(CarbonIntensity::class, [
            'date' => $expected,
            $source::getForeignKeyField() => $source->getID(),
            $zone::getForeignKeyField() => $zone->getID(),
            'intensity' => 255,
            'data_quality' => 2,
        ]);
        $result = $instance->getLastKnownDate('foo', 'bar');
        $this->assertEquals($expected, $result->format('Y-m-d H:i:s'));
    }

    /**
     * @covers GlpiPlugin\Carbon\CarbonIntensity::getKnownDatesQuery
     * @covers GlpiPlugin\Carbon\CarbonIntensity::getFirstKnownDate
     *
     * @return void
     */
    public function testGetFirstKnownDate()
    {
        $instance = new CarbonIntensity();
        $result = $instance->getFirstKnownDate('foo', 'bar');
        $this->assertNull($result);

        $zone = $this->getItem(Zone::class, [
            'name' => 'foo',
        ]);
        $source = $this->getItem(CarbonIntensitySource::class, [
            'name' => 'bar'
        ]);
        $source_zone = $this->getItem(CarbonIntensitySource_Zone::class, [
            $source::getForeignKeyField() => $source->getID(),
            $zone::getForeignKeyField() => $zone->getID()
        ]);
        $result = $instance->getFirstKnownDate('foo', 'bar');
        $this->assertNull($result);

        $intensity = $this->getItem(CarbonIntensity::class, [
            'date' => '2025-02-01 00:00:00',
            $source::getForeignKeyField() => $source->getID(),
            $zone::getForeignKeyField() => $zone->getID(),
            'intensity' => 255,
            'data_quality' => 2,
        ]);
        $expected = '2024-02-01 00:00:00';
        $intensity = $this->getItem(CarbonIntensity::class, [
            'date' => $expected,
            $source::getForeignKeyField() => $source->getID(),
            $zone::getForeignKeyField() => $zone->getID(),
            'intensity' => 255,
            'data_quality' => 2,
        ]);
        $result = $instance->getFirstKnownDate('foo', 'bar');
        $this->assertEquals($expected, $result->format('Y-m-d H:i:s'));
    }

    /**
     * @covers GlpiPlugin\Carbon\CarbonIntensity::findGaps
     *
     * @return void
     */
    public function testFindGaps()
    {
        /** @var DBmysql $DB */
        global $DB;

        // Check DBMS version as this tests does not works on minimal DBMS requirement of GLPI 10.0
        $db_version_full = $DB->getVersion();
        // Isolate version number
        $db_version = preg_replace('/[^0-9.]/', '', $db_version_full);
        // Check if is MariaDB
        $min_version = '8.0';
        if (strpos($db_version_full, 'MariaDB') !== false) {
            $min_version = '10.2';
        }
        if (version_compare($db_version, $min_version, '<') || version_compare($db_version, $min_version, '<')) {
            $this->markTestSkipped('Test requires MySQL 8.0 or MariaDB 10.2');
        }

        $this->login('glpi', 'glpi');

        $source = $this->getItem(CarbonIntensitySource::class, [
            'name' => 'test_source',
        ]);
        $zone = $this->getItem(Zone::class, [
            'name' => 'test_zone',
            'plugin_carbon_carbonintensitysources_id_historical' => $source->getID(),
        ]);
        $source_zone = $this->getItem(CarbonIntensitySource_Zone::class, [
            'plugin_carbon_carbonintensitysources_id' => $source->getID(),
            'plugin_carbon_zones_id' => $zone->getID(),
        ]);

        // create sample intensity data
        $table = CarbonIntensity::getTable();
        $start_date = new DateTime('2024-01-01 00:00:00');
        $end_date = new DateTime('2024-03-01 00:00:00');
        $cursor_date = clone $start_date;
        while ($cursor_date < $end_date) {
            $DB->insert($table, [
                'date' => $cursor_date->format('Y-m-d H:i:s'),
                'plugin_carbon_carbonintensitysources_id' => $source->getID(),
                'plugin_carbon_zones_id' => $zone->getID(),
                'intensity' => 1,
            ]);
            $cursor_date->modify('+1 hour');
        }

        $carbon_intensity = new CarbonIntensity();
        $output = $carbon_intensity->findGaps($source->getID(), $zone->getID(), $start_date, $end_date);
        $this->assertEquals([], $output);

        // delete some samples at the beginning
        $delete_before_date = new DateTime('2024-01-03 12:00:00');
        $DB->delete($table, [
            'plugin_carbon_carbonintensitysources_id' => $source->getID(),
            'plugin_carbon_zones_id' => $zone->getID(),
            'date' => ['<', $delete_before_date->format('Y-m-d H:i:s')],
        ]);

        $output = $carbon_intensity->findGaps($source->getID(), $zone->getID(), $start_date, $end_date);
        $this->assertEquals([
            [
                'start' => $start_date->format('Y-m-d H:i:s'),
                'end' => $delete_before_date->format('Y-m-d H:i:s'),
            ],
        ], $output);

        // delete some samples at the end
        $delete_after_date = new DateTime('2024-02-17 09:00:00');
        $DB->delete($table, [
            'plugin_carbon_carbonintensitysources_id' => $source->getID(),
            'plugin_carbon_zones_id' => $zone->getID(),
            'date' => ['>=', $delete_after_date->format('Y-m-d H:i:s')],
        ]);
        $output = $carbon_intensity->findGaps($source->getID(), $zone->getID(), $start_date, $end_date);
        $this->assertEquals([
            [
                'start' => $start_date->format('Y-m-d H:i:s'),
                'end' => $delete_before_date->format('Y-m-d H:i:s'),
            ],
            [
                'start' => $delete_after_date->format('Y-m-d H:i:s'),
                'end' => $end_date->format('Y-m-d H:i:s'),
            ],
        ], $output);

        // delete some samples in the middle
        $delete_middle_start_date = new DateTime('2024-01-29 06:00:00');
        $delete_middle_end_date = new DateTime('2024-02-05 18:00:00');
        $DB->delete($table, [
            'plugin_carbon_carbonintensitysources_id' => $source->getID(),
            'plugin_carbon_zones_id' => $zone->getID(),
            'AND' => [
                ['date' => ['>=', $delete_middle_start_date->format('Y-m-d H:i:s')]],
                ['date' => ['<', $delete_middle_end_date->format('Y-m-d H:i:s')]],
            ]
        ]);
        $output = $carbon_intensity->findGaps($source->getID(), $zone->getID(), $start_date, $end_date);
        $this->assertEquals([
            [
                'start' => $start_date->format('Y-m-d H:i:s'),
                'end' => $delete_before_date->format('Y-m-d H:i:s'),
            ],
            [
                'start' => $delete_middle_start_date->format('Y-m-d H:i:s'),
                'end' => $delete_middle_end_date->format('Y-m-d H:i:s'),
            ],
            [
                'start' => $delete_after_date->format('Y-m-d H:i:s'),
                'end' => $end_date->format('Y-m-d H:i:s'),
            ],
        ], $output);

        // restore the deleted samples at the beginning
        $cursor_date = clone $start_date;
        while ($cursor_date < $delete_before_date) {
            $DB->insert($table, [
                'date' => $cursor_date->format('Y-m-d H:i:s'),
                'plugin_carbon_carbonintensitysources_id' => $source->getID(),
                'plugin_carbon_zones_id' => $zone->getID(),
                'intensity' => 1,
            ]);
            $cursor_date->modify('+1 hour');
        }

        $output = $carbon_intensity->findGaps($source->getID(), $zone->getID(), $start_date, $end_date);
        $this->assertEquals([
            [
                'start' => $delete_middle_start_date->format('Y-m-d H:i:s'),
                'end' => $delete_middle_end_date->format('Y-m-d H:i:s'),
            ],
            [
                'start' => $delete_after_date->format('Y-m-d H:i:s'),
                'end' => $end_date->format('Y-m-d H:i:s'),
            ],
        ], $output);

        // restore the deleted samples at the middle
        $cursor_date = clone $delete_middle_start_date;
        while ($cursor_date < $delete_middle_end_date) {
            $DB->insert($table, [
                'date' => $cursor_date->format('Y-m-d H:i:s'),
                'plugin_carbon_carbonintensitysources_id' => $source->getID(),
                'plugin_carbon_zones_id' => $zone->getID(),
                'intensity' => 1,
            ]);
            $cursor_date->modify('+1 hour');
        }

        $output = $carbon_intensity->findGaps($source->getID(), $zone->getID(), $start_date, $end_date);
        $this->assertEquals([
            [
                'start' => $delete_after_date->format('Y-m-d H:i:s'),
                'end' => $end_date->format('Y-m-d H:i:s'),
            ],
        ], $output);
    }

    public function testGetDownloadStartDate()
    {
        $instance = new CarbonIntensity();

        $data_source = $this->getMockBuilder(AbstractCarbonIntensity::class)
            ->getMock();
        $result = $instance->getDownloadStartDate('foo', $data_source);
        $expected = (new DateTime('13 months ago'))->setTime(0, 0, 0); // CarbonIntensity::MIN_HISTORY_LENGTH
        $this->assertEquals($expected, $result);

        $computer = $this->getItem(Computer::class);
        $infocom = $this->getItem(Infocom::class, [
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID(),
            'buy_date' => '2022-02-01',
        ]);

        $result = $instance->getDownloadStartDate('foo', $data_source);
        $expected = (new DateTime('2022-02-01'))->setTime(0, 0, 0); // CarbonIntensity::MIN_HISTORY_LENGTH
        $this->assertEquals($expected, $result);
    }

    /**
     * @covers GlpiPlugin\Carbon\CarbonIntensity::getDownloadStopDate
     *
     * @return void
     */
    public function testGetDownloadStopDate()
    {
        $instance = new CarbonIntensity();

        $data_source = $this->getMockBuilder(AbstractCarbonIntensity::class)
            ->getMock();
        $data_source->method('getSourceName')->willReturn('bar');
        $data_source->method('getMaxIncrementalAge')->willReturn(
            DateTimeImmutable::createFromMutable($expected = (new DateTime('15 days ago'))->setTime(0, 0, 0))
        );

        $result = $instance->getDownloadStopDate('foo', $data_source);
        $this->assertEquals($expected, $result);

        $zone = $this->getItem(Zone::class, [
            'name' => 'foo',
        ]);
        $source = $this->getItem(CarbonIntensitySource::class, [
            'name' => 'bar'
        ]);
        $expected = new DateTimeImmutable('2019-01-31 23:00:00');
        $source_zone = $this->getItem(CarbonIntensitySource_Zone::class, [
            $source::getForeignKeyField() => $source->getID(),
            $zone::getForeignKeyField() => $zone->getID(),
        ]);
        $intensity = $this->getItem(CarbonIntensity::class, [
            'date' => '2019-02-01',
            $source::getForeignKeyField() => $source->getID(),
            $zone::getForeignKeyField() => $zone->getID(),
            'intensity' => 255,
            'data_quality' => 2,
        ]);
        $result = $instance->getDownloadStopDate('foo', $data_source);
        $this->assertEquals($expected, $result);
    }

    /**
     * @covers GlpiPlugin\Carbon\CarbonIntensity::downloadOneZone
     *
     * @return void
     */
    public function testDownloadOneZone()
    {
        $instance = new CarbonIntensity();
        $result = $instance->getLastKnownDate('foo', 'bar');
        $this->assertNull($result);

        $zone = $this->getItem(Zone::class, [
            'name' => 'foo',
        ]);
        $source = $this->getItem(CarbonIntensitySource::class, [
            'name' => 'bar'
        ]);
        $source_zone = $this->getItem(CarbonIntensitySource_Zone::class, [
            $source::getForeignKeyField() => $source->getID(),
            $zone::getForeignKeyField() => $zone->getID()
        ]);

        $data_source = $this->getMockBuilder(AbstractCarbonIntensity::class)
            ->getMock();
        $hours = null;
        $data_source->method('fullDownload')->willReturnCallback(
            function ($zone_name, $gap_start, $gap_end, $carbon_intensity, $limit, $progress_bar) use (&$hours) {
                $diff = $gap_end->diff($gap_start);
                $hours = $diff->days * 24 + $diff->h;
                return $hours;
            }
        );
        $output = $this->getMockBuilder(Output::class)
            ->getMock();
        $progress_bar = new ProgressBar($output);

        $result = $instance->downloadOneZone($data_source, 'foo', 1, $progress_bar);
        $this->assertEquals($hours, $result);
        $this->assertEquals($hours, $progress_bar->getMaxSteps());
    }
}
