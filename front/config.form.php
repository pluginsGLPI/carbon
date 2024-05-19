<?php


use Config as GlpiConfig;

include("../../../inc/includes.php");

if (!Plugin::isPluginActive('carbon')) {
    Html::displayNotFoundError();
}

// Requires URL encoded backslashes or Html::redirect will double them
Html::redirect(GlpiConfig::getFormURL() . '?forcetab=GlpiPlugin%5CCarbon%5CConfig$0');
