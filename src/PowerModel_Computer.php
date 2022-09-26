<?php

namespace GlpiPlugin\Carbon;

use CommonDBRelation;
use Computer;
use ComputerModel;
use Migration;

class PowerModel_Computer extends CommonDBRelation
{
    public static $itemtype_1 = 'Computer';
    public static $items_id_1 = 'computers_id';
    static public $checkItem_1_Rights  = self::DONT_CHECK_ITEM_RIGHTS;

    public static $itemtype_2 = 'GlpiPlugin\Carbon\PowerModel';
    public static $items_id_2 = 'plugin_carbon_powermodels_id';
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
                       `computers_id` INT(11) NOT NULL DEFAULT '0',
                       PRIMARY KEY (`id`),
                       UNIQUE INDEX `unicity` (`plugin_carbon_powermodels_id`, `computers_id`)
                    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
            $DB->query($query) or die($DB->error());
        }
    }

    static function uninstall()
    {
        global $DB;
        $DB->query("DROP TABLE IF EXISTS `" . self::getTable() . "`");

        return true;
    }
}
