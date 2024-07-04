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
use GlpiPlugin\Carbon\DataSource\CarbonDataSourceRTE;
use GlpiPlugin\Carbon\DataSource\RestApiClientInterface;
use GlpiPlugin\Carbon\Tests\CommonTestCase;

class CarbonDataSourceRTETest extends CommonTestCase
{
    const RESPONSE_1 = '{"total_count": 25, "results": [{"taux_co2": 13, "date_heure": "2024-06-16T20:00:00+00:00"}, {"taux_co2": 13, "date_heure": "2024-06-16T19:45:00+00:00"}, {"taux_co2": 13, "date_heure": "2024-06-16T19:30:00+00:00"}, {"taux_co2": 13, "date_heure": "2024-06-16T19:15:00+00:00"}, {"taux_co2": 13, "date_heure": "2024-06-16T19:00:00+00:00"}, {"taux_co2": 13, "date_heure": "2024-06-16T18:45:00+00:00"}, {"taux_co2": 13, "date_heure": "2024-06-16T18:30:00+00:00"}, {"taux_co2": 13, "date_heure": "2024-06-16T18:15:00+00:00"}, {"taux_co2": 13, "date_heure": "2024-06-16T18:00:00+00:00"}, {"taux_co2": 13, "date_heure": "2024-06-16T17:45:00+00:00"}, {"taux_co2": 13, "date_heure": "2024-06-16T17:30:00+00:00"}, {"taux_co2": 13, "date_heure": "2024-06-16T17:15:00+00:00"}, {"taux_co2": 14, "date_heure": "2024-06-16T17:00:00+00:00"}, {"taux_co2": 14, "date_heure": "2024-06-16T16:45:00+00:00"}, {"taux_co2": 13, "date_heure": "2024-06-16T16:30:00+00:00"}, {"taux_co2": 14, "date_heure": "2024-06-16T16:15:00+00:00"}, {"taux_co2": 15, "date_heure": "2024-06-16T16:00:00+00:00"}, {"taux_co2": 15, "date_heure": "2024-06-16T15:45:00+00:00"}, {"taux_co2": 15, "date_heure": "2024-06-16T15:30:00+00:00"}, {"taux_co2": 15, "date_heure": "2024-06-16T15:15:00+00:00"}, {"taux_co2": 15, "date_heure": "2024-06-16T15:00:00+00:00"}, {"taux_co2": 15, "date_heure": "2024-06-16T14:45:00+00:00"}, {"taux_co2": 15, "date_heure": "2024-06-16T14:30:00+00:00"}, {"taux_co2": 15, "date_heure": "2024-06-16T14:15:00+00:00"}, {"taux_co2": 15, "date_heure": "2024-06-16T14:00:00+00:00"}]}';

    public function testGetCarbonIntensity()
    {
        $client = $this->createStub(RestApiClientInterface::class);
        $client->method('request')->willReturn(json_decode(self::RESPONSE_1, true));

        $source = new CarbonDataSourceRTE($client);

        $date = new DateTime();
        $intensity = $source->getCarbonIntensity('France', '', '', $date);

        $this->assertIsInt($intensity);
        $this->assertTrue(0 < $intensity && $intensity < 100);
    }
}
