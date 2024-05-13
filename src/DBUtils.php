<?php

namespace GlpiPlugin\Carbon;

use ComputerModel;
use Computer;

class DBUtils
{
    public static function getIdByName(string $table, string $name)
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

    public static function getSum(string $table, string $field)
    {
        global $DB;

        $result = $DB->request([
            'SELECT'    => [
                'SUM' => "$field AS total"
            ],
            'FROM'      => $table,
        ]);

        if ($result->numrows() == 1) {
            return $result->current()['total'];
        }

        return false;
    }

    /**
     * Returns sum of a table field grouped by computer model.
     *
     * @return array of:
     *   - mixed  'number': sum for the model
     *   - string 'url': url to redirect when clicking on the slice
     *   - string 'label': name of the computer model
     */
    public static function getSumEmissionsPerModel(array $where = [])
    {
        global $DB;

        $computers_table = Computer::getTable();
        $computermodels_table = ComputerModel::getTable();
        $carbonemissions_table = CarbonEmission::getTable();

        $request = [
            'SELECT'    => [
                ComputerModel::getTableField('id'),
                ComputerModel::getTableField('name'),
                'SUM' => 'emission_per_day AS total_per_model',
                'COUNT' => Computer::getTableField('id') . ' AS nb_computers_per_model',
            ],
            'FROM'      => $computermodels_table,
            'INNER JOIN' => [
                $computers_table => [
                    'FKEY'   => [
                        $computermodels_table  => 'id',
                        $computers_table => 'computermodels_id',
                    ]
                ],
                $carbonemissions_table => [
                    'FKEY'   => [
                        $computers_table  => 'id',
                        $carbonemissions_table => 'computers_id',
                    ]
                ],
            ],
            'GROUPBY' => ComputerModel::getTableField('id'),
        ];
        if (!empty($where)) {
            $request['WHERE'] = $where;
        }
        $result = $DB->request($request);

        $data = [];
        foreach ($result as $row) {
            $data[] = [
                'number' => $row['total_per_model'],
                'url' => ComputerModel::getFormURLWithID($row['id']),
                'label' => $row['name'] . " (" . $row['nb_computers_per_model'] . " computers)",
            ];
        }

        return $data;
    }

    public static function getSumPowerPerModel(array $where = [])
    {
        global $DB;

        $computers_table = Computer::getTable();
        $computermodels_table = ComputerModel::getTable();
        $computertypes_table = ComputerType::getTable();

        $request = [
            'SELECT'    => [
                ComputerModel::getTableField('id'),
                ComputerModel::getTableField('name'),
                'SUM' => ComputerModel::getTableField('power_consumption') . ' AS total_per_model',
                'COUNT' => Computer::getTableField('id') . ' AS nb_computers_per_model',
            ],
            'FROM'      => $computermodels_table,
            'INNER JOIN' => [
                $computers_table => [
                    'FKEY'   => [
                        $computermodels_table  => 'id',
                        $computers_table => 'computermodels_id',
                    ]
                ],
                $computertypes_table => [
                    'FKEY'   => [
                        $computers_table  => 'computertypes_id',
                        $computertypes_table => 'computertypes_id',
                    ]
                ],
            ],
            'GROUPBY' => ComputerModel::getTableField('id'),
        ];
        if (!empty($where)) {
            $request['WHERE'] = $where;
        }
        $result = $DB->request($request);

        $data = [];
        foreach ($result as $row) {
            $data[] = [
                'number' => $row['total_per_model'],
                'url' => ComputerModel::getFormURLWithID($row['id']),
                'label' => $row['name'] . " (" . $row['nb_computers_per_model'] . " computers)",
            ];
        }

        return $data;
    }
}
