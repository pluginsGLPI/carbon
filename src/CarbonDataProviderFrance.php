<?php

namespace GlpiPlugin\Carbon;

include('../vendor/autoload.php');

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;

class CarbonDataProviderFrance implements CarbonDataProvider
{
    const BASE_URL = 'https://odre.opendatasoft.com/api/v2/catalog/datasets/eco2mix-national-tr/records';
    const API_TIMEOUT = 5;
    const API_HEADERS = [
        'Accept' => 'application/json; charset=utf-8',
    ];

    protected $api_client = null;
    protected $last_error = '';

    function __construct()
    {
        $api_params = [
            'base_uri'        => self::BASE_URL,
            'timeout'         => self::API_TIMEOUT,
            'connect_timeout' => self::API_TIMEOUT,
            'headers'         => self::API_HEADERS,
            // This is insecure and not recommanded, but...
            'verify'          => false
            //'debug'           => true
        ];

        $this->api_client = new \GuzzleHttp\Client($api_params);
    }


    function httpQuery(string $endpoint = '', array $params = [], string $method = 'GET')
    {
        try {
            $response = $this->api_client->request($method, $endpoint, $params);
        } catch (RequestException $e) {
            $this->last_error = [
                'title'     => "Plugins API error",
                'exception' => $e->getMessage(),
                'request'   => Psr7\Message::toString($e->getRequest()),
            ];
            if ($e->hasResponse()) {
                $this->last_error['response'] = Psr7\Message::toString($e->getResponse());
            }

            if (
                $_SESSION['glpi_use_mode'] == \Session::DEBUG_MODE
                || isCommandLine()
            ) {
                \Toolbox::logDebug($this->last_error);
            }
            return false;
        }

        $array_response = json_decode($response->getBody(), true);

        return $array_response;
    }

    // curl -X 'GET' \
    // 'https://odre.opendatasoft.com/api/v2/catalog/datasets/eco2mix-national-tr/records?select=taux_co2%2Cdate_heure&where=date_heure%20IN%20%5Bdate%272022-10-24T14%3A00%3A00%2B00%3A00%27%20TO%20date%272022-10-24T15%3A00%3A00%2B00%3A00%27%5D&order_by=date_heure%20desc&limit=20&offset=0&timezone=UTC' \
    // -H 'accept: application/json; charset=utf-8'
  
    public static function getCarbonIntensity(string $zone): int
    {
        $query_params = [
            'select'    => 'taux_co2,date_heure',
            'where'     => 'date_heure IN [date\'2021-10-24T14:00:00+00:00\' TO date\'2021-10-24T15:00:00+00:00\']',
            'order_by'  => 'date_heure desc',
            'limit'     => 20,
            'offset'    => 0,
            'timezone'  => 'UTC'
        ];

        return 53;
    }
}
