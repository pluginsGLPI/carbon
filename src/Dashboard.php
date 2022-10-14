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
            'plugin_carbon_card_total_power' => [
                'widgettype'   => ["bigNumber"],
                'label'        => "GLPI Carbon - Total power consumption",
                'provider'     => Dashboard::class . "::cardTotalPowerProvider",
            ],
            'plugin_carbon_card_total_power_per_model' => [
                'widgettype'   => ['pie', 'donut', 'halfpie', 'halfdonut', 'bar', 'hbar'],
                'label'        => "GLPI Carbon - Total power consumption per model",
                'provider'     => Dashboard::class . "::cardTotalPowerPerModelProvider",
            ],
            'plugin_carbon_card_power_per_model' => [
                'widgettype'   => ['pie', 'donut', 'halfpie', 'halfdonut', 'bar', 'hbar'],
                'label'        => "GLPI Carbon - Power consumption per model",
                'provider'     => Dashboard::class . "::cardPowerPerModelProvider",
            ],
        ];

        return array_merge($cards, $new_cards);
    }

    static function cardTotalPowerProvider(array $params = [])
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

    static function cardTotalPowerPerModelProvider(array $params = [])
    {
        $default_params = [
            'label' => "plugin carbon - total power per model",
            'icon'  => "fas fa-computer",
            'color' => '#ea9999',
        ];
        $params = array_merge($default_params, $params);

        return [
            'data' => self::getTotalPowerPerModel(),
            'label'  => $params['label'],
            'icon'  => $params['icon'],
            'color' => $params['color'],
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
    static function getTotalPowerPerModel()
    {
        global $DB;

        $computers_table = Computer::getTable();
        $computermodels_table = ComputerModel::getTable();
        $powers_table = Power::getTable();

        $result = $DB->request([
            'SELECT'    => [
                ComputerModel::getTableField('id'),
                ComputerModel::getTableField('name'),
                'SUM' => Power::getTableField('power') . ' AS power_consumption_per_model',
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
                'label' => $row['name'] . " (" . $row['nb_computers_per_model'] . " computers)",
            ];
        }

        return $data;
    }

    /**
     * Returns power per computer model.
     * 
     * @return array of:
     *   - int  'number': power of the model
     *   - string 'url': url to redirect when clicking on the slice
     *   - string 'label': name of the computer model
     */
    static function getPowerPerModel()
    {
        global $DB;

        $computermodels_table = ComputerModel::getTable();
        $powermodels_table = PowerModel::getTable();
        $powermodels_computermodels_table = PowerModel_ComputerModel::getTable();

        $result = $DB->request([
            'SELECT'    => [
                ComputerModel::getTableField('id'),
                ComputerModel::getTableField('name'),
                PowerModel::getTableField('power'),
            ],
            'FROM'      => $computermodels_table,
            'INNER JOIN' => [
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
                ],
            ],
            'WHERE' => [
                PowerModel::getTableField('power') => ['>', '0'],
            ],
        ]);

        $data = [];
        foreach ($result as $row) {
            $data[] = [
                'number' => $row['power'],
                'url' => '/front/computermodel.form.php?id=' . $row['id'],
                'label' => $row['name'],
            ];
        }

        return $data;
    }

}
