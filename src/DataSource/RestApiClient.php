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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;

class RestApiClient implements RestApiClientInterface
{
    const DEFAULT_TIMEOUT = 5;
    const DEFAULT_HEADERS = [
        'Accept' => 'application/json; charset=utf-8',
    ];
    const DEFAULT_HTTP_VERSION = '2.0';

    protected $api_client = null;
    protected $last_error = '';

    public function __construct(array $params)
    {
        $local_params = [
            'timeout'         => self::DEFAULT_TIMEOUT,
            'connect_timeout' => self::DEFAULT_TIMEOUT,
            'headers'         => self::DEFAULT_HEADERS,
            'version'         => self::DEFAULT_HTTP_VERSION,
            'http_errors'     => false,
            'debug'           => ($_SESSION['glpi_use_mode'] == \Session::DEBUG_MODE),
            // This is insecure and not recommanded, but...
            // 'verify'          => false,
        ];

        // array_merge_recursive() is used because it merges headers
        $this->api_client = new Client(array_merge_recursive($local_params, $params));
    }

    function request(string $method = 'GET', string $uri = '', array $options = [])
    {
        try {
            $response = $this->api_client->request($method, $uri, $options);
        } catch (RequestException $e) {
            $this->last_error = [
                'title'     => "Plugins API error",
                'exception' => $e->getMessage(),
                'request'   => Psr7\Message::toString($e->getRequest()),
            ];
            if ($e->hasResponse()) {
                $this->last_error['response'] = Psr7\Message::toString($e->getResponse());
            }

            \Toolbox::logDebug($this->last_error);

            return false;
        }

        return json_decode($response->getBody(), true);
    }
}
