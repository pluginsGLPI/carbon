<?php

namespace GlpiPlugin\Carbon;

class CarbonDataProviderFake implements CarbonDataProvider
{
    public function getCarbonIntensity(string $zone): int
    {
        return mt_rand(53, 116);
    }
}
