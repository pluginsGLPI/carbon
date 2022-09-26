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

    static function install(Migration $migration)
    {
        global $DB;

        $table = self::getTable();
        if (!$DB->tableExists($table)) {
            $migration->displayMessage(sprintf(\__("Installing %s"), $table));

            $query = "CREATE TABLE `$table` (
                       `id` INT(11) NOT NULL auto_increment,
                       `computers_id` INT(11) NOT NULL DEFAULT '0',
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
