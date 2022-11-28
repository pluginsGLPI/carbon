<?php

namespace GlpiPlugin\Carbon;

use DateTime;

class CarbonDataProviderFake implements CarbonDataProvider
{
    public function getCarbonIntensity(string $country, string $latitude, string $longitude, DateTime &$date): int
    {
        return mt_rand(53, 116);
    }
}
