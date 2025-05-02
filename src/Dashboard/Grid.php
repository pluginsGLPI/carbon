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
use DateTimeImmutable;
use Glpi\Dashboard\Filter;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\Config;
use GlpiPlugin\Carbon\Toolbox;
use Monitor;
use NetworkEquipment;
use Session;

class Grid
{
    public static function getDashboardCards(): array
    {
        $cards = [];
        // Declare the following cards only if we show / edit the quick report page of the plugin
        $in_carbon_report_page = self::in_carbon_report_page();

        if ($in_carbon_report_page) {
            // Provide cards for the report page
            $cards = array_merge($cards, self::getReportCards());
        } else {
            // Provide cards for the standard dashboard
            $cards = array_merge($cards, self::getStandardCards());
        }

        // Common cards
        $group = __('Carbon', 'carbon');
        $cards += [
            'plugin_carbon_assets_completeness_ratio' => [
                'widgettype'   => ['apex_radar', 'multipleNumber'],
                'group'        => $group,
                'label'        => __('Handled assets ratio', 'carbon'),
                'provider'     => Provider::class . '::getHandledAssetsRatio',
                'args'         => [
                    'itemtypes'   => PLUGIN_CARBON_TYPES
                ]
            ],
            'plugin_carbon_assets_completeness' => [
                'widgettype' => ['stackedbars'],
                'group'      => $group,
                'label'      => __('Handled assets count', 'carbon'),
                'provider'   => Provider::class . '::getHandledAssetsCounts',
            ]
        ];

        if (Config::isDemoMode()) {
            // Use demo providers
            foreach ($cards as &$card) {
                if (!isset($card['provider'])) {
                    continue;
                }
                $card['provider'] = str_replace(Provider::class, DemoProvider::class, $card['provider']);
            }
        }

        return $cards;
    }

