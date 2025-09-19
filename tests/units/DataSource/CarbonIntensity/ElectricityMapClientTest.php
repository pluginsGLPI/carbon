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

use GlpiPlugin\Carbon\DataSource\CarbonIntensity\ElectricityMapClient;
use GlpiPlugin\Carbon\DataSource\RestApiClientInterface;
use GlpiPlugin\Carbon\Source;
use GlpiPlugin\Carbon\Zone;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use DateTimeImmutable;
use GlpiPlugin\Carbon\Source_Zone;

class ElectricityMapClientTest extends DbTestCase
{
    public function testEnableHistorical()
    {
        $client = $this->createStub(RestApiClientInterface::class);
        /** @var RestApiClientInterface $client */
        $instance = new ElectricityMapClient($client);
        $output = $this->callPrivateMethod($instance, 'enableHistorical', 'France');
        $this->assertFalse($output);

        $output = $this->callPrivateMethod($instance, 'enableHistorical', 'Brazil');
        $this->assertTrue($output);
    }

    public function testQueryZones()
    {
        $client = $this->createStub(RestApiClientInterface::class);
        $fixture_file = realpath(dirname(__DIR__, 3) . '/fixtures/ElectricityMap/zones.json');
        $response = file_get_contents($fixture_file);
        $client->method('request')->willReturn(json_decode($response, true));
        $instance = new ElectricityMapClient($client);
        $output = $this->callPrivateMethod($instance, 'queryZones');
        $this->assertIsArray($output);
        $this->assertCount(2, $output);
    }

    public function testFetchDay()
    {
        $client = $this->createStub(RestApiClientInterface::class);
        $fixture_file = realpath(dirname(__DIR__, 3) . '/fixtures/ElectricityMap/api-sample.json');
        $response = file_get_contents($fixture_file);
        $client->method('request')->willReturn(json_decode($response, true));

        /** @var RestApiClientInterface $client */
        $data_source = new ElectricityMapClient($client);
        $source = new Source();
        $source->getFromDBByCrit(['name' => $data_source->getSourceName()]);
        $this->assertFalse($source->isNewItem());
        $zone = new Zone();
        $zone->getFromDbByCrit(['name' => 'France']);
        $this->assertFalse($zone->isNewItem());
        $source_zone = $this->createItem(Source_Zone::class, [
            Source::getForeignKeyField() => $source->getID(),
            Zone::getForeignKeyField() => $zone->getID(),
            'code' => 'FR'
        ]);

        $date = new DateTimeImmutable('5 days ago');
        $intensities = $data_source->fetchDay($date, 'France');

        $this->assertIsArray($intensities);
        $this->assertArrayHasKey('source', $intensities);
        $this->assertEquals('ElectricityMap', $intensities['source']);
        $this->assertArrayHasKey('France', $intensities);
        $this->assertIsArray($intensities['France']);
        $this->assertEquals(24, count($intensities['France']));
    }
}
