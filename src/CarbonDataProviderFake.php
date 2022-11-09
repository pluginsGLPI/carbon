<?php

namespace GlpiPlugin\Carbon;

use DateTimeInterface;

class CarbonDataProviderFake implements CarbonDataProvider
{
    public function getCarbonIntensity(string $zone, DateTimeInterface $date): int
    {
        return mt_rand(53, 116);
    }
}
