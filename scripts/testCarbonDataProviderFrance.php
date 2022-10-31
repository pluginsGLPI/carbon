<?php

include_once('../vendor/autoload.php');
include_once('../src/CarbonDataProvider.php');
include_once('../src/CarbonDataProviderFrance.php');

$format = "Y-m-d\TH:i:sP";
$now = new DateTimeImmutable();
print_r($now->format($format));
echo  "\n";
$from = $now->sub(new DateInterval('PT2H'))->format($format);
$to = $now->sub(new DateInterval('PT1H'))->format($format);
print_r($from);
echo "\n";
print_r($to);
echo "\n";

$provider = new GlpiPlugin\Carbon\CarbonDataProviderFrance();
$params = [
    'select'    => 'taux_co2,date_heure',
    'where'     => "date_heure IN [date'$from' TO date'$to']",
    'order_by'  => 'date_heure desc',
    'limit'     => 20,
    'offset'    => 0,
    'timezone'  => 'UTC',
];

print_r($provider->request('GET', '', $params));
