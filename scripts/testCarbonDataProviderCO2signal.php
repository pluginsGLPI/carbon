<?php

include("../../../inc/includes.php");

$provider = new GlpiPlugin\Carbon\CarbonDataProviderCO2signal();
$now = new DateTime();
print_r($provider->getCarbonIntensity('FR', '0', '0', $now));
echo "\n";
print_r($provider->getCarbonIntensity('DE', '0', '0', $now));
