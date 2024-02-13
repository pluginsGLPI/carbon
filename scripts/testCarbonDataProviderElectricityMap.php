<?php

include("../../../inc/includes.php");

$provider = new GlpiPlugin\Carbon\CarbonDataProviderElectricityMap();

print_r($provider->getCarbonIntensity('FR', '', '', new DateTime()));
echo "\n";
print_r($provider->getCarbonIntensity('DE', '', '', new DateTime()));
