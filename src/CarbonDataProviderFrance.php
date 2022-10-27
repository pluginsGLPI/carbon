<?php

namespace GlpiPlugin\Carbon;
include ('../vendor/autoload.php');
use GuzzleHttp\Client as Guzzle_Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;

define('VIP_API_TIMEOUT', "5");
define('VIP_API_HEADERS', [
   'Accept'=> 'application/json',
]);

class CarbonDataProviderFrance implements CarbonDataProvider
{
    const BASE_URL = 'https://odre.opendatasoft.com/api/v2/catalog/datasets/eco2mix-national-tr/records';

    public static function getCarbonIntensity(string $zone): int
    {
        return 53;
    }
}