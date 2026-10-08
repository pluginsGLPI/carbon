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
use DateInterval;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Dashboard\Widget as GlpiDashboardWidget;
use GlpiPlugin\Carbon\Impact\Type;
use GlpiPlugin\Carbon\Toolbox;
use Html;
use Monitor;
use NetworkEquipment;
use Safe\DateTime;
use Safe\DateTimeImmutable;
use Toolbox as GlpiToolbox;

class Widget extends GlpiDashboardWidget
{
    /**
     * Get the description of additional widget types
     *
     * @param null|array $types existing types
     * @return array
     */
    public static function WidgetTypes(?array $types = null): array
    {
        $types = array_merge($types ?? [], [
            // Informative
            'information_video' => [
                'label'    => __('Environmental impact information video', 'carbon'),
                'function' => self::class . '::DisplayInformationVideo',
                'image'      => '',
                'width'    => 6,
                'height'   => 3,
            ],
            'methodology_information' => [
                'label'    => __('Methodology information', 'carbon'),
                'function' => self::class . '::DisplayInformationMethodology',
                'image'      => '',
                'width'    => 6,
                'height'   => 3,
            ],

            // Usage impact
            'usage_carbon_emission_ytd' => [
                'label'    => __('Total Carbon Emission', 'carbon'),
                'function' => self::class . '::displayUsageCarbonEmissionYearToDate',
                'image'      => '',
                'width'    => 6,
                'height'   => 3,
            ],
            'total_usage_carbon_emission_two_last_months' => [
                'label'    => __('Monthly Carbon Emission', 'carbon'),
                'function' => self::class . '::DisplayMonthlyCarbonEmission',
                'image'      => '',
                'width'    => 6,
                'height'   => 3,
            ],
            'most_gwp_impacting_computer_models' => [
                'label'    => __('Biggest monthly averaged carbon emission per model', 'carbon'),
                'function' => self::class . '::DisplayGraphUsageCarbonEmissionPerModel',
                'image'      => '',
                'width'    => 6,
                'height'   => 3,
                'limit'    => true,
            ],
            'usage_gwp_monthly' => [
                'label'    => __('Carbon Emission Per month', 'carbon'),
                'function' => self::class . '::DisplayGraphUsageCarbonEmissionPerMonth',
                'image'      => '',
                'width'    => 16,
                'height'   => 12,
            ],

            'impact_criteria_number' => [
                'label'    => __('Impact criteria', 'carbon'),
                'function' => self::class . '::displayImpactCriteriaNumber',
                'image'      => '',
                'width'    => 6,
                'height'   => 3,
            ],
        ]);

        // Data diagnostic
        if (in_array(Computer::class, PLUGIN_CARBON_TYPES)) {
            $types += [
                'unhandled_computers_ratio' => [
                    'label'    => __('Unhandled Computers', 'carbon'),
                    'function' => self::class . '::DisplayUnhandledComputersRatio',
                    'image'      => '',
                    'width'    => 5,
                    'height'   => 3,
                ],
            ];
        }
        if (in_array(Monitor::class, PLUGIN_CARBON_TYPES)) {
            $types += [
                'unhandled_monitors_ratio' => [
                    'label'    => __('Unhandled Monitors', 'carbon'),
                    'function' => self::class . '::DisplayUnhandledMonitorsRatio',
                    'image'      => '',
                    'width'    => 5,
                    'height'   => 3,
                ],
            ];
        }
        if (in_array(NetworkEquipment::class, PLUGIN_CARBON_TYPES)) {
            $types += [
                'unhandled_network_equipments_ratio' => [
                    'label'    => __('Unhandled Network equipments', 'carbon'),
                    'function' => self::class . '::DisplayUnhandledNetworkEquipmentsRatio',
                    'image'      => '',
                    'width'    => 5,
                    'height'   => 3,
                ],
            ];
        }

        $types += [
            'apex_radar' => [
                'label'    => __('Radar chart', 'carbon'),
                'function' => self::class . '::apexRadar',
                'image'    => '',
                'width'    => 4,
                'height'   => 4,
            ],
        ];

        return $types;
    }

