<?php

use GlpiPlugin\Carbon\Report;

include '../../../inc/includes.php';

// Check if plugin is activated
if (!Plugin::isPluginActive('carbon')) {
    Html::displayNotFoundError();
}

if (!Report::canView()) {
    // Will die
    Html::displayRightError();
}

Html::header(
    __('GLPI Carbon', 'carbon'),
    '',
    'tools',
    Report::getType()
);

Report::showInstantReport();

Html::footer();
