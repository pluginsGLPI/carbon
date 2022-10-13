<?php

namespace GlpiPlugin\Carbon;

class DBUtils
{
    static function getIdByName(string $table, string $name)
    {
        global $DB;

        $result = $DB->request(
            [
                'FROM' => $table,
                'WHERE' => [
                    'name' => $name,
                ],
            ],
            '',
            true
        );

        if ($result->numrows() == 1) {
            return $result->current()['id'];
        }

        return false;
    }
}
