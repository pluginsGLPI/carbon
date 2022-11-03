<?php

include ("../../../inc/includes.php");

use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Carbon\Config;

Session::checkRight("config", UPDATE);

// To be available when plugin in not activated
Plugin::load('carbon');

Html::header("TITRE", $_SERVER['PHP_SELF'], "config", "plugins");
echo __("This is the GLPI Carbon plugin config page", 'carbon');

$current_config = \GlpiPlugin\Carbon\Config::getConfig();
$canedit        = Session::haveRight(Config::$rightname, UPDATE);

TemplateRenderer::getInstance()->display('@carbon/config.html.twig', [
    'can_edit'       => $canedit,
    'current_config' => $current_config
]);

Html::footer();
