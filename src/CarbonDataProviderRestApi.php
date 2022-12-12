<?php

namespace GlpiPlugin\Carbon;

include_once(__DIR__ . '/../../../vendor/autoload.php');

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;

abstract class CarbonDataProviderRestApi implements CarbonDataProvider
{
    const DEFAULT_TIMEOUT = 5;
    const DEFAULT_HEADERS = [
        'Accept' => 'application/json; charset=utf-8',
    ];
    const DEFAULT_HTTP_VERSION = '2.0';

    protected $api_client = null;
    protected $last_error = '';

    protected function __construct(array $params)
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

    protected function request(string $method = 'GET', string $uri = '', array $options = [])
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
