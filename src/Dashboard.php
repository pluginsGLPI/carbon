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
            'plugin_carbon_card_total_carbon_emission' => [
                'widgettype'   => ["bigNumber"],
                'label'        => "GLPI Carbon - Total carbon emission",
                'provider'     => Dashboard::class . "::cardTotalCarbonEmissionProvider",
            ],
            'plugin_carbon_card_total_power_per_model' => [
                'widgettype'   => ['pie', 'donut', 'halfpie', 'halfdonut', 'bar', 'hbar'],
                'label'        => "GLPI Carbon - Total power consumption per model",
                'provider'     => Dashboard::class . "::cardTotalPowerPerModelProvider",
            ],
            'plugin_carbon_card_total_carbon_emission_per_model' => [
                'widgettype'   => ['pie', 'donut', 'halfpie', 'halfdonut', 'bar', 'hbar'],
                'label'        => "GLPI Carbon - Total carbon emission per model",
                'provider'     => Dashboard::class . "::cardTotalCarbonEmissionPerModelProvider",
            ],
            'plugin_carbon_card_power_per_model' => [
                'widgettype'   => ['pie', 'donut', 'halfpie', 'halfdonut', 'bar', 'hbar'],
                'label'        => "GLPI Carbon - Power consumption per model",
                'provider'     => Dashboard::class . "::cardPowerPerModelProvider",
            ],
        ];

        return array_merge($cards, $new_cards);
    }

    static function cardNumberProvider(array $params = [], string $label, string $number)
    {
        $default_params = [
            'label' => "plugin carbon - $label",
            'icon'  => "fas fa-computer",
        ];
        $params = array_merge($default_params, $params);

        return [
            'number' => $number,
            'label'  => $params['label'],
            'icon'  => $params['icon'],
        ];
    }

    static function cardTotalPowerProvider(array $params = [])
    {
        return self::cardNumberProvider($params, "total power", self::getTotalPower());
    }

    static function cardTotalCarbonEmissionProvider(array $params = [])
    {
        return self::cardNumberProvider($params, "total carbon emission", self::getTotalCarbonEmission());
    }

    static function cardDataProvider(array $params = [], string $label, array $data)
    {
        $default_params = [
            'label' => "plugin carbon - $label",
            'icon'  => "fas fa-computer",
            'color' => '#ea9999',
        ];
        $params = array_merge($default_params, $params);

        return [
            'data' => $data,
            'label'  => $params['label'],
            'icon'  => $params['icon'],
            'color' => $params['color'],
        ];
    }

    static function cardTotalPowerPerModelProvider(array $params = [])
    {
        return self::cardDataProvider($params, "total power per model", self::getTotalPowerPerModel());
    }

    static function cardTotalCarbonEmissionPerModelProvider(array $params = [])
    {
        return self::cardDataProvider($params, "total carbon emission per model", self::getTotalCarbonEmissionPerModel());
    }

    static function cardPowerPerModelProvider(array $params = [])
    {
        return self::cardDataProvider($params, "power per model", self::getPowerPerModel());
    }

    static function getTotalPower()
    {
        if ($total = DBUtils::getSum(Power::getTable(), 'power'))
            return strval($total) . " W";

        return "0 W";
    }

    static function getTotalCarbonEmission()
    {
        if ($total = DBUtils::getSum(CarbonEmission::getTable(), 'emission_per_day'))
            return number_format($total, 2) . " kg CO2";
        
        return "0.00 kg CO2";
    }

    /**
     * Returns total carbon emission per computer model.
     * 
     * @return array of:
     *   - float  'number': total carbon emission of the model
     *   - string 'url': url to redirect when clicking on the slice
     *   - string 'label': name of the computer model
     */
    static function getTotalCarbonEmissionPerModel()
    {
        return DBUtils::getSumPerModel(CarbonEmission::getTable(), CarbonEmission::getTableField('emission_per_day'));
/* 
        global $DB;

        $computers_table = Computer::getTable();
        $computermodels_table = ComputerModel::getTable();
        $carbonemissions_table = CarbonEmission::getTable();

        $result = $DB->request([
            'SELECT'    => [
                ComputerModel::getTableField('id'),
                ComputerModel::getTableField('name'),
                'SUM' => CarbonEmission::getTableField('emission_per_day') . ' AS emission_per_day_per_model',
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
        ]);

        $data = [];
        foreach ($result as $row) {
            $data[] = [
                'number' => $row['emission_per_day_per_model'],
                'url' => '/front/computermodel.form.php?id=' . $row['id'],
                'label' => $row['name'] . " (" . $row['nb_computers_per_model'] . " computers)",
            ];
        }

        return $data;
 */    }

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
        return DBUtils::getSumPerModel(Power::getTable(), Power::getTableField('power'), [Power::getTableField('power') => ['>', '0']]);

/*         global $DB;

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
 */    }

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
