<?php

namespace GlpiPlugin\Carbon;

use CommonDBChild;
use Computer;
use ComputerModel;
use Location;
use DateTime;

class CarbonEmission extends CommonDBChild
{
    public static $itemtype = Computer::class;
    public static $items_id = 'computers_id';

    public static function getTypeName($nb = 0)
    {
        return \_n("CarbonEmission", "CarbonEmissions", $nb, 'carbon emission');
    }

    public static function computeCarbonEmissionPerDay(int $computer_id, string $country, string $latitude, string $longitude, DateTime &$date): bool
    {
        $power = ComputerType::getPower($computer_id);
        $model = new ComputerModel();
        if ($model->getFromDbByCrit(['computers_id' => $computer_id])) {
            $power = $model->fields['power_consumption'];
        }
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

        $carbonEmission = new self();
        $success = false;
        if ($carbonEmission->getFromDBByCrit(['computers_id' => $computer_id])) {
            $success = $carbonEmission->update([
                'emission_per_day' => $carbon_emission,
                'emission_date' => $date->format('Y-m-d H:i:s')
            ]);
        } else {
            $carbonEmission->add([
                'computers_id' => $computer_id,
                'emission_per_day' => $carbon_emission,
                'emission_date' => $date->format('Y-m-d H:i:s')
            ]);
            $success = (!$carbonEmission->isNewItem());
        }

        return $success;
    }


    public static function computerCarbonEmissionPerDayForAllComputers(DateTime &$date): int
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
