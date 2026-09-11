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

use Glpi\Toolbox\HttpClient;
use GlpiPlugin\Carbon\Config;
use Override;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Toolbox;

use function Safe\json_decode;

class RestApiClient implements RestApiClientInterface
{
    public const DEFAULT_TIMEOUT = 5;
    public const DEFAULT_HEADERS = [
        'Accept' => 'application/json; charset=utf-8',
    ];
    public const DEFAULT_HTTP_VERSION = '2.0';

    protected ?HttpClient $api_client = null;
    protected array $last_error = [];

    public function __construct(array $params = [])
    {
        $local_params = [
            'timeout'              => self::DEFAULT_TIMEOUT,
            'max_connect_duration' => self::DEFAULT_TIMEOUT,
            'headers'              => self::DEFAULT_HEADERS,
            'http_version'         => self::DEFAULT_HTTP_VERSION,
        ];

        // array_merge_recursive() is used because it merges headers
        $this->api_client = new HttpClient(Config::class, array_merge_recursive($local_params, $params));
    }

    #[Override]
    public function request(string $method = 'GET', string $uri = '', array $options = [])
    {
        $request = $this->api_client;
        try {
            $response = $request->request($method, $uri, $options);
        } catch (RedirectionExceptionInterface|ClientExceptionInterface|ServerExceptionInterface $e) {
            // Exception related to HTTP
            $this->last_error = [
                'title'     => "Plugins API error",
                'exception' => $e->getMessage(),
                'request'   => $method . ' ' . $uri,
            ];
            $this->last_error['response'] = $e->getResponse()->getContent(false);

            Toolbox::logDebug($this->last_error);

            return false;
        } catch (RuntimeException $e) {
            // Other exceptions
            $this->last_error = [
                'title'     => "Plugins API error",
                'exception' => $e->getMessage(),
            ];

            Toolbox::logDebug($this->last_error);

            return false;
        }

        return json_decode($response->getContent(), true);
    }
}
