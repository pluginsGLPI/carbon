<?php

namespace GlpiPlugin\Carbon;

use GlpiPlugin\Carbon\Power;

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
            'number' => Power::getTotalPower(),
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
            'data' => Power::getPowerPerModel(),
            'label'  => $params['label'],
            'icon'  => $params['icon'],
            'color' => $params['color'],
        ];
    }
}
