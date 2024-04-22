<?php

namespace GlpiPlugin\Carbon;

use CommonDBTM;
use Glpi\Application\View\TemplateRenderer;
use SebastianBergmann\Type\VoidType;

class Report extends CommonDBTM
{
    public static function getTypeName($nb = 0)
    {
        return _n("Carbon report", "Carbon reports", $nb, 'carbon');
    }

    public static function canView(): bool
    {
        return true;
    }

    public static function getIcon(): string
    {
        return 'fa-solid fa-solar-panel';
    }
    public static function showInstantReport(): void
    {
        TemplateRenderer::getInstance()->display('@carbon/interface.html.twig');
    }
}
