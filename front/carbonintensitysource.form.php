<?php

use GlpiPlugin\Carbon\CarbonIntensitySource;

include ('../../../inc/includes.php');

// Check if plugin is activated...
if (!Plugin::isPluginActive('carbon')) {
    Html::displayNotFoundError();
}

$dropdown = new CarbonIntensitySource();
include (GLPI_ROOT . "/front/dropdown.common.form.php");