    public static function displayGraphUsageCarbonEmissionPerMonth(array $params = []): string
    {
        $default = [
            'url'     => '',
            'label'   => __('Consumed energy and carbon emission per month', 'carbon'),
            'alt'     => '',
            'color'   => '#FFFFFF',
            'icon'    => '',
            'id'      => 'plugin_carbon_usage_carbon_emissions_' . mt_rand(),
            'filters' => [], // TODO: Not implemented yet (is this useful ?)
        ];
        $p = array_merge($default, $params);

        $fg_color        = GlpiToolbox::getFgColor($p['color']);
        $dark_bg_color   = GlpiToolbox::getFgColor($p['color'], 80);
        $dark_fg_color   = GlpiToolbox::getFgColor($p['color'], 40);
        $fg_hover_color  = GlpiToolbox::getFgColor($p['color'], 15);
        $fb_hover_border = GlpiToolbox::getFgColor($p['color'], 30);

        $data = $p['data'];
        $energy = array_column($data['series'][1]['data'], 'y');
        $energy_min = count($energy) > 0 ? 0.8 * min($energy) : 0;
        $echarts_data = [
            'title' => [
                'text' => $p['label'],
                'textStyle' => [
                    'color' => $fg_color,
                ],
            ],
            'color' => ['#BBDA50', '#A00'],
            'tooltip' => [
                'trigger' => 'axis',
            ],
            'legend' => [
                'data' => array_column($data['series'], 'name'),
            ],
            'grid' => [
                'containLabel' => true,
            ],
            'xAxis' => [
                'type' => 'category',
                'data' => $data['labels'],
            ],
            'yAxis' => [
                [
                    'type' => 'value',
                    'position' => 'left',
                    'name' => $data['series'][0]['name'],
                    'nameLocation' => 'middle',
                    'nameRotate' => 90,
                    'nameGap' => 40,
                    'splitLine' => ['show' => false],
                ],
                [
                    'type' => 'value',
                    'position' => 'right',
                    'name' => $data['series'][1]['name'],
                    'nameLocation' => 'middle',
                    'nameRotate' => 90,
                    'nameGap' => 40,
                    'min' => $energy_min,
                    'splitLine' => ['show' => false],
                ],
            ],
            'series' => [
                [
                    'name' => $data['series'][0]['name'],
                    'type' => 'bar',
                    'yAxisIndex' => 0,
                    'data' => array_column($data['series'][0]['data'], 'y'),
                ],
                [
                    'name' => $data['series'][1]['name'],
                    'type' => 'line',
                    'yAxisIndex' => 1,
                    'smooth' => true,
                    'symbolSize' => 6,
                    'data' => $energy,
                ],
            ],
        ];

        return TemplateRenderer::getInstance()->render('@carbon/dashboard/graph-carbon-emission-per-month.html.twig', [
            'id' => $p['id'],
            'color' => $p['color'],
            'fg_color' => $fg_color,
            'dark_fg_color'   => $dark_fg_color,
            'dark_bg_color'   => $dark_bg_color,
            'fg_hover_color' => $fg_hover_color,
            'fg_hover_border' => $fb_hover_border,
            'data' => $echarts_data,
        ]);
    }

