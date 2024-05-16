<?php

use GlpiPlugin\Carbon\PowerModelCategory;

include '../../../inc/includes.php';

// Check if plugin is activated
if (!Plugin::isPluginActive('carbon')) {
    Html::displayNotFoundError();
}

$dropdown = new PowerModelCategory();
include GLPI_ROOT . "/front/dropdown.common.php";
