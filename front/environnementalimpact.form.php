<?php

use Glpi\Event;
use GlpiPlugin\Carbon\EnvironnementalImpact;

include ('../../../inc/includes.php');

// Check if plugin is activated...
if (!Plugin::isPluginActive('carbon')) {
    Html::displayNotFoundError();
}

Session::checkRight(EnvironnementalImpact::$rightname, READ);

$computer = new EnvironnementalImpact();

if (isset($_POST['update'])) {
    $computer->check($_POST['id'], UPDATE);
    $computer->update($_POST);
    Event::log(
        $_POST['id'],
        'computers',
        4,
        'inventory',
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION['glpiname'])
    );
    Html::back();
}