<?php

namespace GlpiPlugin\Carbon;

use GlpiPlugin\Carbon\Power;
use ComputerModel;
use Computer;
use DateTime;
use DateInterval;
use DateTimeInterface;

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
                'group'        => __("Carbon", "carbon"),
                'label'        => __("Total power consumption", "carbon"),
                'provider'     => Dashboard::class . "::cardTotalPowerProvider",
            ],
            'plugin_carbon_card_total_carbon_emission' => [
                'widgettype'   => ["bigNumber"],
                'group'        => __("Carbon", "carbon"),
                'label'        => __("Total carbon emission", "carbon"),
                'provider'     => Dashboard::class . "::cardTotalCarbonEmissionProvider",
            ],
            'plugin_carbon_card_total_power_per_model' => [
                'widgettype'   => ['pie', 'donut', 'halfpie', 'halfdonut', 'bar', 'hbar'],
                'group'        => __("Carbon", "carbon"),
                'label'        => __("Total power consumption per model", "carbon"),
                'provider'     => Dashboard::class . "::cardTotalPowerPerModelProvider",
            ],
            'plugin_carbon_card_total_carbon_emission_per_model' => [
                'widgettype'   => ['pie', 'donut', 'halfpie', 'halfdonut', 'bar', 'hbar'],
                'group'        => __("Carbon", "carbon"),
                'label'        => __("Total carbon emission per model", 'carbon'),
                'provider'     => Dashboard::class . "::cardTotalCarbonEmissionPerModelProvider",
            ],
            'plugin_carbon_card_carbon_emission_per_month' => [
                'widgettype'   => ['lines', 'areas', 'bars', 'stackedbars'],
                'group'        => __("Carbon", "carbon"),
                'label'        => __("Carbon emission per month", 'carbon'),
                'provider'     => Dashboard::class . "::cardCarbonEmissionPerMonthProvider",
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

    static function getTotalPower()
    {
        $unit = 'W';

        if ($total = DBUtils::getSum(Power::getTable(), 'power'))
            return strval($total) . " $unit";

        return "0 $unit";
    }

    static function getTotalCarbonEmission()
    {
        $unit = 'kg CO2';

        if ($total = DBUtils::getSum(CarbonEmission::getTable(), 'emission_per_day'))
            return number_format($total, 2) . " $unit";

        return "0.00 $unit";
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
        return DBUtils::getSumPerModel(Power::getTable(), Power::getTableField('power'), [Power::getTableField('power') => ['>', '0']]);
    }

    static function cardCarbonEmissionPerMonthProvider(array $params = [])
    {
        $default_params = [
            'label' => "plugin carbon - carbon emission per month",
            'icon'  => "fas fa-computer",
            'color' => '#ea9999',
        ];
        $params = array_merge($default_params, $params);

        global $DB;

        $emissions_table = CarbonEmission::getTable();

        $date = new \DateTime();
        $_31days = new DateInterval('P31D');
        $date->sub($_31days);

        $request = [
            'SELECT'    => [
                'SUM' => CarbonEmission::getTableField('emission_per_day') . ' AS total_emission_per_day',
                CarbonEmission::getTableField('emission_date') . ' AS emission_date',
            ],
            'FROM'      => $emissions_table,
            'GROUPBY' => CarbonEmission::getTableField('emission_date'),
            'WHERE' => [
                CarbonEmission::getTableField('emission_date') => ['>', $date->format('Y-m-d')],
            ],
        ];

        $data = [
            'labels' => [],
            'series' => [
                [
                    'name' => __("Carbon emission", "carbon"),
                    'data' => []
                ],
            ]
        ];

        $result = $DB->request($request);
        foreach ($result as $row) {
            $data['labels'][] = $row['emission_date'];
            $data['series'][0]['data'][] = $row['total_emission_per_day'];
        }

        return [
            'data'  => $data,
            'label' => $params['label'],
            'icon'  => $params['icon'],
        ];
    }

    // $date = new \DateTime();
    // $_31days = new DateInterval('P31D');
    // $_1day = new DateInterval('P1D');
    // $date->sub($_31days);

    // for ($day = 0; $day < 31; $day++) {
    //     $data['labels'][] = $date->format('Y-m-d');

    //     $data['series'][0]['data'][] = mt_rand(55, 100);

    //     $date->add($_1day);
    // }

    // return [
    //     'data'  => $data,
    //     'label' => $params['label'],
    //     'icon'  => $params['icon'],
    // ];

}
