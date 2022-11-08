<?php

include("../../../inc/includes.php");
include_once('../src/CarbonDataProvider.php');
include_once('../src/CarbonDataProviderRestApi.php');
include_once('../src/Config.php');
include_once('../src/CarbonDataProviderElectricityMap.php');

$provider = new GlpiPlugin\Carbon\CarbonDataProviderElectricityMap();

print_r($provider->getCarbonIntensity('FR'));
echo "\n";
print_r($provider->getCarbonIntensity('DE'));
