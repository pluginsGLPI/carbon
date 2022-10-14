<?php

namespace GlpiPlugin\Carbon;

use GlpiPlugin\Carbon\Power;
use ComputerModel;
use Computer;

class Dashboard
{
    static function dashboardCards($cards = [])
    {
        if (is_null($cards)) {
            $cards = [];
        }

        $new_cards = [
            'plugin_carbon_card_global_power' => [
                'widgettype'   => ["bigNumber"],
                'label'        => "GLPI Carbon - Global power consumption",
                'provider'     => Dashboard::class . "::cardGlobalPowerProvider",
            ],
            'plugin_carbon_card_power_per_model' => [
                'widgettype'   => ['pie', 'donut', 'halfpie', 'halfdonut', 'bar', 'hbar'],
                'label'        => "GLPI Carbon - Power consumption per model",
                'provider'     => Dashboard::class . "::cardPowerPerModelProvider",
            ],
        ];

        return array_merge($cards, $new_cards);
    }

    static function cardGlobalPowerProvider(array $params = [])
    {
        $default_params = [
            'label' => "plugin carbon - total power",
            'icon'  => "fas fa-computer",
        ];
        $params = array_merge($default_params, $params);

        return [
            'number' => self::getTotalPower(),
            'label'  => $params['label'],
            'icon'  => $params['icon'],
        ];
    }

    static function cardPowerPerModelProvider(array $params = [])
    {
        $default_params = [
            'label' => "plugin carbon - power per model",
            'icon'  => "fas fa-computer",
            'color' => '#ea9999',
        ];
        $params = array_merge($default_params, $params);

        return [
            'data' => self::getPowerPerModel(),
            'label'  => $params['label'],
            'icon'  => $params['icon'],
            'color' => $params['color'],
        ];
    }
    /**
     * Returns total power of all computers.
     * 
     * @return int: total power of all computers
     */
    static function getTotalPower()
    {
        global $DB;

        $powers_table = Power::getTable();

        $result = $DB->request([
            'SELECT'    => [
                'SUM' => 'power AS total_power_consumption'
            ],
            'FROM'      => $powers_table,
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
        $powers_table = Power::getTable();

        $result = $DB->request([
            'SELECT'    => [
                ComputerModel::getTableField('name'),
                'SUM' => Power::getTableField('power') . ' AS power_consumption_per_model',
                ComputerModel::getTableField('id'),
            ],
            'FROM'      => $computermodels_table,
            'INNER JOIN' => [
                $computers_table => [
                    'FKEY'   => [
                        $computermodels_table  => 'id',
                        $computers_table => 'computermodels_id',
                    ]
                ],
                $powers_table => [
                    'FKEY'   => [
                        $computers_table  => 'id',
                        $powers_table => 'computers_id',
                    ]
                ],
            ],
            'WHERE' => [
                Power::getTableField('power') => ['>', '0'],
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
