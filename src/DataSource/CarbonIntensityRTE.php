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
use DateTimeInterface;
use DateTimeZone;
use DateTime;

class CarbonIntensityRTE implements CarbonIntensity
{
    const RECORDS_URL = 'https://odre.opendatasoft.com/api/explore/v2.1/catalog/datasets/eco2mix-national-tr/records';

    private RestApiClientInterface $client;

    public function __construct(RestApiClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Fetch carbon intensities from Opendata Réseaux-Énergies using real-time dataset.
     *
     * See https://odre.opendatasoft.com/explore/dataset/eco2mix-national-tr/api/?disjunctive.nature
     *
     * Dataset has a depth from MONTH-1 to HOUR-2.
     * Note that the HOUR-2 seems to be not fully guaranted.
     *
     * The method fetches the intensities for the day before the current day.
     */
    public function fetchCarbonIntensity(): array
    {
        $today = new DateTime('now', new DateTimeZone('UTC'));
        $today->setTime(0, 0, 0);
        $yesterday = (clone $today)->sub(new DateInterval('P1D'));

        $format = "Y-m-d\TH:i:sP";
        $from = $yesterday->format($format);
        $to = $today->format($format);

        $params = [
            'select' => 'taux_co2,date_heure',
            'where' => "date_heure IN [date'$from' TO date'$to']",
            'order_by' => 'date_heure desc',
            'limit' => 100,
            'offset' => 0,
            'timezone' => 'UTC',
        ];

        $response = $this->client->request('GET', self::RECORDS_URL, ['query' => $params]);
        if (!$response) {
            return [];
        }

        $intensities = [];
        $intensity = 0.0;
        $count = 0;
        $first = true;

        // compute mean value over each hour
        foreach ($response['results'] as $record) {
            $matches = [];
            preg_match("/:([0-9][0-9]):/", $record['date_heure'], $matches);
            if ($matches[1] == '00') {
                if (!$first) {
                    $intensities[] = [
                        'datetime' => $record['date_heure'],
                        'intensity' => intval(round($intensity / $count)),
                    ];
                    $intensity = 0.0;
                    $count = 0;
                }
                $first = false;
            }
            $intensity += $record['taux_co2'];
            $count++;
        }

        return [
            'source' => 'RTE',
            'France' => $intensities,
        ];
    }
}
