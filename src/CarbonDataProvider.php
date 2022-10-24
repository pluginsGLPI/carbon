<?php

namespace GlpiPlugin\Carbon;

interface CarbonDataProvider
{
    const PROVIDER = 'GlpiPlugin\Carbon\CarbonDataProviderFake';

    /**
     * Returns current electricity carbon intensity for the specified zone.
     * 
     * @return int the carbon intensity in gCO2/kWh
     */
    public static function getCarbonIntensity(string $zone): int;
}

