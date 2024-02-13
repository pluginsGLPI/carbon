<?php

namespace GlpiPlugin\Carbon;

use CommonDBChild;
use Computer;
use Location;
use Migration;
use DateTime;

class CarbonEmission extends CommonDBChild
{
    public static $itemtype = 'Computer';
    public static $items_id = 'computers_id';

    public static function getTypeName($nb = 0)
    {
        return \_n("CarbonEmission", "CarbonEmissions", $nb, 'carbon emission');
    }

    public static function computeCarbonEmissionPerDay(int $computer_id, string $country, string $latitude, string $longitude, DateTime &$date)
    {
        global $DB;

        $power = Power::getPower($computer_id);

        $provider = CarbonData::getCarbonDataProvider($country, $latitude, $longitude);

        $carbon_intensity = $provider->getCarbonIntensity($country, $latitude, $longitude, $date);

        if (!$carbon_intensity) {
            return 0;
        }

        // units: power is in Watt, emission is in gCO2/kWh
        $carbon_emission = ((24.0 * (float)$power) / 1000.0) * ((float)$carbon_intensity / 1000.0);

        $params = [
            'computers_id' => $computer_id,
            'emission_per_day' => $carbon_emission,
            'emission_date' => $date->format('Y-m-d H:i:s')
        ];
        // $where = [
        //     'computers_id' => $computer_id,
        // ];

        return $DB->updateOrInsert(self::getTable(), $params);
    }

    public static function computerCarbonEmissionPerDayForAllComputers(DateTime &$date)
    {
        global $DB;

        $computers_table = Computer::getTable();
        $locations_table = Location::getTable();

        $request = [
            'SELECT'    => [
                Computer::getTableField('id') . ' AS computer_id',
                Location::getTableField('country') . ' AS country',
                Location::getTableField('latitude') . ' AS latitude',
                Location::getTableField('longitude') . ' AS longitude',
            ],
            'FROM'      => $computers_table,
            'INNER JOIN' => [
                $locations_table => [
                    'FKEY'   => [
                        $computers_table  => 'locations_id',
                        $locations_table => 'id',
                    ]
                ],
            ],
        ];

        $result = $DB->request($request);
        $count = 0;
        foreach ($result as $r) {
            if (self::computeCarbonEmissionPerDay($r['computer_id'], $r['country'], $r['latitude'], $r['longitude'], $date)) {
                $count++;
            }
        }

        return $count;
    }

    public static function install(Migration $migration)
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

    public static function uninstall(Migration $migration)
    {
        global $DB;

        $DB->query("DROP TABLE IF EXISTS `" . self::getTable() . "`");

        return true;
    }

    public static function cronInfo($name)
    {
        switch ($name) {
            case 'ComputeCarbonEmissionsTask':
                return [
                    'description' => __('Compute carbon emissions for all computers', 'carbon')
                ];
        }
        return [];
    }

    public static function cronComputeCarbonEmissionsTask($task)
    {
        $task->log("Computing carbon emissions for all computers");

        $date = new DateTime();
        $computers_count = self::computerCarbonEmissionPerDayForAllComputers($date);

        $task->setVolume($computers_count);

        return 1;
    }
}
