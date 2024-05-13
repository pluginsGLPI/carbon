<?php

namespace GlpiPlugin\Carbon;

use CommonDropdown;

class PowerModelCategory extends CommonDropdown
{
    public static function getTypeName($nb = 0)
    {

        return __('Carbon Plugin - Power model categories', 'carbon');
    }

    public static function getIdByNameOrInsert(string $name)
    {
        global $DB;

        $table = self::getTable();
        if ($id = DBUtils::getIdByName($table, $name)) {
            return $id;
        }

        $DB->insertOrDie($table, ['name' => $name]);

        return $DB->insertId();
    }
}
