<?php

namespace GlpiPlugin\Carbon;

use CommonDropdown;
use Migration;

class PowerModelCategory extends CommonDropdown
{

    static function getTypeName($nb = 0)
    {

        return __('Carbon Plugin - Power model categories', 'carbon');
    }

    static function install(Migration $migration)
    {
        global $DB;

        $table = self::getTable();
        if (!$DB->tableExists("$table")) {
            $query = "CREATE TABLE `$table` (
                       `id` INT(11) NOT NULL auto_increment,
                       `name` VARCHAR(255) default NULL,
                       `comment` TEXT,
                       PRIMARY KEY  (`id`),
                       KEY `name` (`name`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

            $DB->query($query) or die($DB->error());

            $query = "INSERT INTO `$table` (`id`, `name`, `comment`)
                        VALUES  (1, 'Infrastructure', 'Servers...'),
                                (2, 'Users', 'Desktops, laptops...')";

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
