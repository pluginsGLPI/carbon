<?php

include('../../../vendor/autoload.php');
include('../src/CarbonDataProvider.php');
include('../src/CarbonDataProviderRestApi.php');
include('../src/CarbonDataProviderFrance.php');

$provider = new GlpiPlugin\Carbon\CarbonDataProviderFrance();

print_r($provider->getCarbonIntensity('FR', new DateTime()));
