<?php

namespace GlpiPlugin\Carbon;

use CommonGLPI;
use Session;
use Glpi\Application\View\TemplateRenderer;
use Migration;

class Config extends \Config
{

    static function getTypeName($nb = 0)
    {
        return __('Carbon', 'carbon');
    }

    static function getConfig()
    {
        return \Config::getConfigurationValues('plugin:carbon');
    }

    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        switch ($item->getType()) {
            case \Config::class:
                return self::createTabEntry(self::getTypeName());
        }
        return '';
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch ($item->getType()) {
            case \Config::class:
                return self::showForConfig($item, $withtemplate);
        }

        return true;
    }

    static function showForConfig(\Config $config, $withtemplate = 0)
    {
        global $CFG_GLPI;

        if (!self::canView()) {
            return false;
        }

        $current_config = self::getConfig();
        $canedit        = Session::haveRight(self::$rightname, UPDATE);

        TemplateRenderer::getInstance()->display('@carbon/config.html.twig', [
            'can_edit'       => $canedit,
            'current_config' => $current_config
        ]);
    }

    private static $config_entries = [
        'electricitymap_api_token'              => 'XXX',
    ];

    static function install(Migration $migration)
    {
        $current_config = self::getConfig();

        foreach (self::$config_entries as $key => $value) {
            if (!isset($current_config[$key])) {
                Config::setConfigurationValues('plugin:carbon', [$key => $value]);
            }
        }
    }

    static function uninstall(Migration $migration)
    {
        $config = new Config();
        $config->deleteByCriteria(['context' => 'plugin:carbon']);

        return true;
    }
}
