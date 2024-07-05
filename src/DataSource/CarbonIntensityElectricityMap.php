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

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use GlpiPlugin\Carbon\Config;

class CarbonIntensityElectricityMap implements CarbonIntensity
{
    const HISTORY_URL = 'https://api.electricitymap.org/v3/carbon-intensity/history';

    private RestApiClientInterface $client;

    public function __construct(RestApiClientInterface $client)
    {
        $this->client = $client;
    }
    private function createRestApiClient(): RestApiClientInterface
    {
        $api_key = Config::getconfig()['electricitymap_api_key'];

        return new RestApiClient(
            [
                'headers' => [
                    'auth-token' => $api_key,
                ],
            ]
        );
    }

    public function fetchCarbonIntensity(): array
    {
        // TODO: get zones from GLPI locations
        $params = [
            'zone' => 'FR',
        ];

        $response = $this->client->request('GET', self::HISTORY_URL, ['query' => $params]);
        if (!$response) {
            return [];
        }

        $intensities = [];
        foreach ($response['history'] as $record) {
            $datetime = DateTime::createFromFormat('Y-m-d\TH:i:s+', $record['datetime'], new DateTimeZone('UTC'));
            if (!$datetime instanceof DateTimeInterface) {
                var_dump(DateTime::getLastErrors());
                continue;
            }
            $intensities[] = [
                'datetime' => $datetime->format('Y-m-d\TH:i:sP'),
                'intensity' => $record['carbonIntensity'],
            ];
        }

        return [
            'source' => 'ElectricityMap',
            $response['zone'] => $intensities,
        ];
        ;
    }
}
