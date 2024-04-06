<?php
namespace GlpiPlugin\Carbon;

use CommonDBTM;

class Report extends CommonDBTM
{
    public static function getTypeName($nb = 0)
    {
        return _n("Carbon report", "Carbon reports", $nb, 'carbon');
    }

    public static function getIcon(): string
    {
        return 'fa-solid fa-solar-panel';
    }
}