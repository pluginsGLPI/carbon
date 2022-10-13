<?php

namespace GlpiPlugin\Carbon;

use CommonDBRelation;
use Computer;
use ComputerModel;
use Migration;

class PowerModel_ComputerModel extends CommonDBRelation
{
    public static $itemtype_1 = 'GlpiPlugin\Carbon\PowerModel';
    public static $items_id_1 = 'plugin_carbon_powermodels_id';
    static public $checkItem_1_Rights  = self::DONT_CHECK_ITEM_RIGHTS;

    public static $itemtype_2 = 'ComputerModel';
    public static $items_id_2 = 'computermodels_id';
    static public $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;

    static function getTypeName($nb = 0)
    {
        return _n('Associated element', 'Associated elements', $nb);
    }

    static function install(Migration $migration)
    {
        global $DB;

        $table = self::getTable();
        if (!$DB->tableExists($table)) {
            $migration->displayMessage(sprintf(__("Installing %s"), $table));

            $query = "CREATE TABLE `$table` (
                       `id` INT(11) NOT NULL auto_increment,
                       `plugin_carbon_powermodels_id` INT(11) NOT NULL DEFAULT '0',
                       `computermodels_id` INT(11) NOT NULL DEFAULT '0',
                       PRIMARY KEY (`id`),
                       UNIQUE INDEX `unicity` (`plugin_carbon_powermodels_id`, `computermodels_id`)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
            $DB->query($query) or die($DB->error());
        }
    }

    static function uninstall(Migration $migration)
    {
        global $DB;
        $DB->query("DROP TABLE IF EXISTS `" . self::getTable() . "`");

        return true;
    }

    static function updateOrInsert(string $powerModel, string $computerModel)
    {
        global $DB;

        $powerModel_id = DBUtils::getIdByName(PowerModel::getTable(), $powerModel);
        $computerModel_id = DBUtils::getIdByName(ComputerModel::getTable(), $computerModel);
        $params = [
            'plugin_carbon_powermodels_id' => $powerModel_id,
            'computermodels_id' => $computerModel_id,
        ];
        $where = [
            'plugin_carbon_powermodels_id' => $powerModel_id,
            'computermodels_id' => $computerModel_id,
        ];
        $DB->updateOrInsert(self::getTable(), $params, $where);
    }
}
