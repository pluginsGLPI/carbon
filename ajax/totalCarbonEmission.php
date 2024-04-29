<?php

use GlpiPlugin\Carbon\Dashboard;

include('../../../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");

$res = Dashboard::getTotalCarbonEmission();

echo json_encode($res);
