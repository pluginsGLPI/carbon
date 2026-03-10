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

namespace GlpiPlugin\Carbon\DataSource\CarbonIntensity\ElectricityMaps;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use GlpiPlugin\Carbon\DataSource\RestApiClientInterface;
use GlpiPlugin\Carbon\Source;
use GlpiPlugin\Carbon\Source_Zone;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use GlpiPlugin\Carbon\Zone;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Client::class)]
class ClientTest extends DbTestCase
{
    public function testQueryZones()
    {
        $client = $this->createStub(RestApiClientInterface::class);
        $fixture_file = TU_FIXTURE_PATH . '/ElectricityMap/zones.json';
        $response = file_get_contents($fixture_file);
        $client->method('request')->willReturn(json_decode($response, true));
        $instance = new Client($client);
        $output = $this->callPrivateMethod($instance, 'queryZones');
        $this->assertIsArray($output);
        $this->assertCount(2, $output);
    }

    public function testFetchDay()
    {
        $client = $this->createStub(RestApiClientInterface::class);
        $fixture_file = TU_FIXTURE_PATH . '/ElectricityMap/api-sample.json';
        $response = file_get_contents($fixture_file);
        $client->method('request')->willReturn(json_decode($response, true));

        /** @var RestApiClientInterface $client */
        $data_source = new Client($client);
        $source = new Source();
        $source->getFromDBByCrit(['name' => $data_source->getSourceName()]);
        $this->assertFalse($source->isNewItem());
        $zone = new Zone();
        $zone->getFromDbByCrit(['name' => 'France']);
        $this->assertFalse($zone->isNewItem());
        $source_zone = $this->createItem(Source_Zone::class, [
            Source::getForeignKeyField() => $source->getID(),
            Zone::getForeignKeyField() => $zone->getID(),
            'code' => 'FR',
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

    public function testFetchRange()
    {
        // Prepare the fake HTTP response
        $client = $this->createStub(RestApiClientInterface::class);
        $start = DateTime::createFromFormat(DateTimeInterface::ATOM, '2024-04-01T00:00:00Z');
        $stop = DateTime::createFromFormat(DateTimeInterface::ATOM, '2024-05-01T00:00:00Z');
        $responses = [];
        $response = [
            'zone' => 'FR',
            'data' => [],
            'temporalGranularity' => 'hourly',
        ];
        $step = new DateInterval('PT1H');
        // The date boundaries are extended 12 hours before and after
        $current_date = clone $start;
        $current_date->sub(new DateInterval('PT12H'));
        $extended_stop = clone $stop;
        $extended_stop->add(new DateInterval('PT12H'));
        $count = 0;
        while ($current_date < $extended_stop) {
            $response['data'][] = [
                'zone' => 'FR',
                'carbonIntensity'    => 42,
                'datetime'           => $current_date->format('Y-m-d\\TH:i:s.v') . 'Z',
                'updatedAt'          => "2024-09-07T15:32:36.348Z",
                'createdAt'          => "2024-08-08T06:20:31.772Z",
                'emissionFactorType' => "lifecycle",
                'isEstimated'        => false,
                'estimationMethod'   => null,
            ];
            $count++;
            if ($count == 10 * 24) {
                // Electricitymaps returns 10 days (240 samples) at max
                // @see https://app.electricitymaps.com/developer-hub/api/reference#carbon-intensity-past-range
                // The client slices the requets into 7 days
                $responses[] = $response;
                $response['data'] = [];
                $count = 0;
            }
            $current_date->add($step);
        }
        if (count($response['data']) > 0) {
            $responses[] = $response;
        }
        $client->method('request')->willReturn(...$responses);

        /** @var RestApiClientInterface $client */
        $data_source = new Client($client);
        $source = new Source();
        $source->getFromDBByCrit(['name' => $data_source->getSourceName()]);
        $this->assertFalse($source->isNewItem());
        $zone = new Zone();
        $zone->getFromDbByCrit(['name' => 'France']);
        $this->assertFalse($zone->isNewItem());
        $source_zone = $this->createItem(Source_Zone::class, [
            Source::getForeignKeyField() => $source->getID(),
            Zone::getForeignKeyField() => $zone->getID(),
            'code' => 'FR',
        ]);

        $intensities = $data_source->fetchRange(
            DateTimeImmutable::createFromMutable($start),
            DateTimeImmutable::createFromMutable($stop),
            'France'
        );

        $this->assertCount(24 * 30 + 2 * 12, $intensities);
    }
}
