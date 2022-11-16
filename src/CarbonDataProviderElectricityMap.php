<?php

namespace GlpiPlugin\Carbon;

use DateTime;
use DateTimeInterface;
use GlpiPlugin\Carbon\Config;

class CarbonDataProviderElectricityMap extends CarbonDataProviderRestApi
{
    function __construct()
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
                // 'debug'           => true,
            ]
        );
    }

    public function getCarbonIntensity(string $zone, DateTime $date): int
    {
        $format = DateTimeInterface::ISO8601;
        
        $params = [
            'datetime' => $date->format($format),
            'zone'  => $zone,
        ];

        $carbon_intensity = 0;

        if ($response = $this->request('GET', 'carbon-intensity/history', ['query' => $params])) {
            print_r($response);
            $history = $response['history'];
            if (is_array($history) && count($history) > 0) {
                $carbon_intensity = $history[0]['carbonIntensity'];
            }
        }

        return $carbon_intensity;
    }
}
