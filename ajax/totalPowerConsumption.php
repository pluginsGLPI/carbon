<?php

use GlpiPlugin\Carbon\Dashboard;

include('../../../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");

$res = Dashboard::getTotalPower();

// Generate fake data
$fakeRes = number_format((float)rand(1001, 2999) / 1000, 3) . " kWh";
echo json_encode($fakeRes);
