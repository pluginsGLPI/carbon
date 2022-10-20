<?php

namespace GlpiPlugin\Carbon;

use CommonDBChild;
use Computer;
use ComputerModel;
use Migration;

class CarbonEmission extends CommonDBChild
{
    public static $itemtype = 'Computer';
    public static $items_id = 'computers_id';

    static function getTypeName($nb = 0)
    {
        return \_n("CarbonEmission", "CarbonEmissions", $nb, 'carbon emission');
    }

    static function computeCarbonEmissionPerDay(int $computer_id)
    {
        global $DB;

        $power = Power::getPower($computer_id);
        $provider = CarbonDataProvider::PROVIDER;

        $carbon_intensity = $provider::getCarbonIntensity('FR');

        // units: power is in Watt, emission is in gCO2/kWh
        $carbon_emission = ((24.0 * (float)$power) / 1000.0) * ((float)$carbon_intensity / 1000.0);

        $params = [
            'computers_id' => $computer_id,
            'emission_per_day' => $carbon_emission,
            'emission_date' => date('Y-m-d H:i:s')
        ];
        $where = [
            'computers_id' => $computer_id,
        ];
        return $DB->updateOrInsert(self::getTable(), $params, $where);
    }

    static function computerCarbonEmissionPerDayForAllComputers() 
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
            self::computeCarbonEmissionPerDay($computer['computer_id']);
        }

        return false;
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
                       `emission_per_day` FLOAT DEFAULT '0.0',
                       `emission_date` DATETIME DEFAULT NULL,
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