    public static function displayGraphUsageCarbonEmissionPerModel(array $params = []): string
    {
        $default = [
            'url'     => '',
            'label'   => __('Biggest monthly averaged carbon emission per model', 'carbon'),
            'alt'     => '',
            'color'   => '',
            'icon'    => '',
            'id'      => 'plugin_carbon_usage_carbon_emissions_per_model_' . mt_rand(),
            'filters' => [], // TODO: Not implemented yet (is this useful ?)
        ];
        $p = array_merge($default, $params);
        $fg_color = GlpiToolbox::getFgColor($p['color']);
        $data = $p['data'];
        $source_values = $data['series'] ?? [];
        $source_labels = $data['labels'] ?? [];
        $source_urls = $data['url'] ?? [];
        $limit = min($params['limit'] ?? count($source_values), count($source_values));
        $labels = array_slice($source_labels, 0, $limit);
        $values = array_slice($source_values, 0, $limit);
        $urls = array_slice($source_urls, 0, $limit);
        $series_data = [];
        foreach ($values as $index => $value) {
            $series_data[] = [
                'name' => $labels[$index],
                'value' => $value,
                'url' => $urls[$index],
            ];
        }
        $series_data[] = [
            'name' => '',
            'value' => array_sum($values),
            'itemStyle' => ['color' => 'transparent'],
            'tooltip' => ['show' => false],
            'label' => ['show' => false],
        ];

        $echarts_data = [
            'title' => [
                'text' => $p['label'],
                'textStyle' => [
                    'color' => $fg_color,
                ],
            ],
            'color' => ['#146151', '#FEEC5C', '#BBDA50', '#F78343', '#97989C'],
            'tooltip' => [
                'trigger' => 'item',
                'appendToBody' => true,
            ],
            'legend' => [
                'show' => true,
                'type' => 'scroll',
                'orient' => 'vertical',
                'data' => $labels,
                // 'left' => '68%',
                // 'top' => '50%',
                'right'  => '5%',
                'top'    => '25%',
                'textStyle' => [
                    'color' => $fg_color,
                ],
            ],
            'series' => [
                [
                    'type' => 'pie',
                    'radius' => ['40%', '70%'],
                    // 'center' => ['32%', '68%'],
                    'center' => ['25%', '68%'],
                    'startAngle' => 180,
                    'avoidLabelOverlap' => true,
                    'data' => $series_data,
                    'label' => [
                        'show' => false,
                    ],
                    'labelLine' => [
                        'show' => false,
                    ],
                ],
            ],
        ];

        return TemplateRenderer::getInstance()->render('@carbon/dashboard/graph-carbon-emission-per-model.html.twig', [
            'id' => $p['id'],
            'color' => $p['color'],
            'fg_color' => $fg_color,
            'fg_hover_color'  => GlpiToolbox::getFgColor($p['color'], 15),
            'fg_hover_border' => GlpiToolbox::getFgColor($p['color'], 30),
            'data' => $echarts_data,
        ]);
    }

