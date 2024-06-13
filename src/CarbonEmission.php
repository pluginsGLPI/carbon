<?php

/**
 * -------------------------------------------------------------------------
 * carbon plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2024 Teclib' and contributors.
 * @license   MIT https://opensource.org/licenses/mit-license.php
 * @link      https://github.com/pluginsGLPI/carbon
 * -------------------------------------------------------------------------
 */

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

    /**
     * @deprecated uses old data model
     */
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

    /**
     * @deprecated uses deprecated method computeCarbonEmissionPerDay
     */
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

    /**
     * @deprecated uses deprecated methods
     */
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

    /**
     * @deprecated uses deprecated methods
     */
    public static function cronComputeCarbonEmissionsTask($task)
    {
        $task->log("Computing carbon emissions for all computers");

        $date = new DateTime();
        $computers_count = self::computerCarbonEmissionPerDayForAllComputers($date);

        $task->setVolume($computers_count);

        return 1;
    }
}
