<?php
namespace GlpiPlugin\Carbon;

use Session;

class Menu
{
    public static function hookRedefineMenu(array $menu): array
    {
        if (Session::getCurrentInterface() != 'central') {
            return $menu;
        }

        $menu['tools']['content'][Report::getType()] = [
            'title' => Report::getTypeName(0),
            'shortcut' => Report::getMenuShorcut(),
            'page' => Report::getSearchURL(),
            'icon' => Report::getIcon(),
            'lists_itemtype' => Report::getType(),
            'links' => [
                'search' => Report::getSearchURL(),
                'lists' => '',
            ]
        ];

        return $menu;
    }
}