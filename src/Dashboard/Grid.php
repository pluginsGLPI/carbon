<?php

/**
 * -------------------------------------------------------------------------
 * Carbon plugin for GLPI
 *
 * @copyright Copyright (C) 2024-2025 Teclib' and contributors.
 * @license   https://www.gnu.org/licenses/gpl-3.0.txt GPLv3+
 * @link      https://github.com/pluginsGLPI/carbon
 *
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Carbon plugin for GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Carbon\Dashboard;

use Computer;
use Glpi\Dashboard\Filter;
use GlpiPlugin\Carbon\Config;
use GlpiPlugin\Carbon\Impact\Type;
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
                    'itemtypes'   => PLUGIN_CARBON_TYPES,
                ],
            ],
            'plugin_carbon_assets_completeness' => [
                'widgettype' => ['stackedbars'],
                'group'      => $group,
                'label'      => __('Handled assets count', 'carbon'),
                'provider'   => Provider::class . '::getHandledAssetsCounts',
            ],
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
     * @return bool
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

        $impact_types = Type::getImpactTypes();

        // Embodied impact
        foreach ($impact_types as $impact_type) {
            $key = "plugin_carbon_embodied_{$impact_type}_impact";
            if (isset($new_cards[$key])) {
                trigger_error("The card $key already exists", E_USER_WARNING);
            }
            $new_cards[$key] = [
                'widgettype'   => ['bigNumber'],
                'group'        => $group,
                'label'        => Type::getEmbodiedImpactLabel($impact_type),
                'provider'     => Provider::class . '::getImpactOfEmbodiedCriteria',
                'args'     => [
                    'impact_type' => $impact_type,
                ],
            ];
        }

        // Usage impact
        foreach ($impact_types as $impact_type) {
            $key = "plugin_carbon_usage_{$impact_type}_impact";
            if (isset($new_cards[$key])) {
                trigger_error("The card $key already exists", E_USER_WARNING);
            }
            $new_cards[$key] = [
                'widgettype'   => ['bigNumber'],
                'group'        => $group,
                'label'        => Type::getUsageImpactLabel($impact_type),
                'provider'     => Provider::class . '::getImpactOfUsageCriteria',
                'args'     => [
                    'impact_type' => $impact_type,
                ],
            ];
        }

        // embodied + usage impact
        foreach ($impact_types as $impact_type) {
            $key = "plugin_carbon_all_scopes_{$impact_type}_impact";
            if (isset($new_cards[$key])) {
                trigger_error("The card $key already exists", E_USER_WARNING);
            }
            $new_cards[$key] = [
                'widgettype'   => ['bigNumber'],
                'group'        => $group,
                'label'        => Type::getEmbodiedAndUsageImpactLabel($impact_type),
                'provider'     => Provider::class . '::getImpactOfEmbodiedAndUsageCriteria',
                'args'     => [
                    'impact_type' => $impact_type,
                ],
            ];
        }

        return $new_cards;
    }

    /**
     * Get description of cards for dashboard in the reporting page of the plugin
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
                    'args'         => ['itemtypes' => $itemtype],
                ],
            ];
        }

        $new_cards += [
            // Usage impact
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
                    'crit'        => [],
                ],
            ],
            'plugin_carbon_report_usage_carbon_emissions_graph' => [
                'widgettype'   => ['usage_gwp_monthly'],
                'group'        => $group,
                'label'        => __('Usage global warming potential chart', 'carbon'),
                'provider'     => Provider::class . '::getUsageCarbonEmissionPerMonth',
                'args'         => [
                    'crit'        => [],
                ],
            ],
            'plugin_carbon_report_biggest_gwp_per_model' => [
                'widgettype'   => ['most_gwp_impacting_computer_models'],
                'group'        => $group,
                'label'        => __('Biggest monthly averaged carbon emission per model', 'carbon'),
                'provider'     => Provider::class . '::getSumUsageEmissionsPerModel',
            ],
        ];

        foreach (Type::getImpactTypes() as $impact_type) {
            // Embodied impact
            $key = "plugin_carbon_report_embodied_{$impact_type}_impact";
            if (isset($new_cards[$key])) {
                trigger_error("The card $key already exists", E_USER_WARNING);
            }
            $new_cards[$key] = [
                'widgettype'   => ['impact_criteria_number'],
                'group'        => $group,
                'label'        => Type::getEmbodiedImpactLabel($impact_type),
                'provider'     => Provider::class . '::getImpactOfEmbodiedCriteria',
                'args'     => [
                    'impact_type' => $impact_type,
                ],
            ];

            // Usage impact
            $key = "plugin_carbon_report_usage_{$impact_type}_impact";
            if (isset($new_cards[$key])) {
                trigger_error("The card $key already exists", E_USER_WARNING);
            }
            $new_cards[$key] = [
                'widgettype'   => ['impact_criteria_number'],
                'group'        => $group,
                'label'        => Type::getUsageImpactLabel($impact_type),
                'provider'     => Provider::class . '::getImpactOfUsageCriteria',
                'args'     => [
                    'impact_type' => $impact_type,
                ],
            ];

            // Embodied + Usage
            $key = "plugin_carbon_all_scopes_{$impact_type}_impact";
            if (isset($new_cards[$key])) {
                trigger_error("The card $key already exists", E_USER_WARNING);
            }
            $new_cards[$key] = [
                'widgettype'   => ['impact_criteria_number'],
                'group'        => $group,
                'label'        => Type::getEmbodiedAndUsageImpactLabel($impact_type),
                'provider'     => Provider::class . '::getImpactOfEmbodiedAndUsageCriteria',
                'args'     => [
                    'impact_type' => $impact_type,
                ],
            ];
        }

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
                'label'        => __('Environmental impact methodology information', 'carbon'),
            ],
        ];

        return $new_cards;
    }
}
