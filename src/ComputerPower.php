<?php

namespace GlpiPlugin\Carbon;

use Computer;
use ComputerModel;
use ComputerType as GlpiComputerType;
use QueryExpression;

class ComputerPower
{
    public static function getTypeName($nb = 0)
    {
        return _n("Computer power", "Computers power", $nb, 'power');
    }

    public static function getPower(int $computer_id): int
    {
        global $DB;

        $computertypes_table = ComputerType::getTable();
        $computermodels_table = ComputerModel::getTable();
        $computers_table = Computer::getTable();
        $computermodels_table = ComputerModel::getTable();

        $request = [
            'SELECT'    => [
                Computer::getTableField('id') . ' AS computer_id',
                new QueryExpression('COALESCE('
                    . ComputerModel::getTableField('power_consumption') . ', ' . ComputerType::getTableField('power_consumption') . ', 0) AS power_consumption'),
            ],
            'FROM'      => $computertypes_table,
            'LEFT JOIN' => [
                $computers_table => [
                    'FKEY'   => [
                        $computertypes_table  => 'computertypes_id',
                        $computers_table => 'computertypes_id',
                    ]
                ],
                $computermodels_table => [
                    'FKEY'   => [
                        $computers_table => 'computermodels_id',
                        $computermodels_table  => 'id',
                    ]
                ]
            ],
            'WHERE' => [
                Computer::getTableField('id') => $computer_id,
            ],
        ];
        $result = $DB->request($request);

        if ($result->numrows() == 1) {
            $power = $result->current()['power_consumption'];
            return $power;
        }

        return 0;
    }

    public static function computePowerForComputer(int $computer_id)
    {
        global $DB;

        $computers_table = Computer::getTable();
        $computermodels_table = ComputerModel::getTable();
        $powermodels_computermodels_table = PowerModel_ComputerModel::getTable();
        $powermodels_table = PowerModel::getTable();

        $request = [
            'SELECT'    => [
                Computer::getTableField('id') . ' AS computer_id',
                PowerModel::getTableField('power'),
                //                ComputerModel::getTableField('name') . ' AS computermodel_name',
                //                PowerModel::getTableField('name') . 'AS powermodel_name',
            ],
            'FROM'      => $computers_table,
            'INNER JOIN' => [
                $computermodels_table => [
                    'FKEY'   => [
                        $computermodels_table  => 'id',
                        $computers_table => 'computermodels_id',
                    ]
                ],
                $powermodels_computermodels_table => [
                    'FKEY'   => [
                        $computermodels_table  => 'id',
                        $powermodels_computermodels_table => 'computermodels_id',
                    ]
                ],
                $powermodels_table => [
                    'FKEY'   => [
                        $powermodels_computermodels_table  => 'plugin_carbon_powermodels_id',
                        $powermodels_table => 'id',
                    ]
                ]
            ],
            'WHERE' => [
                Computer::getTableField('id') => $computer_id,
            ],
        ];
        $result = $DB->request($request);

        if ($result->numrows() == 1) {
            $params = [
                'computers_id' => $computer_id,
                'power' => $result->current()['power'],
            ];
            $where = [
                'computers_id' => $computer_id,
            ];
            return $DB->updateOrInsert(self::getTable(), $params, $where);
        }

        return false;
    }

    public static function computePowerForAllComputers()
    {
        global $DB;

        $computers_table = Computer::getTable();

        $request = [
            'SELECT'    => [
                Computer::getTableField('id') . ' AS computer_id',
            ],
            'FROM'      => $computers_table,
        ];
        $result = $DB->request($request);

        $computers_count = $result->numrows();
        foreach ($result as $computer) {
            self::computePowerForComputer($computer['computer_id']);
        }

        return $computers_count;
    }

    public static function cronInfo($name)
    {
        switch ($name) {
            case 'ComputePowersTask':
                return [
                    'description' => __('Compute powers for all computers', 'carbon')
                ];
        }
        return [];
    }

    public static function cronComputePowersTask($task)
    {
        $task->log("Computing powers for all computers");

        $computers_count = self::computePowerForAllComputers();

        $task->setVolume($computers_count);

        return 1;
    }
}
