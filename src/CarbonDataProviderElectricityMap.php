<?php

namespace GlpiPlugin\Carbon;

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

    public function getCarbonIntensity(string $zone): int
    {
        $params = [
            'zone'  => $zone,
        ];

        $carbon_intensity = 0;

        if ($response = $this->request('GET', 'carbon-intensity/latest', ['query' => $params])) {
            $carbon_intensity = $response['carbonIntensity'];
        }

        return $carbon_intensity;
    }
}
