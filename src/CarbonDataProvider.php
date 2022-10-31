<?php

namespace GlpiPlugin\Carbon;

interface CarbonDataProvider
{
    /**
     * Returns current electricity carbon intensity for the specified zone.
     * 
     * @return int the carbon intensity in gCO2/kWh
     */
    public function getCarbonIntensity(string $zone): int;
}
