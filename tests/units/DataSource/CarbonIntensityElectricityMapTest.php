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

use GlpiPlugin\Carbon\DataSource\CarbonIntensityElectricityMap;
use GlpiPlugin\Carbon\DataSource\RestApiClientInterface;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\Zone;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use DateTimeImmutable;
use GlpiPlugin\Carbon\CarbonIntensitySource_Zone;

class CarbonIntensityElectricityMapTest extends DbTestCase
{
    public function testEnableHistorical()
    {
        $client = $this->createStub(RestApiClientInterface::class);
        /** @var RestApiClientInterface $client */
        $instance = new CarbonIntensityElectricityMap($client);
        $output = $this->callPrivateMethod($instance, 'enableHistorical', 'France');
        $this->assertFalse($output);

        $output = $this->callPrivateMethod($instance, 'enableHistorical', 'Brazil');
        $this->assertTrue($output);
    }

    public function testQueryZones()
    {
        $client = $this->createStub(RestApiClientInterface::class);
        $response = file_get_contents(__DIR__ . '/../../fixtures/ElectricityMap/zones.json');
        $client->method('request')->willReturn(json_decode($response, true));
        $instance = new CarbonIntensityElectricityMap($client);
        $output = $this->callPrivateMethod($instance, 'queryZones');
        $this->assertIsArray($output);
        $this->assertCount(2, $output);
    }

    public function testFetchDay()
    {
        $client = $this->createStub(RestApiClientInterface::class);
        $response = file_get_contents(__DIR__ . '/../../fixtures/ElectricityMap/api-sample.json');
        $client->method('request')->willReturn(json_decode($response, true));

        /** @var RestApiClientInterface $client */
        $data_source = new CarbonIntensityElectricityMap($client);
        $source = new CarbonIntensitySource();
        $source->getFromDBByCrit(['name' => $data_source->getSourceName()]);
        $this->assertFalse($source->isNewItem());
        $zone = new Zone();
        $zone->getFromDbByCrit(['name' => 'France']);
        $this->assertFalse($zone->isNewItem());
        $source_zone = $this->getItem(CarbonIntensitySource_Zone::class, [
            CarbonIntensitySource::getForeignKeyField() => $source->getID(),
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