    public static function displayMonthlyCarbonEmission(array $params = []): string
    {
        $default = [
            'number'  => 0,
            'url'     => '',
            'label'   => '',
            'alt'     => '',
            'color'   => '',
            'icon'    => '',
            'id'      => 'plugin_carbon_last_2_months_carbon_emission_' . mt_rand(),
            'filters' => [], // TODO: Not implemented yet (is this useful ?)
        ];
        $p = array_merge($default, $params);

        // Force dates filter to 2 last complete months
        // End date is 1st day of current month (excluded)
        $end_date = new DateTime();
        $end_date->setTime(0, 0, 0, 0);
        $end_date->setDate((int) $end_date->format('Y'), (int) $end_date->format('m'), 1); // First day of current month
        $start_date = clone $end_date;
        $start_date = $start_date->sub(new DateInterval("P2M")); // 2 months back from $end_date

        $params['args']['apply_filters']['dates'][0] = $start_date->format('Y-m-d\TH:i:s.v\Z');
        $params['args']['apply_filters']['dates'][1] = $end_date->format('Y-m-d\TH:i:s.v\Z');
        $last_month = $p['data'];

        // Prepare date format
        $date_format = 'Y F';
        $original_date_format = 'Y-m';
        switch ($_SESSION['glpidate_format'] ?? 0) {
            case 0:
                $date_format = 'Y F';
                $original_date_format = 'Y-m';
                break;
            case 1:
            case 2:
                $date_format = 'F Y';
                $original_date_format = 'm-Y';
                break;
        }
        if (isset($last_month['date_interval'][0])) {
            $last_month['date_interval'][0] = DateTime::createFromFormat($original_date_format, $last_month['date_interval'][0])->format($date_format);
            // $last_month['date_interval'][0] = (new DateTime($last_month['date_interval'][0]))->format($date_format);
        }
        if (isset($last_month['date_interval'][1])) {
            // This date is the end boundary excluded, and is the 1st day of a month.
            // We need to find the previous month for display
            // $last_month['date_interval'][1] = (new DateTime($last_month['date_interval'][1]));
            $last_month['date_interval'][1] = DateTime::createFromFormat($original_date_format, $last_month['date_interval'][1]);
            $last_month['date_interval'][1]->setDate((int) $end_date->format('Y'), (int) $end_date->format('m'), 0);
            $last_month['date_interval'][1] = $last_month['date_interval'][1]->format($date_format);
        }

        $last_month_emissions = 0;
        if (count($last_month['series'][0]['data']) > 0) {
            $last_month_emissions = (float) array_pop($last_month['series'][0]['data'])['y'];
        }
        $penultimate_month_emissions = 0;
        $percentage_change = 0;
        if (count($last_month['series'][0]['data']) > 0) {
            $penultimate_month_emissions = (float) array_pop($last_month['series'][0]['data'])['y'];
            if ($last_month_emissions != 0) {
                $percentage_change = (($last_month_emissions - $penultimate_month_emissions) / $last_month_emissions) * 100;
            }
        }
        $last_month_emissions = sprintf(
            '%s %s',
            Toolbox::dynamicRound($last_month_emissions),
            $last_month['series'][0]['unit']
        );
        $penultimate_month_emissions = sprintf(
            '%s %s',
            Toolbox::dynamicRound($penultimate_month_emissions),
            $last_month['series'][0]['unit']
        );

        $url = Type::getCriteriaInfoLink('gwp');
        $tooltip = __('Evaluates the usage carbon emission in CO₂ equivalent during the last 2 months. %s More information %s', 'carbon');
        $tooltip = sprintf($tooltip, '<br /><a target="_blank" href="' . $url . '">', '</a>');
        $tooltip_html = Html::showToolTip($tooltip, [
            'display' => false,
            'applyto' => $p['id'] . '_tip',
        ]);

        $label_color = '#626976';
        $fg_color = GlpiToolbox::getFgColor($p['color']);
        $decrease_color = '#00FF00';
        $increase_color = '#FF0000';
        return TemplateRenderer::getInstance()->render('@carbon/dashboard/monthly-carbon-emission.html.twig', [
            'id' => $p['id'],
            'color' => $p['color'],
            'fg_color' => $fg_color,
            'fg_hover_color'      => GlpiToolbox::getFgColor($p['color'], 15),
            'fg_hover_border'     => GlpiToolbox::getFgColor($p['color'], 30),
            'label_color'         => Toolbox::getAdaptedFgColor($p['color'], $label_color, 4),
            'dark_label_color'    => Toolbox::getAdaptedFgColor($fg_color, $label_color, 4),
            'increase_color'      => Toolbox::getAdaptedFgColor($p['color'], $increase_color),
            'decrease_color'      => Toolbox::getAdaptedFgColor($p['color'], $decrease_color),
            'dark_increase_color' => Toolbox::getAdaptedFgColor($fg_color, $increase_color),
            'dark_decrease_color' => Toolbox::getAdaptedFgColor($fg_color, $decrease_color),
            'last_month_emissions' => $last_month_emissions,
            'last_month' => $last_month['date_interval'][1] ?? '',
            'penultimate_month_emissions' => $penultimate_month_emissions,
            'penultimate_month' => $last_month['date_interval'][0] ?? '',
            'percentage_change' => Html::formatNumber(abs($percentage_change)),
            'variation' => $percentage_change,
            'tooltip_html' => $tooltip_html,
        ]);
    }

    /**
     * display a big number widget with the total carbon emission
     *
     * @param array $params
     * @return string html of the widget
     */
    public static function displayUsageCarbonEmissionYearToDate(array $params = []): string
    {
        $default = [
            'number'  => 0,
            'url'     => '',
            'label'   => '',
            'alt'     => '',
            'color'   => '',
            'icon'    => '',
            'id'      => 'plugin_carbon_total_carbon_emission_ytd_' . mt_rand(),
            'filters' => [], // TODO: Not implemented yet (is this useful ?)
        ];
        $p = array_merge($default, $params);
        [$start_date, $end_date] = (new Toolbox())->yearToLastMonth(new DateTimeImmutable('now'));
        $end_date->setDate((int) $end_date->format('Y'), (int) $end_date->format('m'), 0);
        $date_format = 'Y F';
        switch ($_SESSION['glpidate_format'] ?? 0) {
            case 0:
                $date_format = 'Y F';
                break;
            case 1:
            case 2:
                $date_format = 'F Y';
                break;
        }

        $url = Type::getCriteriaInfoLink('gwp');
        $tooltip = __('Evaluates the usage carbon emission in CO₂ equivalent during the last 12 elapsed months. %s More information %s', 'carbon');
        $tooltip = sprintf($tooltip, '<br /><a target="_blank" href="' . $url . '">', '</a>');
        $tooltip_html = Html::showToolTip($tooltip, [
            'display' => false,
            'applyto' => $p['id'] . '_tip',
        ]);

        $label_color = '#626976';
        $fg_color = GlpiToolbox::getFgColor($p['color']);
        return TemplateRenderer::getInstance()->render('@carbon/dashboard/usage-carbon-emission-last-year.html.twig', [
            'id' => $p['id'],
            'color' => $p['color'],
            'fg_color' => $fg_color,
            'fg_hover_color'   => GlpiToolbox::getFgColor($p['color'], 15),
            'fg_hover_border'  => GlpiToolbox::getFgColor($p['color'], 30),
            'label_color'      => Toolbox::getAdaptedFgColor($p['color'], $label_color, 4),
            'dark_label_color' => Toolbox::getAdaptedFgColor($fg_color, $label_color, 4),
            'number' => $p['number'],
            'date_interval' => [
                $start_date->format($date_format),
                $end_date->format($date_format),
            ],
            'tooltip_html' => $tooltip_html,
        ]);
    }

