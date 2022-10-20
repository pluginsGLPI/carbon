<?php

namespace GlpiPlugin\Carbon;

use CommonDBChild;
use Computer;
use ComputerModel;
use Migration;

class Power extends CommonDBChild
{
    public static $itemtype = 'Computer';
    public static $items_id = 'computers_id';

    static function getTypeName($nb = 0)
    {
        return \_n("Power", "Powers", $nb, 'power');
    }

    static function getPower(int $computer_id): int
    {
        global $DB;

        $powers_table = Power::getTable();
        $computers_table = Computer::getTable();

        $request = [
            'SELECT'    => [
                Computer::getTableField('id') . ' AS computer_id',
                Power::getTableField('power') . ' AS power',
            ],
            'FROM'      => $powers_table,
            'INNER JOIN' => [
                $computers_table => [
                    'FKEY'   => [
                        $powers_table  => 'computers_id',
                        $computers_table => 'id',
                    ]
                ],
            ],
            'WHERE' => [
                Computer::getTableField('id') => $computer_id,
            ],
        ];
        $result = $DB->request($request);

        if ($result->numrows() == 1) {
            $power = $result->current()['power'];
            return $power;
        }
        
        return 0;
    }

    static function computerPowerForComputer(int $computer_id)
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

    static function computerPowerForAllComputers() 
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

        foreach ($result as $computer) {
            self::computerPowerForComputer($computer['computer_id']);
        }
    }

    static function install(Migration $migration)
    {
        global $DB;

        $table = self::getTable();
        if (!$DB->tableExists($table)) {
            $migration->displayMessage(sprintf(\__("Installing %s"), $table));

            $query = "CREATE TABLE `$table` (
                       `id` INT(11) UNSIGNED NOT NULL auto_increment,
                       `computers_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
                       `power` INT(11) DEFAULT '0',
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
}
