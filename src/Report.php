<?php

namespace GlpiPlugin\Carbon;

use CommonDBTM;
use Glpi\Application\View\TemplateRenderer;
use SebastianBergmann\Type\VoidType;

class Report extends CommonDBTM
{
    public static $rightname = 'carbon:report';

    public static function getTypeName($nb = 0)
    {
        return _n("Carbon report", "Carbon reports", $nb, 'carbon');
    }

    public static function getIcon(): string
    {
        return 'fa-solid fa-solar-panel';
    }

    public static function getMenuContent()
    {
        $menu = [];

        if (self::canView()) {
            $menu = [
                'title' => Report::getTypeName(0),
                'shortcut' => Report::getMenuShorcut(),
                'page' => Report::getSearchURL(false),
                'icon' => Report::getIcon(),
                'lists_itemtype' => Report::getType(),
                'links' => [
                    'search' => Report::getSearchURL(),
                    'lists' => '',
                ]
            ];
        }

        return $menu;
    }

    public function getRights($interface = 'central')
    {
        $values = parent::getRights();

        return array_intersect_key($values, [READ => true]);
    }

    public static function showInstantReport(): void
    {
        TemplateRenderer::getInstance()->display('@carbon/interface.html.twig');
    }
}
