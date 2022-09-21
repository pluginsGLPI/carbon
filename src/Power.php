<?php

namespace GlpiPlugin\Carbon;

use Computer;
use ComputerModel;

class Power
{
    static function getTotalPower()
    {
        global $DB;

        $computers_table = Computer::getTable();
        $computermodels_table = ComputerModel::getTable();

        // select sum(glpi_computermodels.power_consumption),glpi_computers.name,glpi_computers.id from glpi_computermodels inner join glpi_computers on glpi_computermodels.id = glpi_computers.computermodels_id; 
        // "SELECT SUM(`power_consumption`) AS `total_power_consumption` FROM `glpi_computermodels` INNER JOIN `glpi_computers` ON (`glpi_computermodels`.`id` = `glpi_computers`.`computermodels_id`)"
        $result = $DB->request([
            'SELECT'    => [
                'SUM' => 'power_consumption AS total_power_consumption'
            ],
            'FROM'      => $computermodels_table,
            'INNER JOIN' => [
                $computers_table => [
                    'FKEY'   => [
                        $computermodels_table  => 'id',
                        $computers_table => 'computermodels_id',
                    ]
                ]
            ]
        ]);
        if ($row = $result->current()) {
            $total_power_consumption = $row['total_power_consumption'];
            return $total_power_consumption;
        }

        return 42;
    }
}
