<?php

use GlpiPlugin\Carbon\Dashboard\Dashboard;

include('../../../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");

$res = Dashboard::getUnhandledComputersCount();

$fakeres = 167;
echo json_encode($fakeres);
