<?php

namespace GlpiPlugin\Carbon;

use Config as GlpiConfig;
use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;
use Session;

class Config extends GlpiConfig
{
    public static function getTypeName($nb = 0)
    {
        return plugin_carbon_getFriendlyName();
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        $tabNames = [];
        if (!$withtemplate) {
            if ($item->getType() == GlpiConfig::class) {
                $tabNames[] = self::getTypeName();
            }
        }
        return $tabNames;
    }

    /**
     * Undocumented function
     *
     * @param CommonGLPI $item
     * @param integer $tabnum
     * @param integer $withtemplate
     * @return void
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        /** @var CommonDBTM $item */
        if ($item->getType() == GlpiConfig::class) {
            $config = new self();
            $config->showForm($item->getId());
        }
    }

    public function showForm($ID, $options = [])
    {
        $current_config = GlpiConfig::getConfigurationValues('plugin:carbon');
        $canedit        = Session::haveRight(Config::$rightname, UPDATE);

        TemplateRenderer::getInstance()->display('@carbon/config.html.twig', [
            'can_edit'       => $canedit,
            'current_config' => $current_config,
            'action'         => (isset($options['plugin_config']) ? Config::getFormURL() : GlpiConfig::getFormURL()),
        ]);
    }
}
