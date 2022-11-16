<?php

include("../../../inc/includes.php");
include_once('../src/CarbonDataProvider.php');
include_once('../src/CarbonDataProviderRestApi.php');
include_once('../src/Config.php');
include_once('../src/CarbonDataProviderElectricityMap.php');

$provider = new GlpiPlugin\Carbon\CarbonDataProviderElectricityMap();

$zones = [
    'BE',
    'CH',
    'DE',
    'ES',
    'FR',
    'GB',
    'IT-NO',
    'NL',
];
$headers = [
    'Zone',
    'Date',
    'Carbon intensity',
];

$f = fopen($argv[1], 'w');
if ($f == false) {
    die("Cannot open file {$argv[1]}");
}

fputcsv($f, $headers);

$date = DateTime::createFromFormat(DateTimeInterface::ISO8601, '2022-11-14T12:00:00+00:00');
$end_date = new DateTime();
$step = new DateInterval('P1D');

while ($date < $end_date) {
    foreach ($zones as $zone) {
        $carbon_intensity = $provider->getCarbonIntensity($zone, $date);
        fputcsv($f, [$zone, $date->format('Y-m-d H:i:s'), $carbon_intensity]);
    }
    $date->add($step);
}

fclose($f);