    public static function displayImpactCriteriaNumber(array $params = []): string
    {
        $default = [
            'url'     => '',
            'label'   => '',
            'alt'     => '',
            'color'   => '',
            'icon'    => '',
            'id'      => 'plugin_carbon_impact_criteria_' . mt_rand(),
            'filters' => [], // TODO: Not implemented yet (is this useful ?)
        ];
        $p = array_merge($default, $params);

        $url = $p['doc_url'];
        $tooltip = $p['tooltip'];
        $tooltip .= '<br /><a target="_blank" href="' . $url . '">'
            . __('More information', 'carbon')
            . '</a>';
        $tooltip_html = Html::showToolTip($tooltip, [
            'display' => false,
            'applyto' => $p['id'] . '_tip',
        ]);

        $label_color = '#626976';
        $fg_color = GlpiToolbox::getFgColor($p['color']);
        return TemplateRenderer::getInstance()->render('@carbon/dashboard/impact-criteria.html.twig', [
            'id' => $p['id'],
            'color' => $p['color'],
            'fg_color' => $fg_color,
            'fg_hover_color'   => GlpiToolbox::getFgColor($p['color'], 15),
            'fg_hover_border'  => GlpiToolbox::getFgColor($p['color'], 30),
            'label_color'      => Toolbox::getAdaptedFgColor($p['color'], $label_color, 4),
            'dark_label_color' => Toolbox::getAdaptedFgColor($fg_color, $label_color, 4),
            'label' => $p['label'],
            'number' => $p['number'],
            'tooltip_html' => $tooltip_html,
            'pictogram_file' => $p['pictogram_file'],
        ]);
    }

    public static function displayUsageAbioticDepletion(array $params = []): string
    {
        $default = [
            'number'  => 0,
            'url'     => '',
            'label'   => '',
            'alt'     => '',
            'color'   => '',
            'icon'    => '',
            'id'      => 'plugin_carbon_usage_abiotic_depletion_' . mt_rand(),
            'filters' => [], // TODO: Not implemented yet (is this useful ?)
        ];
        $p = array_merge($default, $params);

        $url = Type::getCriteriaInfoLink('adp');
        $tooltip = __('Evaluates the consumption of non renewable resources in Antimony equivalent. %s More information %s', 'carbon');
        $tooltip = sprintf($tooltip, '<br /><a target="_blank" href="' . $url . '">', '</a>');
        $tooltip_html = Html::showToolTip($tooltip, [
            'display' => false,
            'applyto' => $p['id'] . '_tip',
        ]);

        $label_color = '#626976';
        $fg_color = GlpiToolbox::getFgColor($p['color']);
        return TemplateRenderer::getInstance()->render('@carbon/dashboard/usage-abiotic-depletion.html.twig', [
            'id' => $p['id'],
            'color' => $p['color'],
            'fg_color' => $fg_color,
            'fg_hover_color'   => GlpiToolbox::getFgColor($p['color'], 15),
            'fg_hover_border'  => GlpiToolbox::getFgColor($p['color'], 30),
            'label_color'      => Toolbox::getAdaptedFgColor($p['color'], $label_color, 4),
            'dark_label_color' => Toolbox::getAdaptedFgColor($fg_color, $label_color, 4),
            'number' => $p['number'],
            'tooltip_html' => $tooltip_html,
        ]);
    }

