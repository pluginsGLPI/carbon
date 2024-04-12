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
    __('Form Creator', 'carbon'),
    '',
    'tools',
    Report::getType()
);

Search::show(Report::getType());

Html::footer();