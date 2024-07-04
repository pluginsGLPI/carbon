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

namespace GlpiPlugin\Carbon\DataSource;

use DateInterval;
use DateTimeImmutable;
use DateTime;

class CarbonDataSourceRTE implements CarbonDataSource
{
    const RECORDS_URL = 'https://odre.opendatasoft.com/api/explore/v2.1/catalog/datasets/eco2mix-national-tr/records';

    private RestApiClientInterface $client;

    public function __construct(RestApiClientInterface $client)
    {
        $this->client = $client;
    }

    public function getCarbonIntensity(string $country = "", string $latitude = "", string $longitude = "", DateTime &$date = null): int
    {
        $d = DateTimeImmutable::createFromMutable($date);

        $format = "Y-m-d\TH:i:sP";

        // "Données éCO2mix nationales temps réel" has a depth from M-1 to H-2
        $from = $d->sub(new DateInterval('PT3H'))->format($format);
        $to = $d->sub(new DateInterval('PT2H'))->format($format);

        $params = [
            'select'    => 'taux_co2,date_heure',
            'where'     => "date_heure IN [date'$from' TO date'$to']",
            'order_by'  => 'date_heure desc',
            'limit'     => 20,
            'offset'    => 0,
            'timezone'  => 'UTC',
        ];

        $carbon_intensity = 0.0;

        if ($response = $this->client->request('GET', self::RECORDS_URL, ['query' => $params])) {
            foreach ($response['results'] as $record) {
                $carbon_intensity += $record['taux_co2'];
            }
            $carbon_intensity /= count($response['results']);
        }

        return intval(round($carbon_intensity));
    }
}
