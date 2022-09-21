<?php

namespace GlpiPlugin\Carbon;

class Dashboard
{
    static function dashboardCards($cards = [])
    {
        if (is_null($cards)) {
            $cards = [];
        }

        $new_cards =  [
            'plugin_carbon_card_global_power' => [
                'widgettype'   => ["bigNumber"],
                'label'        => "GLPI Carbon - Global power consumption",
                'provider'     => Dashboard::class . "::cardGlobalPowerProvider",
            ],
        ];

        return array_merge($cards, $new_cards);
    }

    static function cardGlobalPowerProvider(array $params = []) {
        $default_params = [
           'label' => null,
           'icon'  => null,
        ];
        $params = array_merge($default_params, $params);
  
        return [
           'number' => 42,
           'url'    => "https://www.linux.org/",
           'label'  => "plugin carbon - global power",
           'icon'   => "fab fa-linux", // font awesome icon
        ];
     }
  
}
