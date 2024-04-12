<?php

namespace GlpiPlugin\Carbon;

use CommonDBChild;
use Migration;

class PowerModel extends CommonDBChild
{
    public static $itemtype = 'GlpiPlugin\Carbon\PowerModelCategory';
    public static $items_id = 'plugin_carbon_powermodelcategories_id';

    public static function getTypeName($nb = 0)
    {
        return \_n("PowerModel", "PowerModels", $nb, 'powermodel');
    }

    public static function updateOrInsert(string $name, float $power, string $category)
    {
        global $DB;

        $params = [
            'name' => $name,
            'power' => $power,
            'plugin_carbon_powermodelcategories_id' => PowerModelCategory::getIdByNameOrInsert($category),
        ];
        $where = [
            'name' => $name,
        ];
        $DB->updateOrInsert(self::getTable(), $params, $where);
    }
}
