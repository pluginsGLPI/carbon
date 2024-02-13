<?php

namespace GlpiPlugin\Carbon;

use DateTime;

interface CarbonDataProvider
{
    /**
     * Returns current electricity carbon intensity for the specified location and date.
     *
     * @return int the carbon intensity in gCO2/kWh
     */
    public function getCarbonIntensity(string $country = "", string $latitude = "", string $longitude = "", DateTime &$date = null): int;
}
