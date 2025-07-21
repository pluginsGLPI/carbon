<?php

/**
 * -------------------------------------------------------------------------
 * Carbon plugin for GLPI
 *
 * @copyright Copyright (C) 2024-2025 Teclib' and contributors.
 * @license   https://www.gnu.org/licenses/gpl-3.0.txt GPLv3+
 * @link      https://github.com/pluginsGLPI/carbon
 *
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Carbon plugin for GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * -------------------------------------------------------------------------
 */

include("../../../inc/includes.php");

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
        $carbon_intensity = $provider->getCarbonIntensity($zone, '', '', $date);
        fputcsv($f, [$zone, $date->format('Y-m-d H:i:s'), $carbon_intensity]);
    }
    $date->add($step);
}

fclose($f);
