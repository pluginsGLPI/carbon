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

use Computer;
use Glpi\Dashboard\Filter;

class Grid
{
    public static function getDashboardCards($cards = [])
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
                'filter'       => Filter::getAppliableFilters(Computer::getTable()),
            ],
            'plugin_carbon_card_complete_computers' => [
                'widgettype'   => ["bigNumber"],
                'group'        => __("Carbon", "carbon"),
                'label'        => __("Handled computers", "carbon"),
                'provider'     => Dashboard::class . "::cardHandledComputersCountProvider",
                'filter'       => Filter::getAppliableFilters(Computer::getTable()),
            ],
            'plugin_carbon_card_unhandled_computers' => [
                'widgettype'   => ['unhandledcomputers'],
                'group'        => __("Carbon", "carbon"),
                'label'        => __("Unhandled computers", 'carbon'),
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
            'plugin_carbon_card_carbon_emission_per_month' => [
                'widgettype'   => ['apex_lines'],
                'group'        => __("Carbon", "carbon"),
                'label'        => __("Carbon emission per month", 'carbon'),
                'provider'     => Provider::class . "::getCarbonEmissionPerMonth",
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
                'provider'     => Provider::class . "::getSumEmissionsPerModel",
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
        ];

        return array_merge($cards, $new_cards);
    }
}
