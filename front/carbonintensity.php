<?php

use GlpiPlugin\Carbon\CarbonIntensity;

include ('../../../inc/includes.php');

// Check if plugin is activated...
if (!Plugin::isPluginActive('carbon')) {
    Html::displayNotFoundError();
}

$carbon_intensity = new CarbonIntensity();

Html::header(
    CarbonIntensity::getTypeName(Session::getPluralNumber()),
    $_SERVER['PHP_SELF'],
    'admin',
    CarbonIntensity::class,
    'option'
);
Search::show(CarbonIntensity::class);

Html::footer();