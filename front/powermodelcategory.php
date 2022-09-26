<?php

use GlpiPlugin\Carbon\PowerModelCategory;

include ('../../../inc/includes.php');

Plugin::load('carbon', true);

$dropdown = new PowerModelCategory();
include (GLPI_ROOT . "/front/dropdown.common.php");
