<?php

use GlpiPlugin\Carbon\Dashboard\Dashboard;

include('../../../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");

$res = Dashboard::getTotalCarbonEmissionPerModel();

// Generate fake data
$fakeRes = [
    'desktop' => number_format((float)rand(1001, 2999) / 1000, 3),
    'tablets' => number_format((float)rand(1001, 2999) / 1000, 3),
    'laptops' => number_format((float)rand(1001, 2999) / 1000, 3),
    'mobiles' => number_format((float)rand(1001, 2999) / 1000, 3),
    'others' => number_format((float)rand(1001, 2999) / 1000, 3)
];

echo json_encode($fakeRes);
