<?php

use GlpiPlugin\Carbon\Dashboard\Dashboard;

include('../../../inc/includes.php');

// Check if plugin is activated...
if (!Plugin::isPluginActive('carbon')) {
    http_response_code(404);
    die();
}

if (!Report::canView()) {
    // Will die
    http_response_code(403);
    die();
}

$count = Dashboard::getUnhandledComputersCount();
echo json_encode($count);
