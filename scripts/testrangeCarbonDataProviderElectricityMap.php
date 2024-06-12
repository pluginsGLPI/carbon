<?php

/**
 * -------------------------------------------------------------------------
 * carbon plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2024 Teclib' and contributors.
 * @license   MIT https://opensource.org/licenses/mit-license.php
 * @link      https://github.com/pluginsGLPI/carbon
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
