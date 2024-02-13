<?php

namespace GlpiPlugin\Carbon;

use DateTime;
use DateTimeInterface;
use GlpiPlugin\Carbon\Config;

class CarbonDataProviderElectricityMap extends CarbonDataProviderRestApi
{
    public function __construct()
    {
        $base_url = Config::getconfig()['electricitymap_base_url'];
        if (substr($base_url, -1) != '/') {
            $base_url .= '/';
        }
        $api_key = Config::getconfig()['electricitymap_api_key'];

        parent::__construct(
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

        if ($response = $this->request('GET', 'carbon-intensity/history', ['query' => $params])) {
            $history = $response['history'];
            if (is_array($history) && count($history) > 0) {
                $carbon_intensity = $history[0]['carbonIntensity'];
            }
        }

        return $carbon_intensity;
    }
}
