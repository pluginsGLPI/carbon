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
use DateInterval;
use DateTime;
use DateTimeImmutable;
use GlpiPlugin\Carbon\Zone;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use GlpiPlugin\Carbon\Toolbox;
use Infocom;
use Location;

class ToolboxTest extends DbTestCase
{
    /**
     * #CoversMethod GlpiPlugin\Carbon\Toolbox::getOldestAssetDate
     *
     * @return void
     */
    public function testGetOldestAssetDate()
    {
        $toolbox = new Toolbox();
        $output = $toolbox->getOldestAssetDate();
        $expected = null;
        $this->assertEquals($expected, $output);

        $computer = $this->createItem(Computer::class);
        $output = $toolbox->getLatestAssetDate();
        $expected = null;
        $this->assertEquals($expected, $output);

        $expected = new DateTime('1980-01-01 00:00:00');
        $computer = $this->createItem(Computer::class, [
            'date_creation' => $expected->format('Y-m-d H:i:s'),
        ]);
        $output = $toolbox->getOldestAssetDate();
        $this->assertEquals($expected, $output);

        $expected = new DateTime('2000-01-01 00:00:00');
        $infocom = $this->createItem(Infocom::class, [
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

    /**
     * #CoversMethod GlpiPlugin\Carbon\Toolbox::getLatestAssetDate
     *
     * @return void
     */
    public function testGetLatestAssetDate()
    {
        $toolbox = new Toolbox();
        $output = $toolbox->getLatestAssetDate();
        $expected = null;
        $this->assertEquals($expected, $output);

        $computer = $this->createItem(Computer::class);
        $output = $toolbox->getLatestAssetDate();
        $expected = null;
        $this->assertEquals($expected, $output);


        $expected = new DateTime('2024-06-15 00:00:00');
        $infocom = $this->createItem(Infocom::class, [
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID(),
            'decommission_date' => $expected->format('Y-m-d H:i:s'),
        ]);
        $output = $toolbox->getLatestAssetDate();
        $this->assertEquals($expected, $output);
    }

    /**
     * #CoversMethod GlpiPlugin\Carbon\Toolbox::getDefaultCarbonIntensityDownloadDate
     *
     * @return void
     */
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

    /**
     * #CoversMethod GlpiPlugin\Carbon\Toolbox::yearToLastMonth
     *
     * @return void
     */
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

    /**
     * #CoversMethod GlpiPlugin\Carbon\Toolbox::isLocationExistForZone
     *
     * @return void
     */
    public function testIsLocationExistsForZone()
    {
        $output = Toolbox::isLocationExistForZone('foo');
        $this->assertFalse($output);

        $this->createItem(Zone::class, [
            'name' => 'foo',
        ]);
        $this->createItem(Location::class, [
            'country' => 'foo'
        ]);
        $output = Toolbox::isLocationExistForZone('foo');
        $this->assertTrue($output);
    }

    /**
     * #CoversMethod GlpiPlugin\Carbon\Toolbox::getGwpUsageImpactClasses
     *
     * @return void
     */
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

    /**
     * #CoversMethod GlpiPlugin\Carbon\Toolbox::getUsageImpactClasses
     *
     * @return void
     */
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

    /**
     * #CoversMethod GlpiPlugin\Carbon\Toolbox::getEmbodiedImpactClasses
     *
     * @return void
     */
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
}
