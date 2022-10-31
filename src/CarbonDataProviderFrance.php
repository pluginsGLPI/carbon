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
    const API_HTTP_VERSION = '2.0';

    protected $api_client = null;
    protected $last_error = '';

    function __construct()
    {
        $api_params = [
            'base_uri'        => self::BASE_URL,
            'timeout'         => self::API_TIMEOUT,
            'connect_timeout' => self::API_TIMEOUT,
            'headers'         => self::API_HEADERS,
            'version'         => self::API_HTTP_VERSION,
            // This is insecure and not recommanded, but...
            'verify'          => false,
            'debug'           => true,
        ];

        $this->api_client = new \GuzzleHttp\Client($api_params);
    }


    function request(string $method = 'GET', string $uri = '', array $params = [], array $options = [])
    {
        $options['query'] = $params;

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

            if ($_SESSION['glpi_use_mode'] == \Session::DEBUG_MODE || isCommandLine()) {
                \Toolbox::logDebug($this->last_error);
            }

            return false;
        }

        $array_response = json_decode($response->getBody(), true);

        return $array_response;
    }

    public function getCarbonIntensity(string $zone): int
    {
        $format = "Y-m-d\TH:i:sP";
        $now = new \DateTimeImmutable();

        // "Données éCO2mix nationales temps réel" has a depth from M-1 to H-2
        $from = $now->sub(new \DateInterval('PT3H'))->format($format);
        $to = $now->sub(new \DateInterval('PT2H'))->format($format);

        $params = [
            'select'    => 'taux_co2,date_heure',
            'where'     => "date_heure IN [date'$from' TO date'$to']",
            'order_by'  => 'date_heure desc',
            'limit'     => 20,
            'offset'    => 0,
            'timezone'  => 'UTC',
        ];

        $carbon_intensity = 0.0;

        if ($response = $this->request('GET', '', $params)) {
            print_r($response);
            foreach ($response['records'] as $record) {
                $carbon_intensity += $record['record']['fields']['taux_co2'];
            }
            $carbon_intensity /= count($response['records']);
        }

        return intval(round($carbon_intensity));
    }
}