    /**
     * Show complete staistics for unhandled computers
     *
     * @param array $params
     * @return string
     */
    public static function displayUnhandledComputersRatio(array $params = []): string
    {
        $default = [
            'url'     => '',
            'label'   => '',
            'alt'     => '',
            'color'   => '',
            'icon'    => '',
            'id'      => 'plugin_carbon_unhandled_computers_ratio_' . mt_rand(),
            'filters' => [], // TODO: Not implemented yet (is this useful ?)
        ];
        $p = array_merge($default, $params);

        $p['handled'] = Provider::getHandledAssetCount(Computer::class, true);
        $p['unhandled'] = Provider::getHandledAssetCount(Computer::class, false);
        return TemplateRenderer::getInstance()->render('@carbon/dashboard/unhandled-computers-card.html.twig', [
            'id' => $p['id'],
            'color' => $p['color'],
            'fg_color' => GlpiToolbox::getFgColor($p['color']),
            'fg_hover_color' => GlpiToolbox::getFgColor($p['color'], 15),
            'fg_hover_border' => GlpiToolbox::getFgColor($p['color'], 30),
            'handled' => $p['handled'],
            'unhandled' => $p['unhandled'],
        ]);
    }

    /**
     * Show complete staistics for unhandled monitors
     *
     * @param array $params
     * @return string
     */
    public static function displayUnhandledMonitorsRatio(array $params = []): string
    {
        $default = [
            'url'     => '',
            'label'   => '',
            'alt'     => '',
            'color'   => '',
            'icon'    => '',
            'id'      => 'plugin_carbon_unhandled_monitors_ratio_' . mt_rand(),
            'filters' => [], // TODO: Not implemented yet (is this useful ?)
        ];
        $p = array_merge($default, $params);

        $p['handled'] = Provider::getHandledAssetCount(Monitor::class, true);
        $p['unhandled'] = Provider::getHandledAssetCount(Monitor::class, false);
        return TemplateRenderer::getInstance()->render('@carbon/dashboard/unhandled-monitors-card.html.twig', [
            'id' => $p['id'],
            'color' => $p['color'],
            'fg_color' => GlpiToolbox::getFgColor($p['color']),
            'fg_hover_color' => GlpiToolbox::getFgColor($p['color'], 15),
            'fg_hover_border' => GlpiToolbox::getFgColor($p['color'], 30),
            'handled' => $p['handled'],
            'unhandled' => $p['unhandled'],
        ]);
    }

    /**
     * Show complete staistics for unhandled network equipments
     *
     * @param array $params
     * @return string
     */
    public static function displayUnhandledNetworkEquipmentsRatio(array $params = []): string
    {
        $default = [
            'url'     => '',
            'label'   => '',
            'alt'     => '',
            'color'   => '',
            'icon'    => '',
            'id'      => 'plugin_carbon_unhandled_networkequipments_ratio_' . mt_rand(),
            'filters' => [], // TODO: Not implemented yet (is this useful ?)
        ];
        $p = array_merge($default, $params);

        $p['handled'] = Provider::getHandledAssetCount(NetworkEquipment::class, true);
        $p['unhandled'] = Provider::getHandledAssetCount(NetworkEquipment::class, false);
        return TemplateRenderer::getInstance()->render('@carbon/dashboard/unhandled-network-equipments-card.html.twig', [
            'id' => $p['id'],
            'color' => $p['color'],
            'fg_color' => GlpiToolbox::getFgColor($p['color']),
            'fg_hover_color' => GlpiToolbox::getFgColor($p['color'], 15),
            'fg_hover_border' => GlpiToolbox::getFgColor($p['color'], 30),
            'handled' => $p['handled'],
            'unhandled' => $p['unhandled'],
        ]);
    }

    public static function displayInformationVideo(array $params = []): string
    {
        $default = [
            'url'     => '',
            'label'   => '',
            'alt'     => '',
            'color'   => '',
            'icon'    => '',
            'id'      => 'plugin_carbon_information_video_' . mt_rand(),
            'filters' => [], // TODO: Not implemented yet (is this useful ?)
        ];
        $p = array_merge($default, $params);

        return TemplateRenderer::getInstance()->render('@carbon/dashboard/information-video-card.html.twig', [
            'id' => $p['id'],
            'color' => $p['color'],
            'fg_color' => GlpiToolbox::getFgColor($p['color']),
            'fg_hover_color' => GlpiToolbox::getFgColor($p['color'], 15),
            'fg_hover_border' => GlpiToolbox::getFgColor($p['color'], 30),
        ]);
    }

