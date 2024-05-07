<?php

include("../../../inc/includes.php");

use GlpiPlugin\Carbon\ComputerType;

if (!(new Plugin())->isActivated('carbon')) {
    Html::displayNotFoundError();
}

Session::checkRight('config', UPDATE);

$item = new ComputerType();

if (isset($_POST['update'])) {
    // Add a new Form
    Session::checkRight('entity', UPDATE);
    $_POST['_create_empty_section'] = true;
    $item->update($_POST);
    Html::back();
}

Html::back();