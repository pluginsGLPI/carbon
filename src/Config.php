<?php

namespace GlpiPlugin\Carbon;

use Config as GlpiConfig;

class Config extends GlpiConfig
{
    public static function getTypeName($nb = 0)
    {
        return __('Carbon', 'carbon');
    }

    public static function getConfig()
    {
        return parent::getConfigurationValues('plugin:carbon');
    }
}
