<?php

namespace GlpiPlugin\Carbon;

use DateTime;

class CarbonDataProviderFake implements CarbonDataProvider
{
    public function getCarbonIntensity(string $zone, DateTime $date): int
    {
        return mt_rand(53, 116);
    }
}
