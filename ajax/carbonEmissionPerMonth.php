<?php

use GlpiPlugin\Carbon\Dashboard\Dashboard;

include('../../../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");

$res = Dashboard::getCarbonEmissionPerMonth();

// Generate fake data

$date = new DateTime();

$labels = [];
for ($i = 0; $i < 12; $i++) {
    $date->modify('-1 month');
    $labels[] = $date->format('F-Y');
}

$data = [];
for ($i = 0; $i < 12; $i++) {
    $data[] = number_format((float)rand(1001, 2999) / 1000, 3);
}

$chartData = [
    'labels' => array_reverse($labels),
    'series' => [
        [
            'name' => 'Carbon Emission',
            'data' => array_reverse($data)
        ]
    ]
];

Toolbox::logDebug($chartData);
echo json_encode($chartData);
