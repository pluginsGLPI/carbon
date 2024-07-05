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

use DateTime;
use GlpiPlugin\Carbon\DataSource\CarbonIntensityRTE;
use GlpiPlugin\Carbon\DataSource\RestApiClientInterface;
use GlpiPlugin\Carbon\Tests\CommonTestCase;

class CarbonIntensityRTETest extends CommonTestCase
{
    const RESPONSE_1 = '{"total_count": 97, "results": [{"date_heure": "2024-07-03T00:00:00+00:00", "taux_co2": 13}, {"date_heure": "2024-07-03T00:15:00+00:00", "taux_co2": 13}, {"date_heure": "2024-07-03T00:30:00+00:00", "taux_co2": 13}, {"date_heure": "2024-07-03T00:45:00+00:00", "taux_co2": 13}, {"date_heure": "2024-07-03T01:00:00+00:00", "taux_co2": 13}, {"date_heure": "2024-07-03T01:15:00+00:00", "taux_co2": 13}, {"date_heure": "2024-07-03T01:30:00+00:00", "taux_co2": 13}, {"date_heure": "2024-07-03T01:45:00+00:00", "taux_co2": 13}, {"date_heure": "2024-07-03T02:00:00+00:00", "taux_co2": 13}, {"date_heure": "2024-07-03T02:15:00+00:00", "taux_co2": 13}, {"date_heure": "2024-07-03T02:30:00+00:00", "taux_co2": 13}, {"date_heure": "2024-07-03T02:45:00+00:00", "taux_co2": 13}, {"date_heure": "2024-07-03T03:00:00+00:00", "taux_co2": 13}, {"date_heure": "2024-07-03T03:15:00+00:00", "taux_co2": 14}, {"date_heure": "2024-07-03T03:30:00+00:00", "taux_co2": 14}, {"date_heure": "2024-07-03T03:45:00+00:00", "taux_co2": 14}, {"date_heure": "2024-07-03T04:00:00+00:00", "taux_co2": 14}, {"date_heure": "2024-07-03T04:15:00+00:00", "taux_co2": 14}, {"date_heure": "2024-07-03T04:30:00+00:00", "taux_co2": 13}, {"date_heure": "2024-07-03T04:45:00+00:00", "taux_co2": 13}, {"date_heure": "2024-07-03T05:00:00+00:00", "taux_co2": 13}, {"date_heure": "2024-07-03T05:15:00+00:00", "taux_co2": 13}, {"date_heure": "2024-07-03T05:30:00+00:00", "taux_co2": 13}, {"date_heure": "2024-07-03T05:45:00+00:00", "taux_co2": 13}, {"date_heure": "2024-07-03T06:00:00+00:00", "taux_co2": 13}, {"date_heure": "2024-07-03T06:15:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T06:30:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T06:45:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T07:00:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T07:15:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T07:30:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T07:45:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T08:00:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T08:15:00+00:00", "taux_co2": 11}, {"date_heure": "2024-07-03T08:30:00+00:00", "taux_co2": 11}, {"date_heure": "2024-07-03T08:45:00+00:00", "taux_co2": 11}, {"date_heure": "2024-07-03T09:00:00+00:00", "taux_co2": 11}, {"date_heure": "2024-07-03T09:15:00+00:00", "taux_co2": 11}, {"date_heure": "2024-07-03T09:30:00+00:00", "taux_co2": 11}, {"date_heure": "2024-07-03T09:45:00+00:00", "taux_co2": 11}, {"date_heure": "2024-07-03T10:00:00+00:00", "taux_co2": 11}, {"date_heure": "2024-07-03T10:15:00+00:00", "taux_co2": 11}, {"date_heure": "2024-07-03T10:30:00+00:00", "taux_co2": 11}, {"date_heure": "2024-07-03T10:45:00+00:00", "taux_co2": 11}, {"date_heure": "2024-07-03T11:00:00+00:00", "taux_co2": 11}, {"date_heure": "2024-07-03T11:15:00+00:00", "taux_co2": 11}, {"date_heure": "2024-07-03T11:30:00+00:00", "taux_co2": 11}, {"date_heure": "2024-07-03T11:45:00+00:00", "taux_co2": 11}, {"date_heure": "2024-07-03T12:00:00+00:00", "taux_co2": 11}, {"date_heure": "2024-07-03T12:15:00+00:00", "taux_co2": 11}, {"date_heure": "2024-07-03T12:30:00+00:00", "taux_co2": 11}, {"date_heure": "2024-07-03T12:45:00+00:00", "taux_co2": 11}, {"date_heure": "2024-07-03T13:00:00+00:00", "taux_co2": 11}, {"date_heure": "2024-07-03T13:15:00+00:00", "taux_co2": 11}, {"date_heure": "2024-07-03T13:30:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T13:45:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T14:00:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T14:15:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T14:30:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T14:45:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T15:00:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T15:15:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T15:30:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T15:45:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T16:00:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T16:15:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T16:30:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T16:45:00+00:00", "taux_co2": 11}, {"date_heure": "2024-07-03T17:00:00+00:00", "taux_co2": 11}, {"date_heure": "2024-07-03T17:15:00+00:00", "taux_co2": 11}, {"date_heure": "2024-07-03T17:30:00+00:00", "taux_co2": 11}, {"date_heure": "2024-07-03T17:45:00+00:00", "taux_co2": 11}, {"date_heure": "2024-07-03T18:00:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T18:15:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T18:30:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T18:45:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T19:00:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T19:15:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T19:30:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T19:45:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T20:00:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T20:15:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T20:30:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T20:45:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T21:00:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T21:15:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T21:30:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T21:45:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T22:00:00+00:00", "taux_co2": 12}, {"date_heure": "2024-07-03T22:15:00+00:00", "taux_co2": 13}, {"date_heure": "2024-07-03T22:30:00+00:00", "taux_co2": 13}, {"date_heure": "2024-07-03T22:45:00+00:00", "taux_co2": 13}, {"date_heure": "2024-07-03T23:00:00+00:00", "taux_co2": 13}, {"date_heure": "2024-07-03T23:15:00+00:00", "taux_co2": 13}, {"date_heure": "2024-07-03T23:30:00+00:00", "taux_co2": 13}, {"date_heure": "2024-07-03T23:45:00+00:00", "taux_co2": 13}, {"date_heure": "2024-07-04T00:00:00+00:00", "taux_co2": 14}]}';

    public function testFetchCarbonIntensity()
    {
        $client = $this->createStub(RestApiClientInterface::class);
        $client->method('request')->willReturn(json_decode(self::RESPONSE_1, true));

        $source = new CarbonIntensityRTE($client);

        $intensities = $source->fetchCarbonIntensity();

        $this->assertIsArray($intensities);
        $this->assertArrayHasKey('source', $intensities);
        $this->assertEquals('RTE', $intensities['source']);
        $this->assertArrayHasKey('France', $intensities);
        $this->assertIsArray($intensities['France']);
        $this->assertEquals(24, count($intensities['France']));
    }
}
