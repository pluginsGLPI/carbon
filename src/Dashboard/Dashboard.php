<?php

namespace GlpiPlugin\Carbon\Dashboard;

use ComputerModel;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\DBUtils;
use DateInterval;
use DateTime;

class Dashboard
{
    public static function dashboardCards($cards = [])
    {
        if (is_null($cards)) {
            $cards = [];
        }

        $new_cards = [
            'plugin_carbon_card_incomplete_computers' => [
                'widgettype'   => ["bigNumber"],
                'group'        => __("Carbon", "carbon"),
                'label'        => __("Unhandled computers", "carbon"),
                'provider'     => Dashboard::class . "::cardUnhandledComputersCountProvider",
            ],
            'plugin_carbon_card_cmplete_computers' => [
                'widgettype'   => ["bigNumber"],
                'group'        => __("Carbon", "carbon"),
                'label'        => __("Handled computers", "carbon"),
                'provider'     => Dashboard::class . "::cardHandledComputersCountProvider",
            ],
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

    public static function cardNumberProvider(array $params = [], string $label = "", string $number = "")
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

    public static function cardUnhandledComputersCountProvider(array $params = [])
    {
        return self::cardNumberProvider($params, "unhandled computers", self::getUnhandledComputersCount());
    }

    public static function cardHandledComputersCountProvider(array $params = [])
    {
        return self::cardNumberProvider($params, "unhandled computers", self::getHandledComputersCount());
    }

    public static function cardTotalPowerProvider(array $params = [])
    {
        return self::cardNumberProvider($params, "total power", self::getTotalPower());
    }

    public static function cardTotalCarbonEmissionProvider(array $params = [])
    {
        return self::cardNumberProvider($params, "total carbon emission", self::getTotalCarbonEmission());
    }

    public static function cardDataProvider(array $params = [], string $label = "", array $data = [])
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

    public static function cardTotalPowerPerModelProvider(array $params = [])
    {
        return self::cardDataProvider($params, "total power per model", self::getTotalPowerPerModel());
    }

    public static function cardTotalCarbonEmissionPerModelProvider(array $params = [])
    {
        return self::cardDataProvider($params, "total carbon emission per model", self::getTotalCarbonEmissionPerModel());
    }

    public static function getUnhandledComputersCount()
    {
        $unit = ''; // This is a count, no unit

        $total = Provider::getUnhandledComputersCount();
        if ($total === null) {
            return 'N/A';
        }

        return strval($total) . " $unit";
    }

    public static function getHandledComputersCount()
    {
        $unit = ''; // This is a count, no unit

        $total = Provider::getHandledComputersCount();
        if ($total === null) {
            return 'N/A';
        }

        return strval($total) . " $unit";
    }

    public static function getTotalPower()
    {
        $unit = 'W';

        if ($total = Provider::getSum(ComputerType::getTable(), 'power')) {
            return strval($total) . " $unit";
        }

        return "0 $unit";
    }

    public static function getTotalCarbonEmission()
    {
        $unit = 'kg CO2';

        if ($total = Provider::getSum(CarbonEmission::getTable(), 'emission_per_day')) {
            return number_format($total, 2) . " $unit";
        }

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
    public static function getTotalCarbonEmissionPerModel()
    {
        return Provider::getSumEmissionsPerModel();
    }

    /**
     * Returns total power per computer model.
     *
     * @return array of:
     *   - int  'number': total power of the model
     *   - string 'url': url to redirect when clicking on the slice
     *   - string 'label': name of the computer model
     */
    public static function getTotalPowerPerModel()
    {
        return Provider::getSumPowerPerModel([ComputerModel::getTableField('power_consumption') => ['>', '0']]);
    }

    public static function getCarbonEmissionPerMonth()
    {
        global $DB;

        $emissions_table = CarbonEmission::getTable();

        $date = new DateTime();
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

        return $data;
    }

    public static function cardCarbonEmissionPerMonthProvider(array $params = [])
    {
        $default_params = [
            'label' => "plugin carbon - carbon emission per month",
            'icon'  => "fas fa-computer",
            'color' => '#ea9999',
        ];
        $params = array_merge($default_params, $params);

        $data = self::getCarbonEmissionPerMonth();

        return [
            'data'  => $data,
            'label' => $params['label'],
            'icon'  => $params['icon'],
        ];
    }

    // $date = new DateTime();
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
