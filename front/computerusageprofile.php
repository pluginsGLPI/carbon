<?php

use GlpiPlugin\Carbon\ComputerUsageProfile;

include ('../../../inc/includes.php');

// Check if plugin is activated
if (!Plugin::isPluginActive('carbon')) {
    Html::displayNotFoundError();
}

// Session::checkRight('entity', UPDATE);

$dropdown = new ComputerUsageProfile();
include (GLPI_ROOT . "/front/dropdown.common.php");