    public static function displayInformationMethodology(array $params = []): string
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $default = [
            'url'     => '',
            'label'   => '',
            'alt'     => '',
            'color'   => '',
            'icon'    => '',
            'id'      => 'plugin_carbon_information_methodology_' . mt_rand(),
            'filters' => [], // TODO: Not implemented yet (is this useful ?)
        ];
        $p = array_merge($default, $params);

        $icon_url = $CFG_GLPI['root_doc'] . '/plugins/carbon/images/ecology-icon-light.png';
        return TemplateRenderer::getInstance()->render('@carbon/dashboard/information-block.html.twig', [
            'id'              => $p['id'],
            'color'           => $p['color'],
            'fg_color'        => GlpiToolbox::getFgColor($p['color']),
            'fg_hover_color'  => GlpiToolbox::getFgColor($p['color'], 15),
            'fg_hover_border' => GlpiToolbox::getFgColor($p['color'], 30),
            'icon_url'        => $icon_url,
        ]);
    }

    /**
     * Displays a widget with a radar (or web) chart
     *
     * @param array $params
     * @return string
     */
    public static function apexRadar(array $params = []): string
    {
        $default = [
            'data'         => [],
            'label'        => '',
            'alt'          => '',
            'color'        => '',
            'icon'         => '',
            'donut'        => false,
            'half'         => false,
            'use_gradient' => false,
            'limit'        => 99999,
            'filters'      => [],
            'rand'         => mt_rand(),
        ];
        $p = array_merge($default, $params);
        $p['cache_key'] ??= $p['rand'];

        $fg_color      = GlpiToolbox::getFgColor($p['color']);
        $dark_bg_color = GlpiToolbox::getFgColor($p['color'], 80);

        $chart_id = GlpiToolbox::slugify("chart_{$p['cache_key']}");

        $class = "radar";
        $class .= count($p['filters']) > 0 ? " filter-" . implode(' filter-', $p['filters']) : "";

        $indicators = [];
        $values = [];
        foreach ($p['data'] as $itemtype_data) {
            $indicators[] = [
                'name' => $itemtype_data['label'],
                'max'  => 100,
            ];
            $values[] = (float) $itemtype_data['number'];
        }

        $data = [
            'color' => [$fg_color],
            'title' => [
                'text' => $p['label'],
                'textStyle' => [
                    'color' => $fg_color,
                ],
            ],
            'tooltip' => [
                'trigger' => 'item',
            ],
            'radar' => [
                'indicator' => $indicators,
                'shape' => 'polygon',
                'radius' => '65%',
                'center' => ['50%', '55%'],
                'axisName' => [
                    'color' => $fg_color,
                ],
                'axisLine' => [
                    'lineStyle' => [
                        'color' => $fg_color,
                    ],
                ],
                'splitLine' => [
                    'lineStyle' => [
                        'color' => $fg_color,
                    ],
                ],
                'splitArea' => [
                    'show' => false,
                ],
            ],
            'series' => [
                [
                    'type' => 'radar',
                    'areaStyle' => [
                        'opacity' => 0.2,
                    ],
                    'data' => [
                        [
                            'name' => __('Handled percentage', 'carbon'),
                            'value' => $values,
                        ],
                    ],
                ],
            ],
        ];

        $output = TemplateRenderer::getInstance()->render('@carbon/dashboard/apex_radar.html.twig', [
            'chart_id' => $chart_id,
            'class'    => $class,
            'color' => $p['color'],
            'fg_color' => $fg_color,
            'fg_hover_color' => GlpiToolbox::getFgColor($p['color'], 15),
            'fg_hover_border' => GlpiToolbox::getFgColor($p['color'], 30),
            'dark_fg_color' => GlpiToolbox::getFgColor($p['color'], 40),
            'dark_bg_color' => $dark_bg_color,
            'label' => $p['label'],
            'data' => $data,
            'icon' => $p['icon'],
        ]);

        return $output;
    }
}
