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
