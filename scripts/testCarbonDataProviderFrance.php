<?php

include_once('../vendor/autoload.php');
include_once('../src/CarbonDataProvider.php');
include_once('../src/CarbonDataProviderFrance.php');

$provider = new GlpiPlugin\Carbon\CarbonDataProviderFrance();
$query_params = [
    'select'    => 'taux_co2,date_heure',
    'where'     => 'date_heure IN [date\'2021-10-24T14:00:00+00:00\' TO date\'2021-10-24T15:00:00+00:00\']',
    'order_by'  => 'date_heure desc',
    'limit'     => 20,
    'offset'    => 0,
    'timezone'  => 'UTC'
];

print_r($provider->httpQuery('', $query_params));
