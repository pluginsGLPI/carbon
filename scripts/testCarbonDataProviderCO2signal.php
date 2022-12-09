<?php

include("../../../inc/includes.php");
include_once('../src/CarbonDataProvider.php');
include_once('../src/CarbonDataProviderRestApi.php');
include_once('../src/Config.php');
include_once('../src/CarbonDataProviderCO2signal.php');

$provider = new GlpiPlugin\Carbon\CarbonDataProviderCO2signal();

print_r($provider->getCarbonIntensity('FR', '0', '0', new DateTime()));
echo "\n";
print_r($provider->getCarbonIntensity('DE', '0', '0', new DateTime()));
