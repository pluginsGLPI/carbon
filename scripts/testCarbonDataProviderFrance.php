<?php

include_once('../src/CarbonDataProvider.php');
include_once('../src/CarbonDataProviderRestApi.php');
include_once('../src/CarbonDataProviderFrance.php');

$provider = new GlpiPlugin\Carbon\CarbonDataProviderFrance();

print_r($provider->getCarbonIntensity('FR'));
