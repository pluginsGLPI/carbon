<?php

namespace GlpiPlugin\Carbon;

use DateInterval;
use DateTimeImmutable;
use DateTime;

class CarbonDataProviderFrance extends CarbonDataProviderRestApi
{
    const BASE_URL = 'https://odre.opendatasoft.com/api/v2/catalog/datasets/eco2mix-national-tr/records';

    function __construct()
    {
        parent::__construct(
            [
                'base_uri'        => self::BASE_URL,
                'debug'           => true,
            ]
        );
    }

    public function getCarbonIntensity(string $country, string $latitude, string $longitude, DateTime &$date): int
    {
        $d = DateTimeImmutable::createFromMutable($date);

        $format = "Y-m-d\TH:i:sP";

        // "Données éCO2mix nationales temps réel" has a depth from M-1 to H-2
        $from = $d->sub(new DateInterval('PT3H'))->format($format);
        $to = $d->sub(new DateInterval('PT2H'))->format($format);

        $params = [
            'select'    => 'taux_co2,date_heure',
            'where'     => "date_heure IN [date'$from' TO date'$to']",
            'order_by'  => 'date_heure desc',
            'limit'     => 20,
            'offset'    => 0,
            'timezone'  => 'UTC',
        ];

        $carbon_intensity = 0.0;

        if ($response = $this->request('GET', '', ['query' => $params])) {
            foreach ($response['records'] as $record) {
                $carbon_intensity += $record['record']['fields']['taux_co2'];
            }
            $carbon_intensity /= count($response['records']);
        }

        return intval(round($carbon_intensity));
    }
}
