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
use GlpiPlugin\Carbon\Config;

class CarbonDataSourceElectricityMap implements CarbonDataSource
{
    private RestApiClient $client;

    public function __construct()
    {
        $base_url = Config::getconfig()['electricitymap_base_url'];
        if (substr($base_url, -1) != '/') {
            $base_url .= '/';
        }
        $api_key = Config::getconfig()['electricitymap_api_key'];

        $this->client = new RestApiClient(
            [
                'base_uri'        => $base_url,
                'headers'      => [
                    'X-BLOBR-KEY' => $api_key,
                ],
            ]
        );
    }

    public function getCarbonIntensity(string $country = "", string $latitude = "", string $longitude = "", DateTime &$date = null): int
    {
        $format = DateTimeInterface::ISO8601;

        $params = [
            'datetime' => $date->format($format),
            'zone'  => $country,
        ];

        $carbon_intensity = 0;

        if ($response = $this->client->request('GET', 'carbon-intensity/history', ['query' => $params])) {
            $history = $response['history'];
            if (is_array($history) && count($history) > 0) {
                $carbon_intensity = $history[0]['carbonIntensity'];
            }
        }

        return $carbon_intensity;
    }
}
