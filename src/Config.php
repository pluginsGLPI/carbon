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

    private static $config_entries = [
        'electricitymap_api_key'              => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
        'electricitymap_base_url'             => 'https://api.electricitymap.org/ZZZZZZZZZZZZZZv4/',
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
