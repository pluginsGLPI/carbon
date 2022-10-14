<?php

namespace GlpiPlugin\Carbon;

use CommonDBChild;
use Computer;
use ComputerModel;
use Migration;

class PowerModel extends CommonDBChild {

    public static $itemtype = 'GlpiPlugin\Carbon\PowerModelCategory';
    public static $items_id = 'plugin_carbon_powermodelcategories_id';

    static function getTypeName($nb = 0)
    {
        return \_n("PowerModel", "PowerModels", $nb, 'powermodel');
    }

    static function install(Migration $migration)
    {
        global $DB;

        $table = self::getTable();
        if (!$DB->tableExists($table)) {
            $migration->displayMessage(sprintf(\__("Installing %s"), $table));

            $query = "CREATE TABLE `$table` (
                       `id` INT(11) UNSIGNED NOT NULL auto_increment,
                       `name` VARCHAR(255),
                       `power` FLOAT(24) DEFAULT '0.0',
                       `plugin_carbon_powermodelcategories_id` INT(11) DEFAULT '0',
                        PRIMARY KEY (`id`)
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

    static function updateOrInsert(string $name, float $power, string $category)
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
