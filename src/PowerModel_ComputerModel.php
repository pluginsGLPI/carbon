<?php

namespace GlpiPlugin\Carbon;

use CommonDBRelation;
use ComputerModel;
use Migration;

class PowerModel_ComputerModel extends CommonDBRelation
{
    public static $itemtype_1 = 'GlpiPlugin\Carbon\PowerModel';
    public static $items_id_1 = 'plugin_carbon_powermodels_id';
    public static $checkItem_1_Rights  = self::DONT_CHECK_ITEM_RIGHTS;

    public static $itemtype_2 = 'ComputerModel';
    public static $items_id_2 = 'computermodels_id';
    public static $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;

    public static function getTypeName($nb = 0)
    {
        return _n('Associated element', 'Associated elements', $nb);
    }

    public static function updateOrInsert(string $powerModel, string $computerModel)
    {
        global $DB;

        if (!($powerModel_id = DBUtils::getIdByName(PowerModel::getTable(), $powerModel))) {
            return false;
        }
        if (!($computerModel_id = DBUtils::getIdByName(ComputerModel::getTable(), $computerModel))) {
            return false;
        }

        $params = [
            'plugin_carbon_powermodels_id' => $powerModel_id,
            'computermodels_id' => $computerModel_id,
        ];
        $where = [
            'plugin_carbon_powermodels_id' => $powerModel_id,
            'computermodels_id' => $computerModel_id,
        ];
        return $DB->updateOrInsert(self::getTable(), $params, $where);
    }
}
