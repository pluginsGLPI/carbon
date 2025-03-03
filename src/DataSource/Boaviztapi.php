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

use Config;
use Dropdown;

class Boaviztapi
{
    private RestApiClientInterface $client;

    private string $base_url;

    public function __construct(RestApiClientInterface $client)
    {
        $this->client = $client;
        $url = Config::getConfigurationValue('plugin:carbon', 'boaviztapi_base_url');
        if (!is_string($url)) {
            throw new \Exception('Invalid Boaviztapi base URL');
        }
        $this->base_url = $url;
    }

    public function post(string $endpoint, array $options = []): array
    {
        $options['headers'] = [
            'Accept'       => 'application/json',
        ];
        $response = $this->client->request('POST', $this->base_url . '/v1/' . $endpoint, $options);
        if (!$response) {
            return [];
        }

        return $response;
    }

    public function get(string $endpoint, array $options = []): array
    {
        $options['headers'] = [
            'Accept'       => 'application/json',
        ];
        $response = $this->client->request('GET', $this->base_url . '/v1/' . $endpoint, $options);
        if (!$response) {
            return [];
        }

        return $response;
    }

    /**
     * Get zones from Boaviztapi
     * countries or world regions woth a 3 letters code
     *
     * @return array
     */
    public function getZones(): array
    {
        $response = $this->get('utils/country_code');
        ksort($response);
        $response = array_flip($response);
        return $response;
    }

    /**
     * Show a dropdown of zones handleed by Boaviztapi
     */
    public static function dropdownBoaviztaZone(string $name, array $options)
    {
        $boaviztapi = new self(new RestApiClient());
        try {
            $zones = $boaviztapi->getZones();
        } catch (\RuntimeException $e) {
            trigger_error('Error while fetching Boaviztapi zones ' . $e->getMessage(), E_USER_WARNING);
            echo 'Error while fetching Boaviztapi zones';
            return;
        }
        Dropdown::showFromArray($name, $zones, $options);
    }
}
