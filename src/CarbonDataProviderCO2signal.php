<?php

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
