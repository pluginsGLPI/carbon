<?php

namespace GlpiPlugin\Carbon;

use Computer;
use ComputerModel;

class Power
{
    /**
     * Returns total power of all computers.
     * 
     * @return int: total power of all computers
     */
    static function getTotalPower()
    {
        global $DB;

        $computers_table = Computer::getTable();
        $computermodels_table = ComputerModel::getTable();

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

        $result = $DB->request([
            'SELECT'    => [
                ComputerModel::getTableField('name'),
                'SUM' => 'power_consumption AS power_consumption_per_model',
                ComputerModel::getTableField('id'),
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
                'power_consumption' => ['>', '0'],
            ],
            'GROUPBY' => ComputerModel::getTableField('id'),
        ]);

        $data = [];
        foreach ($result as $row) {
            $data[] = [
                'number' => $row['power_consumption_per_model'],
                'url' => '/front/computermodel.form.php?id=' . $row['id'],
                'label' => $row['name'],
            ];
        }

        return $data;
    }
}
