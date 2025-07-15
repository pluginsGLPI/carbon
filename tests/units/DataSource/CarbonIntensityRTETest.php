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

use GlpiPlugin\Carbon\DataSource\CarbonIntensityRTE;
use GlpiPlugin\Carbon\DataSource\RestApiClientInterface;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use DateTimeImmutable;
use GlpiPlugin\Carbon\CarbonIntensity;

class CarbonIntensityRTETest extends DbTestCase
{
    public function testFetchDay()
    {
        $client = $this->createStub(RestApiClientInterface::class);
        $response = file_get_contents(__DIR__ . '/../../fixtures/RTE/api-sample.json');
        $client->method('request')->willReturn(json_decode($response, true));

        $source = new CarbonIntensityRTE($client);

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
        $response = file_get_contents(__DIR__ . '/../../fixtures/RTE/export-sample.json');
        $client->method('request')->willReturn(json_decode($response, true));

        $source = new CarbonIntensityRTE($client);

        $start = new DateTimeImmutable('2021-03-01');
        $stop  = new DateTimeImmutable('2021-03-27');
        $intensities = $source->fetchRange($start, $stop, '');
        $this->assertIsArray($intensities);
        $this->assertArrayHasKey('source', $intensities);
        $this->assertEquals('RTE', $intensities['source']);
        $this->assertArrayHasKey('France', $intensities);
        $this->assertIsArray($intensities['France']);
        // There are 288 intensities in the sample file
        $this->assertEquals((288 / 4), count($intensities['France']));
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
        $instance = new CarbonIntensityRTE($client);
        $start_date = new DateTimeImmutable('2024-10-08');
        $stop_date = new DateTimeImmutable('2024-10-08');
        $carbon_intensity = new CarbonIntensity();
        $output = $instance->fullDownload('France', $start_date, $stop_date, $carbon_intensity);
        $this->assertEquals(1, $output);
    }
}
