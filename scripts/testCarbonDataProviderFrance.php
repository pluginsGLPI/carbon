<?php

include_once('../vendor/autoload.php');
include_once('../src/CarbonDataProvider.php');
include_once('../src/CarbonDataProviderFrance.php');

$provider = new GlpiPlugin\Carbon\CarbonDataProviderFrance();
$params = [
    'select'    => 'taux_co2,date_heure',
    'where'     => 'date_heure IN [date\'2022-10-31T09:00:00+00:00\' TO date\'2022-10-31T10:00:00+00:00\']',
    'order_by'  => 'date_heure desc',
    'limit'     => 20,
    'offset'    => 0,
    'timezone'  => 'UTC',
];

print_r($provider->request('GET', '', $params));
