<?php

use GlpiPlugin\Carbon\CarbonIntensityZone;

include ('../../../inc/includes.php');

// Check if plugin is activated
if (!Plugin::isPluginActive('carbon')) {
    Html::displayNotFoundError();
}

$dropdown = new CarbonIntensityZone();
include (GLPI_ROOT . "/front/dropdown.common.php");
