<?php

namespace GlpiPlugin\Carbon;

class CarbonData {

    private static $providers = [
        'France' => 'GlpiPlugin\Carbon\CarbonDataProviderFrance',
        'Germany' => 'GlpiPlugin\Carbon\CarbonDataProviderElectricityMap',
    ];

    /**
     * Returns carbon data provider instance for the specified zone.
     * 
     * @return string class name of the provider
     */
    public static function getCarbonDataProvider(string $country, string $latitude, string $longitude): CarbonDataProvider
    {
        if (array_key_exists($country, self::$providers)) {
            $provider = self::$providers[$country];
            return new $provider();
        }

        // if (is_numeric($latitude) && is_numeric($longitude)) {
        //     return new CarbonDataProviderElectricityMap();
        // }

        return new CarbonDataProviderElectricityMap();
    }

}
