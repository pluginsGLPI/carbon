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

    /**
     * Returns total power per computer model.
     * 
     * @return array of:
     *   - int  'number': total power of the model
     *   - string 'url': url to redirect when clicking on the slice
     *   - string 'label': name of the computer model
     */
    static function getPowerPerModel()
    {
        global $DB;

        $computers_table = Computer::getTable();
        $computermodels_table = ComputerModel::getTable();

        /*  SQL:
            SELECT
                glpi_computermodels.name,
                SUM(
                    glpi_computermodels.power_consumption
                ),
                COUNT(glpi_computers.id)
            FROM
                glpi_computermodels
            LEFT JOIN glpi_computers ON glpi_computermodels.id = glpi_computers.computermodels_id
            WHERE
                glpi_computermodels.power_consumption <> 0
            GROUP BY
                glpi_computermodels.id;
        */
        $result = $DB->request([
            'SELECT'    => [
                $computermodels_table . '.name',
                'SUM' => 'power_consumption AS power_consumption_per_model',
                'COUNT' => $computers_table . '.id',
            ],
            'FROM'      => $computermodels_table,
            'INNER JOIN' => [
                $computers_table => [
                    'FKEY'   => [
                        $computermodels_table  => 'id',
                        $computers_table => 'computermodels_id',
                    ]
                ]
            ],
            'WHERE' => [
                'power_consumption' => ['<>', '0'],
            ],
            'GROUPBY' => $computermodels_table . '.id',
        ]);

        $data = [];
        foreach ($result as $id => $row) {
            $data[] = [
                'number' => $row['power_consumption_per_model'],
                'url' => '',
                'label' => $row['name'],
            ];
        }

        return $data;
    }
}
