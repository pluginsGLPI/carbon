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

namespace GlpiPlugin\Carbon;

use DateTime;
use GlpiPlugin\Carbon\Config;

class CarbonDataProviderCO2signal extends CarbonDataProviderRestApi
{
    const BASE_URL = 'https://api.co2signal.com/v1/';

    public function __construct()
    {
        $api_key = Config::getconfig()['co2signal_api_key'];

        parent::__construct(
            [
                'base_uri'        => self::BASE_URL,
                'headers'      => [
                    'auth-token' => $api_key,
                ],
            ]
        );
    }

    public function getCarbonIntensity(string $country = "", string $latitude = "", string $longitude = "", DateTime &$date = null): int
    {
        $params = [
            'countryCode'  => $country,
        ];

        $carbon_intensity = 0;

        if ($response = $this->request('GET', 'latest', ['query' => $params])) {
            $carbon_intensity = $response['data']['carbonIntensity'];
        }

        return $carbon_intensity;
    }
}
