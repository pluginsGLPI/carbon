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

namespace GlpiPlugin\Carbon\Dashboard;

use ComputerModel;
use GlpiPlugin\Carbon\Toolbox;

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
            'plugin_carbon_card_complete_computers' => [
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
            'plugin_carbon_card_carbon_intensity' => [
                'widgettype'   => ['lines', 'bars'],
                'group'        => __("Carbon", "carbon"),
                'label'        => __("Carbon intensity  (gCO<sub>2</sub>eq / KWh)", 'carbon'),
                'provider'     => Dashboard::class . "::cardCarbonintensityProvider",
            ],
            'plugin_carbon_card_carbon_emission_per_type' => [
                'widgettype'   => ['graphpertype'],
                'group'        => __("Carbon", "carbon"),
                'label'        => __("Carbon emission per type", 'carbon'),
            ],
            'plugin_carbon_card_total_carbon_emission' => [
                'widgettype'   => ['totalcarbonemission'],
                'group'        => __("Carbon", "carbon"),
                'label'        => __("Total carbon emission", 'carbon'),
            ],
            'plugin_carbon_card_monthly_carbon_emission' => [
                'widgettype'   => ['monthlycarbonemission'],
                'group'        => __("Carbon", "carbon"),
                'label'        => __("Monthly carbon emission", 'carbon'),
            ],
            'plugin_carbon_card_unhandled_computers' => [
                'widgettype'   => ['unhandledcomputers'],
                'group'        => __("Carbon", "carbon"),
                'label'        => __("Unhandled computers", 'carbon'),
            ],
            'plugin_carbon_card_graph_carbon_emission_per_month' => [
                'widgettype'   => ['graphpermonth'],
                'group'        => __("Carbon", "carbon"),
                'label'        => __("Carbon emission per month graph", 'carbon'),
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
        // GLPI dashboard
        return self::cardNumberProvider($params, "total power", Provider::getTotalPower());
    }

    public static function cardTotalCarbonEmissionProvider(array $params = [])
    {
        return self::cardNumberProvider($params, "total carbon emission", Provider::getTotalCarbonEmission());
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
     * Returns total carbon emission per computer type.
     *
     * @return array of:
     *  - float  'number': total carbon emission of the type
     *  - string 'url': url to redirect when clicking on the slice
     *  - string 'label': name of the computer type
     */
    public static function getTotalCarbonEmissionPerType()
    {
        return Provider::getSumEmissionsPerType();
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

    public static function cardCarbonEmissionPerMonthProvider(array $params = [])
    {
        $default_params = [
            'label' => __('Carbon emission per month', 'carbon'),
            'icon'  => "fas fa-computer",
            'color' => '#ea9999',
        ];
        $params = array_merge($default_params, $params);

        $data = Provider::getCarbonEmissionPerMonth($params);

        return [
            'data'  => $data,
            'label' => $params['label'],
            'icon'  => $params['icon'],
        ];
    }

    public static function cardCarbonintensityProvider(array $params = [])
    {
        $default_params = [
            'label' => __('Carbon dioxyde intensity', 'carbon'),
            'icon'  => "fas fa-computer",
            'color' => '#ea9999',
        ];
        $params = array_merge($default_params, $params);

        $data = Provider::getCarbonIntensity($params);

        return [
            'data'  => $data,
            'label' => $params['label'],
            'icon'  => $params['icon'],
        ];
    }
}
