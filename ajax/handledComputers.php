<?php

use GlpiPlugin\Carbon\Dashboard\Dashboard;

include('../../../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");

//$res = Dashboard::getHandledComputersCount();
$res = 5000;
echo json_encode($res);
