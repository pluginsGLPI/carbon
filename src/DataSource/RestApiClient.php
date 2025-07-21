<?php

/**
 * -------------------------------------------------------------------------
 * Carbon plugin for GLPI
 *
 * @copyright Copyright (C) 2024-2025 Teclib' and contributors.
 * @license   https://www.gnu.org/licenses/gpl-3.0.txt GPLv3+
 * @link      https://github.com/pluginsGLPI/carbon
 *
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Carbon plugin for GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Carbon\DataSource;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use Session;
use Toolbox;

class RestApiClient implements RestApiClientInterface
{
    const DEFAULT_TIMEOUT = 5;
    const DEFAULT_HEADERS = [
        'Accept' => 'application/json; charset=utf-8',
    ];
    const DEFAULT_HTTP_VERSION = '2.0';

    protected $api_client = null;
    protected $last_error = '';

    public function __construct(array $params = [])
    {
        $local_params = [
            'timeout'         => self::DEFAULT_TIMEOUT,
            'connect_timeout' => self::DEFAULT_TIMEOUT,
            'headers'         => self::DEFAULT_HEADERS,
            'version'         => self::DEFAULT_HTTP_VERSION,
            'http_errors'     => false,
            'debug'           => false, // ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE),
            // This is insecure and not recommanded, but...
            // 'verify'          => false,
        ];

        // array_merge_recursive() is used because it merges headers
        $this->api_client = new Client(array_merge_recursive($local_params, $params));
    }

    public function request(string $method = 'GET', string $uri = '', array $options = [])
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

            Toolbox::logDebug($this->last_error);

            return false;
        }

        return json_decode($response->getBody(), true);
    }
}
