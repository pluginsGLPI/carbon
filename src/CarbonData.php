<?php

namespace GlpiPlugin\Carbon;

class CarbonData {

    /**
     * Returns carbon data provider instance for the specified zone.
     * 
     * @return string class name of the provider
     */
    public static function getCarbonDataProvider(string $zone): string
    {
        $provider = 'CarbonDataProviderFrance';

        return $provider;
    }

}
