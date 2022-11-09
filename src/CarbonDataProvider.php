<?php

namespace GlpiPlugin\Carbon;

use DateTime;

interface CarbonDataProvider
{
    /**
     * Returns current electricity carbon intensity for the specified zone.
     * 
     * @return int the carbon intensity in gCO2/kWh
     */
    public function getCarbonIntensity(string $zone, DateTime $date): int;
}
