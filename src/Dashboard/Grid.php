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
use NetworkEquipment;

class Grid
{
    public static function getDashboardCards($cards = []): array
    {
        if (is_null($cards)) {
            $cards = [];
        }

        // Declare the following cards only if we show / edit the quick report page of the plugin
        $in_carbon_report_page = self::in_carbon_report_page();

        if ($in_carbon_report_page) {
            // Provide cards for the report page
            $cards = array_merge($cards, self::getReportCards());
        } else {
            // Provide cards for the standard dashboard
            $cards = array_merge($cards, self::getStandardCards());
        }

        return $cards;
    }

    protected static function in_carbon_report_page(): bool
    {
        if (strpos($_SERVER['REQUEST_URI'], 'carbon/front/report.php') !== false) {
            return true;
        }

        if (strpos($_SERVER['REQUEST_URI'], '/ajax/dashboard.php') !== false) {
            if ((($_GET['dashboard'] ?? '') == 'plugin_carbon_board')) {
                return true;
            }
            if (($_POST['dashboard'] ?? '') == 'plugin_carbon_board') {
                if (in_array($_POST['action'], ['display_add_widget', 'display_edit_widget'])) {
                    return true;
                }
            }
        }

        return false;
    }

    protected static function getStandardCards(): array
    {
        $new_cards = [];
        // Data completeness diagnosis
        if (in_array(Computer::class, PLUGIN_CARBON_TYPES)) {
            $new_cards += [
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
        if (in_array(Monitor::class, PLUGIN_CARBON_TYPES)) {
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
        if (in_array(NetworkEquipment::class, PLUGIN_CARBON_TYPES)) {
            $new_cards += [
                'plugin_carbon_complete_network_equipments' => [
                    'widgettype'   => ['bigNumber'],
                    'group'        => __('Carbon', 'carbon'),
                    'label'        => __('Handled network equipments', 'carbon'),
                    'provider'     => Provider::class . '::getHandledNetworkEquipmentsCount',
                    'filter'       => Filter::getAppliableFilters(NetworkEquipment::getTable()),
                ],
                'plugin_carbon_incomplete_network_equipments' => [
                    'widgettype'   => ['bigNumber'],
                    'group'        => __('Carbon', 'carbon'),
                    'label'        => __('Unhandled network equipments', 'carbon'),
                    'provider'     => Provider::class . '::getUnhandledNetworkEquipmentsCount',
                    'filter'       => Filter::getAppliableFilters(NetworkEquipment::getTable()),
                ],
            ];
        }

        $new_cards += [
            // Usage impact
            'plugin_carbon_total_usage_power' => [
                'widgettype'   => ['bigNumber'],
                'group'        => __('Carbon', 'carbon'),
                'label'        => __('Usage power consumption', 'carbon'),
                'provider'     => Provider::class . '::getUsagePower',
            ],
            'plugin_carbon_total_usage_carbon_emission' => [
                'widgettype'   => ['bigNumber'],
                'group'        => __('Carbon', 'carbon'),
                'label'        => __('Usage carbon emission', 'carbon'),
                'provider'     => Provider::class . '::getUsageCarbonEmission',
            ],
            'plugin_carbon_total_usage_adp_impact' => [
                'widgettype'   => ['bigNumber'],
                'group'        => __('Carbon', 'carbon'),
                'label'        => __('Usage abiotic depletion potential', 'carbon'),
                'provider'     => Provider::class . '::getUsageAbioticDepletion',
            ],

            // Embodied impact
            'plugin_carbon_embodied_gwp_impact' => [
                'widgettype'   => ['bigNumber'],
                'group'        => __('Carbon', 'carbon'),
                'label'        => __('Embodied global warming potential', 'carbon'),
                'provider'     => Provider::class . '::getEmbodiedGlobalWarming',
            ],
            'plugin_carbon_embodied_pe_impact' => [
                'widgettype'   => ['bigNumber'],
                'group'        => __('Carbon', 'carbon'),
                'label'        => __('Embodied primary energy consumed', 'carbon'),
                'provider'     => Provider::class . '::getEmbodiedPrimaryEnergy',
            ],
            'plugin_carbon_embodied_adp_impact' => [
                'widgettype'   => ['bigNumber'],
                'group'        => __('Carbon', 'carbon'),
                'label'        => __('Embodied abiotic depletion potential', 'carbon'),
                'provider'     => Provider::class . '::getEmbodiedAbioticDepletion',
            ],

            // embodied + usage impact
            'plugin_carbon_total_gwp_impact' => [
                'widgettype'   => ['bigNumber'],
                'group'        => __('Carbon', 'carbon'),
                'label'        => __('Global warming potential', 'carbon'),
                'provider'     => Provider::class . '::getTotalGlobalWarming',
            ],
            'plugin_carbon_total_adp_impact' => [
                'widgettype'   => ['bigNumber'],
                'group'        => __('Carbon', 'carbon'),
                'label'        => __('Abiotic depletion potential', 'carbon'),
                'provider'     => Provider::class . '::getTotalAbioticDepletion',
            ],
        ];

        return $new_cards;
    }

    protected static function getReportCards(): array
    {
        $new_cards = [];

        // Data completeness diagnosis
        if (in_array(Computer::class, PLUGIN_CARBON_TYPES)) {
            $new_cards += [
                'plugin_carbon_report_unhandled_computers_ratio' => [
                    'widgettype'   => ['unhandled_computers_ratio'],
                    'group'        => __('Carbon', 'carbon'),
                    'label'        => __('Unhandled computers ratio', 'carbon'),
                ],
            ];
        }
        if (in_array(Monitor::class, PLUGIN_CARBON_TYPES)) {
            $new_cards += [
                'plugin_carbon_report_unhandled_monitors_ratio' => [
                    'widgettype'   => ['unhandled_monitors_ratio'],
                    'group'        => __('Carbon', 'carbon'),
                    'label'        => __('Unhandled monitors ratio', 'carbon'),
                ],
            ];
        }
        if (in_array(NetworkEquipment::class, PLUGIN_CARBON_TYPES)) {
            $new_cards += [
                'plugin_carbon_report_unhandled_network_equipments_ratio' => [
                    'widgettype'   => ['unhandled_network_equipments_ratio'],
                    'group'        => __('Carbon', 'carbon'),
                    'label'        => __('Unhandled network equipments ratio', 'carbon'),
                ],
            ];
        }

        // Usage impact
        $new_cards += [
            'plugin_carbon_report_usage_carbon_emission_ytd' => [
                'widgettype'   => ['usagecarbonemission_ytd'],
                'group'        => __('Carbon', 'carbon'),
                'label'        => __('Usage carbon emission year to date', 'carbon'),
            ],
            'plugin_carbon_report_usage_carbon_emission_two_last_months' => [
                'widgettype'   => ['totalusagecarbonemission_two_last_months'],
                'group'        => __('Carbon', 'carbon'),
                'label'        => __('Monthly carbon emission', 'carbon'),
            ],
            'plugin_carbon_report_usage_carbon_emissions_graph' => [
                'widgettype'   => ['usage_gwp_monthly'],
                'group'        => __('Carbon', 'carbon'),
                'label'        => __('Usage global warming potential chart', 'carbon'),
            ],
            'plugin_carbon_report_biggest_gwp_per_model' => [
                'widgettype'   => ['most_gwp_impacting_computer_models'],
                'group'        => __('Carbon', 'carbon'),
                'label'        => __('Biggest monthly averaged carbon emission per model', 'carbon'),
            ],

            // Embodied impact
            // 'plugin_carbon_report_embodied_gwp_impact' => [
            //     'widgettype'   => ['embodied_a'],
            //     'group'        => __('Carbon', 'carbon'),
            //     'label'        => __('Embodied abiotic depletion potential', 'carbon'),
            //     'provider'     => Provider::class . '::getTotalEmbodiedAdp',
            // ],
            'plugin_carbon_report_embodied_abiotic_depletion' => [
                'widgettype'   => ['embodied_abiotic_depletion'],
                'group'        => __('Carbon', 'carbon'),
                'label'        => __('Embodied abiotic depletion potential', 'carbon'),
            ],
            // 'plugin_carbon_embodied_pe_impact' => [
            //     'widgettype'   => ['embodied_primary_energy_impact'],
            //     'group'        => __('Carbon', 'carbon'),
            //     'label'        => __('Embodied primary energy consumed', 'carbon'),
            //     'provider'     => Provider::class . '::getTotalPrimaryEnergyConsumed',
            // ],
        ];

        // Informational content
        $new_cards += [
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
        ];

        return $new_cards;
    }
}