    /**
     * Determine if the user is viewing or editing the reporting dashboard
     *
     * @return boolean
     */
    protected static function in_carbon_report_page(): bool
    {
        if (strpos($_SERVER['REQUEST_URI'], 'carbon/front/report.php') !== false) {
            return true;
        }

        if (strpos($_SERVER['REQUEST_URI'], '/ajax/dashboard.php') !== false) {
            if ((($_GET['dashboard'] ?? '') === 'plugin_carbon_board')) {
                return true;
            }
            if (($_POST['dashboard'] ?? '') === 'plugin_carbon_board') {
                if (in_array($_POST['action'], ['display_add_widget', 'display_edit_widget'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get description of cards for standard dashboard (in GLPI Central page)
     *
     * @return array
     */
    protected static function getStandardCards(): array
    {
        $new_cards = [];
        $group = __('Carbon', 'carbon');

        // Data completeness diagnosis

        foreach (PLUGIN_CARBON_TYPES as $itemtype) {
            $type_name = $itemtype::getTypeName(Session::getPluralNumber());
            $card_complete_key = 'plugin_carbon_complete_' . $type_name;
            $card_incomplete_key = 'plugin_carbon_incomplete_' . $type_name;
            $new_cards += [
                $card_complete_key => [
                    'widgettype'   => ['bigNumber'],
                    'group'        => $group,
                    'label'        => sprintf(__('Handled %s', 'carbon'), $type_name),
                    'provider'     => Provider::class . '::getHandledAssetCount',
                    'args'         => [
                        'itemtype' => $itemtype,
                        'handled'  => true,
                    ],
                    'filter'       => Filter::getAppliableFilters(Computer::getTable()),
                ],
                $card_incomplete_key => [
                    'widgettype'   => ['bigNumber'],
                    'group'        => $group,
                    'label'        => sprintf(__('Unhandled %s', 'carbon'), $type_name),
                    'provider'     => Provider::class . '::getHandledAssetCount',
                    'args'         => [
                        'itemtype' => $itemtype,
                        'handled'  => false,
                    ],
                    'filter'       => Filter::getAppliableFilters(Computer::getTable()),
                ],
            ];
        }

        $new_cards += [
            // Usage impact
            'plugin_carbon_total_usage_power' => [
                'widgettype'   => ['bigNumber'],
                'group'        => $group,
                'label'        => __('Usage power consumption', 'carbon'),
                'provider'     => Provider::class . '::getUsagePower',
            ],
            'plugin_carbon_total_usage_carbon_emission' => [
                'widgettype'   => ['bigNumber'],
                'group'        => $group,
                'label'        => __('Usage carbon emission', 'carbon'),
                'provider'     => Provider::class . '::getUsageCarbonEmission',
            ],
            'plugin_carbon_total_usage_adp_impact' => [
                'widgettype'   => ['bigNumber'],
                'group'        => $group,
                'label'        => __('Usage abiotic depletion potential', 'carbon'),
                'provider'     => Provider::class . '::getUsageAbioticDepletion',
            ],

            // Embodied impact
            'plugin_carbon_embodied_gwp_impact' => [
                'widgettype'   => ['bigNumber'],
                'group'        => $group,
                'label'        => __('Embodied global warming potential', 'carbon'),
                'provider'     => Provider::class . '::getEmbodiedGlobalWarming',
            ],
            'plugin_carbon_embodied_pe_impact' => [
                'widgettype'   => ['bigNumber'],
                'group'        => $group,
                'label'        => __('Embodied primary energy consumed', 'carbon'),
                'provider'     => Provider::class . '::getEmbodiedPrimaryEnergy',
            ],
            'plugin_carbon_embodied_adp_impact' => [
                'widgettype'   => ['bigNumber'],
                'group'        => $group,
                'label'        => __('Embodied abiotic depletion potential', 'carbon'),
                'provider'     => Provider::class . '::getEmbodiedAbioticDepletion',
            ],

            // embodied + usage impact
            'plugin_carbon_total_gwp_impact' => [
                'widgettype'   => ['bigNumber'],
                'group'        => $group,
                'label'        => __('Global warming potential', 'carbon'),
                'provider'     => Provider::class . '::getTotalGlobalWarming',
            ],
            'plugin_carbon_total_adp_impact' => [
                'widgettype'   => ['bigNumber'],
                'group'        => $group,
                'label'        => __('Abiotic depletion potential', 'carbon'),
                'provider'     => Provider::class . '::getTotalAbioticDepletion',
            ],
        ];

        return $new_cards;
    }

    /**
     * getdescription of cards for dashboard in the reporting page of the plugin
     *
     * @return array
     */
    protected static function getReportCards(): array
    {
        $new_cards = [];
        $group = __('Carbon', 'carbon');

        // Data completeness diagnosis
        foreach (PLUGIN_CARBON_TYPES as $itemtype) {
            $type_name = $itemtype::getTypeName(Session::getPluralNumber());
            $type_name = strtolower($type_name);
            $card_incomplete_ratio_key = 'plugin_carbon_incomplete_' . $type_name . '_ratio';
            $new_cards += [
                $card_incomplete_ratio_key => [
                    'widgettype'   => ['unhandled_' . $type_name . '_ratio'],
                    'group'        => $group,
                    'label'        => sprintf(__('Unhandled %s ratio', 'carbon'), $type_name),
                    'provider'     => Provider::class . '::getHandledAssetsCounts',
                    'args'         => ['itemtypes' => $itemtype]
                ],
            ];
        }

        // Usage impact
        $new_cards += [
            'plugin_carbon_report_usage_carbon_emission_ytd' => [
                'widgettype'   => ['usage_carbon_emission_ytd'],
                'group'        => $group,
                'label'        => __('Usage carbon emission year to date', 'carbon'),
                'provider'     => Provider::class . '::getUsageCarbonEmissionYearToDate',
            ],
            'plugin_carbon_report_usage_carbon_emission_two_last_months' => [
                'widgettype'   => ['total_usage_carbon_emission_two_last_months'],
                'group'        => $group,
                'label'        => __('Monthly carbon emission', 'carbon'),
                'provider'     => Provider::class . '::getUsageCarbonEmissionlastTwoMonths',
                'args'         => [
                    'crit'        => []
                ]
            ],
            'plugin_carbon_report_usage_carbon_emissions_graph' => [
                'widgettype'   => ['usage_gwp_monthly'],
                'group'        => $group,
                'label'        => __('Usage global warming potential chart', 'carbon'),
                'provider'     => Provider::class . '::getUsageCarbonEmissionPerMonth',
                'args'         => [
                    'crit'        => []
                ]
            ],
            'plugin_carbon_report_biggest_gwp_per_model' => [
                'widgettype'   => ['most_gwp_impacting_computer_models'],
                'group'        => $group,
                'label'        => __('Biggest monthly averaged carbon emission per model', 'carbon'),
                'provider'     => Provider::class . '::getSumUsageEmissionsPerModel',
            ],
            'plugin_carbon_report_usage_abiotic_depletion' => [
                'widgettype'   => ['usage_abiotic_depletion'],
                'group'        => $group,
                'label'        => __('Usage abiotic depletion potential', 'carbon'),
                'provider'     => Provider::class . '::getUsageAbioticDepletion',
            ],

            // Embodied impact
            'plugin_carbon_report_embodied_global_warming' => [
                'widgettype'   => ['embodied_global_warming'],
                'group'        => $group,
                'label'        => __('Embodied global warming potential', 'carbon'),
                'provider'     => Provider::class . '::getEmbodiedGlobalWarming',
            ],
            'plugin_carbon_report_embodied_abiotic_depletion' => [
                'widgettype'   => ['embodied_abiotic_depletion'],
                'group'        => $group,
                'label'        => __('Embodied abiotic depletion potential', 'carbon'),
                'provider'     => Provider::class . '::getEmbodiedAbioticDepletion',
            ],
            'plugin_carbon_report_embodied_pe_impact' => [
                'widgettype'   => ['embodied_primary_energy'],
                'group'        => $group,
                'label'        => __('Embodied primary energy consumed', 'carbon'),
                'provider'     => Provider::class . '::getEmbodiedPrimaryEnergy',
            ],
        ];

        // Informational content
        $new_cards += [
            'plugin_carbon_report_information_video' => [
                'widgettype'   => ['information_video'],
                'group'        => $group,
                'label'        => __('Environmental impact information video', 'carbon'),
            ],
            'plugin_carbon_report_methodology_information' => [
                'widgettype'   => ['methodology_information'],
                'group'        => $group,
                'label'        => __('Environmental impact methodology_information', 'carbon'),
            ],
        ];

        return $new_cards;
    }
}
