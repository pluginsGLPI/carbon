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
use Monitor;

class Grid
{
    public static function getDashboardCards($cards = [])
    {
        if (is_null($cards)) {
            $cards = [];
        }

        $new_cards = [];
        if (in_array(Computer::class, PLUGIN_CARBON_TYPES)) {
            $new_cards += [
                // Data completeness diagnosis
                'plugin_carbon_complete_computers' => [
                    'widgettype'   => ['bigNumber'],
                    'group'        => __('Carbon', 'carbon'),
                    'label'        => __('Handled computers', 'carbon'),
                    'provider'     => Provider::class . '::getHandledComputersCount',
                    'filter'       => Filter::getAppliableFilters(Computer::getTable()),
                ],
                'plugin_carbon_incomplete_computers' => [
                    'widgettype'   => ['bigNumber'],
                    'group'        => __('Carbon', 'carbon'),
                    'label'        => __('Unhandled computers', 'carbon'),
                    'provider'     => Provider::class . '::getUnhandledComputersCount',
                    'filter'       => Filter::getAppliableFilters(Computer::getTable()),
                ],
            ];
        }

        if (in_array(Computer::class, PLUGIN_CARBON_TYPES)) {
            $new_cards += [
                'plugin_carbon_complete_monitors' => [
                    'widgettype'   => ['bigNumber'],
                    'group'        => __('Carbon', 'carbon'),
                    'label'        => __('Handled monitors', 'carbon'),
                    'provider'     => Provider::class . '::getHandledMonitorsCount',
                    'filter'       => Filter::getAppliableFilters(Monitor::getTable()),
                ],
                'plugin_carbon_incomplete_monitors' => [
                    'widgettype'   => ['bigNumber'],
                    'group'        => __('Carbon', 'carbon'),
                    'label'        => __('Unhandled monitors', 'carbon'),
                    'provider'     => Provider::class . '::getUnhandledMonitorsCount',
                    'filter'       => Filter::getAppliableFilters(Monitor::getTable()),
                ],
            ];
        }
        // TODO : Data completeness diagnosis for other assets (Net equipment, ...)

        // Usage impact
        $new_cards += [
            'plugin_carbon_total_power' => [
                'widgettype'   => ['bigNumber'],
                'group'        => __('Carbon', 'carbon'),
                'label'        => __('Total usage power consumption', 'carbon'),
                'provider'     => Provider::class . '::getTotalUsagePower',
            ],
            'plugin_carbon_total_carbon_emission' => [
                'widgettype'   => ['bigNumber'],
                'group'        => __('Carbon', 'carbon'),
                'label'        => __('Total usage carbon emission', 'carbon'),
                'provider'     => Provider::class . '::getTotalUsageCarbonEmission',
            ],

            // Embodied impact

            'plugin_carbon_total_embodied_gwp_impact' => [
                'widgettype'   => ['bigNumber'],
                'group'        => __('Carbon', 'carbon'),
                'label'        => __('Total embodied global warming potential', 'carbon'),
                'provider'     => Provider::class . '::getTotalEmbodiedGwp',
            ],
            'plugin_carbon_total_embodied_pe_impact' => [
                'widgettype'   => ['bigNumber'],
                'group'        => __('Carbon', 'carbon'),
                'label'        => __('Total embodied primary energy consumed', 'carbon'),
                'provider'     => Provider::class . '::getTotalPrimaryEnergyConsumed',
            ],
            'plugin_carbon_total_embodied_adp_impact' => [
                'widgettype'   => ['bigNumber'],
                'group'        => __('Carbon', 'carbon'),
                'label'        => __('Total embodied abiotic depletion potential', 'carbon'),
                'provider'     => Provider::class . '::getTotalEmbodiedAdp',
            ],
        ];

        // Declare the following cards only if we show the quick report page of the plugin
        if (strpos($_SERVER['REQUEST_URI'], 'carbon/front/report.php') !== false || strpos($_SERVER['REQUEST_URI'], '/ajax/dashboard.php') !== false) {
            $new_cards += [
                'plugin_carbon_report_totalcarbonemission_ytd' => [
                    'widgettype'   => ['totalcarbonemission_ytd'],
                    'group'        => __('Carbon', 'carbon'),
                    'label'        => __('Total usage carbon emission', 'carbon'),
                ],
                'plugin_carbon_report_totalcarbonemission_two_last_months' => [
                    'widgettype'   => ['totalcarbonemission_two_last_months'],
                    'group'        => __('Carbon', 'carbon'),
                    'label'        => __('Monthly carbon emission', 'carbon'),
                ],
                'plugin_carbon_report_unhandled_computers' => [
                    'widgettype'   => ['unhandledcomputers'],
                    'group'        => __('Carbon', 'carbon'),
                    'label'        => __('Unhandled computers', 'carbon'),
                ],
                'plugin_carbon_report_information_video' => [
                    'widgettype'   => ['information_video'],
                    'group'        => __('Carbon', 'carbon'),
                    'label'        => __('Environmental impact information video', 'carbon'),
                ],
                'plugin_carbon_report_methodology_information' => [
                    'widgettype'   => ['methodology_information'],
                    'group'        => __('Carbon', 'carbon'),
                    'label'        => __('Environmental impact methodology_information', 'carbon'),
                ],
                'plugin_carbon_report_usage_carbon_emissions_graph' => [
                    'widgettype'   => ['usage_gwp_monthly'],
                    'group'        => __('Carbon', 'carbon'),
                    'label'        => __('usage global warming potential chart', 'carbon'),
                ],
                'plugin_carbon_biggest_gwp_per_model' => [
                    'widgettype'   => ['most_gwp_impacting_computer_models'],
                    'group'        => __('Carbon', 'carbon'),
                    'label'        => __('Biggest monthly averaged carbon emission per model', 'carbon'),
                ],
            ];
        }

        return array_merge($cards, $new_cards);
    }
}
