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

use DateTime;
use GlpiPlugin\Carbon\DataSource\CarbonIntensity\RteClient;
use GlpiPlugin\Carbon\DataSource\RestApiClientInterface;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use DateTimeImmutable;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\Zone;

class RteClientTest extends DbTestCase
{
    public function testFetchDay()
    {
        $client = $this->createStub(RestApiClientInterface::class);
        $fixture_file = realpath(dirname(__DIR__, 3) . '/fixtures/RTE/api-sample.json');
        $response = file_get_contents($fixture_file);
        $client->method('request')->willReturn(json_decode($response, true));

        $source = new RteClient($client);

        $date = new DateTimeImmutable('5 days ago');
        $intensities = $source->fetchDay($date, '');

        $this->assertIsArray($intensities);
        $this->assertArrayHasKey('source', $intensities);
        $this->assertEquals('RTE', $intensities['source']);
        $this->assertArrayHasKey('France', $intensities);
        $this->assertIsArray($intensities['France']);
        $this->assertEquals(24, count($intensities['France']));
    }

    public function testFetchRange()
    {
        $client = $this->createStub(RestApiClientInterface::class);
        $response = [];
        $date = new DateTime('2021-03-01 00:00:00');
        $date_increment = new \DateInterval('PT15M'); // 15 minutes interval
        for ($i = 0; $i < 2496; $i++) {
            $response[] = [
                'taux_co2'   => 1,
                'date_heure' => $date->format('Y-m-d\TH:i:sP'),
            ];
            $date->add($date_increment);
        }
        $client->method('request')->willReturn($response);

        $source = new RteClient($client);

        $start = new DateTimeImmutable('2021-03-01');
        $stop  = new DateTimeImmutable('2021-03-27');
        $intensities = $source->fetchRange($start, $stop, '');
        $this->assertIsArray($intensities);
        $this->assertIsArray($intensities);
        // There are 2496 intensities in the sample set
        $this->assertEquals((2496), count($intensities));
    }

    public function testFullDownload()
    {
        $client = $this->createStub(RestApiClientInterface::class);
        $client->method('request')->willReturn([
            [
                'taux_co2'   => 1,
                'date_heure' => '2024-10-08T18:00:00+00:00'
            ],
            [
                'taux_co2'   => 1,
                'date_heure' => '2024-10-08T18:15:00+00:00'
            ],
            [
                'taux_co2'   => 1,
                'date_heure' => '2024-10-08T18:30:00+00:00'
            ],
            [
                'taux_co2'   => 1,
                'date_heure' => '2024-10-08T18:45:00+00:00'
            ],
        ]);
        /** @var RestApiClientInterface $client */
        $instance = new RteClient($client);
        $start_date = new DateTimeImmutable('2024-10-08');
        $stop_date = new DateTimeImmutable('2024-10-08');
        $carbon_intensity = new CarbonIntensity();
        $output = $instance->fullDownload('France', $start_date, $stop_date, $carbon_intensity);
        $this->assertEquals(1, $output);
    }

    public function testIncrementalDownload()
    {
        $zone = new Zone();
        $zone->getFromDBByCrit(['name' => 'France']);
        if ($zone->isNewItem()) {
            $zone = $this->createItem(Zone::class, ['name' => 'France']);
        }
        $intensity = $this->createMock(CarbonIntensity::class);

        // 4 calls to fetchRange [3 days ago; today]
        $intensity->expects($this->exactly(4))->method('save');

        // $instance = $this->getMockBuilder(CarbonIntensityRTE::class)
        //     ->disableOriginalConstructor()
        //     ->getMock();
        // $instance->method('fetchDay')->willReturn(['FR' => []]);
        $client = $this->createStub(RestApiClientInterface::class);
        $client->method('request')->willReturn([
            'results' => [
                [
                    'taux_co2'   => 1,
                    'date_heure' => '2024-10-08T18:00:00+00:00'
                ]
            ],
        ]);
        $instance = new RteClient($client);
        $start_date = new DateTime('3 days ago');
        $start_date->setTime(0, 0, 0);
        $start_date = DateTimeImmutable::createFromMutable($start_date);
        $instance->incrementalDownload('France', $start_date, $intensity);
    }
}
