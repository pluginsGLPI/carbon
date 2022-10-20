<?php

namespace GlpiPlugin\Carbon;

interface CarbonDataProvider
{
    const PROVIDER = 'CarbonDataProviderStub';

    /**
     * Returns current electricity carbon intensity for the specified zone.
     * 
     * @return int the carbon intensity in gCO2/kWh
     */
    public static function getCarbonIntensity(string $zone): int;
}

class CarbonDataProviderStub implements CarbonDataProvider
{
    public static function getCarbonIntensity(string $zone): int
    {
        return mt_rand(53, 116);
    }
}
