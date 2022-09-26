<?php

namespace GlpiPlugin\Carbon;

use CommonDBTM;
use Computer;
use ComputerModel;
use Migration;

class PowerModel extends CommonDBTM {

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
                       `id` INT(11) NOT NULL auto_increment,
                       `name` VARCHAR(255),
                       `power` FLOAT(24) DEFAULT '0.0',
                       PRIMARY KEY (`id`)
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
