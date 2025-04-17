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
use Html;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Dashboard\Widget as GlpiDashboardWidget;
use GlpiPlugin\Carbon\Report;
use Monitor;
use NetworkEquipment;
use Toolbox;

class Widget extends GlpiDashboardWidget
{
    public static function WidgetTypes(): array
    {
        $types = [
            // Total carbon emission year to last complete month
            'totalcarbonemission_ytd' => [
                'label'    => __('Total Carbon Emission', 'carbon'),
                'function' => self::class . '::DisplayTotalCarbonEmissionYearToDate',
                'width'    => 6,
                'height'   => 3,
            ],
            'totalcarbonemission_two_last_months' => [
                'label'    => __('Monthly Carbon Emission', 'carbon'),
                'function' => self::class . '::DisplayMonthlyCarbonEmission',
                'width'    => 6,
                'height'   => 3,
            ],
            'information_video' => [
                'label'    => __('Environmental impact information video', 'carbon'),
                'function' => self::class . '::DisplayInformationVideo',
                'width'    => 6,
                'height'   => 3,
            ],
            'methodology_information' => [
                'label'    => __('Methodology information', 'carbon'),
                'function' => self::class . '::DisplayInformationMethodology',
                'width'    => 6,
                'height'   => 3,
            ],
            'most_gwp_impacting_computer_models' => [
                'label'    => __('Biggest monthly averaged carbon emission per model', 'carbon'),
                'function' => self::class . '::DisplayGraphUsageCarbonEmissionPerModel',
                'width'    => 6,
                'height'   => 3,
                'limit'    => true,
            ],
            'usage_gwp_monthly' => [
                'label'    => __('Carbon Emission Per month', 'carbon'),
                'function' => self::class . '::DisplayGraphUsageCarbonEmissionPerMonth',
                'width'    => 16,
                'height'   => 12,
            ],
        ];
        if (in_array(Computer::class, PLUGIN_CARBON_TYPES)) {
            $types += [
                'unhandledComputersRatio' => [
                    'label'    => __('Unhandled Computers', 'carbon'),
                    'function' => self::class . '::DisplayUnhandledComputersRatio',
                    'width'    => 5,
                    'height'   => 4,
                ],
            ];
        }
        if (in_array(Monitor::class, PLUGIN_CARBON_TYPES)) {
            $types += [
                'unhandledMonitorsRatio' => [
                    'label'    => __('Unhandled Monitors', 'carbon'),
                    'function' => self::class . '::DisplayUnhandledMonitorsRatio',
                    'width'    => 5,
                    'height'   => 4,
                ],
            ];
        }
        if (in_array(NetworkEquipment::class, PLUGIN_CARBON_TYPES)) {
            $types += [
                'unhandledNetworkequipmentsRatio' => [
                    'label'    => __('Unhandled Network equipments', 'carbon'),
                    'function' => self::class . '::DisplayUnhandledNetworkEquipmentsRatio',
                    'width'    => 5,
                    'height'   => 4,
                ],
            ];
        }
            // 'graphpertype' => [
            //     'label'    => __('Carbon Emission Per Type', 'carbon'),
            //     'function' => self::class . '::DisplayGraphCarbonEmissionPerType',
            //     'limit'    => true,
            //     'width'    => 12,
            //     'height'   => 10,
            // ],
            // 'totalcarbonemission' => [
            //     'label'    => __('Total Carbon Emission', 'carbon'),
            //     'function' => self::class . '::DisplayTotalCarbonEmission',
            //     'width'    => 5,
            //     'height'   => 4,
            // ],
            // 'monthlycarbonemission' => [
            //     'label'    => __('Monthly Carbon Emission', 'carbon'),
            //     'function' => self::class . '::DisplayMonthlyCarbonEmission',
            //     'width'    => 5,
            //     'height'   => 4,
            // ],
            // 'apex_lines' => [
            //     'label'    => __('Multiple lines', 'carbon'),
            //     'function' => self::class . '::multipleLines',
            //     'image'    => $CFG_GLPI['root_doc'] . '/pics/charts/line.png',
            //     'width'    => 5,
            //     'height'   => 4,
            // ]
            // 'apex_pie' => [
            //     'label'    => __('Pie', 'carbon'),
            //     'function' => self::class . '::apex_pie',
            //     'image'    => $CFG_GLPI['root_doc'] . '/pics/charts/line.png',
            //     'width'    => 5,
            //     'height'   => 4,
            // ],

        return $types;
    }

    /**
     * Display a widget with a multiple line chart (with multiple series)
     * @see self::getLinesGraph for params
     *
     * @return string html
     */
    public static function multipleLines(array $params = []): string
    {
        return self::getLinesGraph(
            array_merge($params, [
                'legend'   => true,
                'multiple' => true,
            ]),
            $params['data']['labels'],
            $params['data']['series']
        );
    }

    /**
     * Display a widget with a lines chart
     *
     * @param array $params contains these keys:
     * - array  'data': represents the lines to display
     *    - string 'url': url to redirect when clicking on the line
     *    - string 'label': title of the line
     *    - int     'number': number of the line
     * - string 'label': global title of the widget
     * - string 'alt': tooltip
     * - string 'color': hex color of the widget
     * - string 'icon': font awesome class to display an icon side of the label
     * - string 'id': unique dom identifier
     * - bool   'area': do we want an area chart
     * - bool   'legend': do we display a legend for the graph
     * - bool   'use_gradient': gradient or generic palette
     * - bool   'point_labels': display labels (for values) directly on graph
     * - int    'limit': the number of lines
     * - array  'filters': array of filter's id to apply classes on widget html
     * @param array $labels title of the lines (if a single array is given, we have a single line graph)
     * @param array $series values of the line (if a single array is given, we have a single line graph)
     *
     * @return string html of the widget
     */
    private static function getLinesGraph(
        array $params = [],
        array $labels = [],
        array $series = []
    ): string {
        $defaults = [
            'data'         => [],
            'label'        => '',
            'alt'          => '',
            'color'        => '',
            'icon'         => '',
            'area'         => false,
            'legend'       => false,
            'multiple'     => false,
            'use_gradient' => false,
            'point_labels' => false,
            'limit'        => 99999,
            'filters'      => [],
            'rand'         => mt_rand(),
        ];

        $p = array_merge($defaults, $params);
        $p['cache_key'] = $p['cache_key'] ?? $p['rand'];

        $nb_series = count($series);
        $nb_labels = min($p['limit'], count($labels));
        array_splice($labels, 0, -$nb_labels);
        if ($p['multiple']) {
            foreach ($series as &$serie) {
                if (isset($serie['data'])) {
                    array_splice($serie['data'], 0, -$nb_labels);
                }
            }
            unset($serie);
        } else {
            array_splice($series[0], 0, -$nb_labels);
        }

        // Chart title
        $chart_title = $p['label'];

        // Line or area ?
        $chart_type = $p['area'] ? 'area' : 'line';

        // legend
        $show_legend = $p['legend'] ? true : false;

        // Series and y axis
        $yaxis = [];
        $stroke = [];
        foreach ($series as $key => $serie) {
            $yaxis[$key] = [
                'title' => [
                    'text' => $serie['name'],
                ],
                'opposite' => ($key % 2 > 0),
            ];
            $stroke['width'][] = (($serie['type'] ?? 'line') == 'line') ? 4 : 0;
        }

        $fg_color        = Toolbox::getFgColor($p['color']);
        $line_color      = Toolbox::getFgColor($p['color'], 10);
        $dark_bg_color   = Toolbox::getFgColor($p['color'], 80);
        $dark_fg_color   = Toolbox::getFgColor($p['color'], 40);
        $dark_line_color = Toolbox::getFgColor($p['color'], 90);

        $chart_id        = "chart-{$p['cache_key']}";

        $palette_style = "";
        if (!$p['multiple'] || $p['use_gradient']) {
            $palette_style = self::getCssGradientPalette($p['color'], $nb_series, "#{$chart_id}");
        }

        $chart_id = 'chart_' . $p['cache_key'];
        $class = "line";
        $class .= $p['area'] ? " area" : "";
        $class .= $p['multiple'] ? " multiple" : "";
        $class .= count($p['filters']) > 0 ? " filter-" . implode(' filter-', $p['filters']) : "";
        $categories  = json_encode($labels);
        $series      = json_encode($series);
        $yaxis       = json_encode($yaxis);
        $stroke      = json_encode($stroke);
        $class       = count($p['filters']) > 0 ? " filter-" . implode(' filter-', $p['filters']) : "";

        return TemplateRenderer::getInstance()->render('@carbon/dashboard/multiple-lines.html.twig', [
            'class'       => $class,
            'chart_id'    => $chart_id,
            'chart_type'  => $chart_type,
            'chart_title' => $chart_title,
            'show_legend' => $show_legend,
            'series'      => $series,
            'categories'  => $categories,
            'yaxis'       => $yaxis,
            'icon'        => $p['icon'],
            'label_class' => $p['label'],
            'color'       => $p['color'],
            'palette_style' => $palette_style,
            'fg_color'    => $fg_color,
            'line_color'  => $line_color,
            'dark_bg_color' => $dark_bg_color,
            'dark_fg_color' => $dark_fg_color,
            'dark_line_color' => $dark_line_color,
            'stroke'          => $stroke,
        ]);
    }

    // /**
    //  * Display a widget with a pie chart
    //  *
    //  * @param array $params contains these keys:
    //  * - array  'data': represents the slices to display
    //  *    - int    'number': number of the slice
    //  *    - string 'url': url to redirect when clicking on the slice
    //  *    - string 'label': title of the slice
    //  * - string 'label': global title of the widget
    //  * - string 'alt': tooltip
    //  * - string 'color': hex color of the widget
    //  * - string 'icon': font awesome class to display an icon side of the label
    //  * - string 'id': unique dom identifier
    //  * - bool   'use_gradient': gradient or generic palette
    //  * - int    'limit': the number of slices
    //  * - bool 'donut': do we want a "holed" pie
    //  * - bool 'gauge': do we want an half pie
    //  * - array  'filters': array of filter's id to apply classes on widget html
    //  *
    //  * @return string html of the widget
    //  */
    // public static function pie(
    //     array $params = []
    // ): string {
    //     $default = [
    //         'type'         => 'pie',
    //         'data'         => [],
    //         'label'        => '',
    //         'alt'          => '',
    //         'color'        => '',
    //         'icon'         => '',
    //         'donut'        => false,
    //         'half'         => false,
    //         'legend'       => false,
    //         'use_gradient' => false,
    //         'limit'        => 99999,
    //         'filters'      => [],
    //         'rand'         => mt_rand(),
    //     ];
    //     $p = array_merge($default, $params);
    //     $p['cache_key'] = $p['cache_key'] ?? $p['rand'];

    //     $chart_id = "chart-{$p['cache_key']}";

    //     $nb_slices = min($p['limit'], count($p['series']));
    //     array_splice($p['series'], $nb_slices);
    //     array_splice($p['labels'], $nb_slices);
    //     $nb_series = min($p['limit'], count($p['series']));

    //     $options = ['pie' => [
    //         'startAngle' => 0,
    //         'endAngle'   => 360,
    //         'offsetY'    => 0,
    //     ]
    //     ];
    //     if ($p['donut']) {
    //         $p['type'] = 'donut';
    //     }
    //     if ($p['half']) {
    //         $options['pie'] = [
    //             'startAngle' => -90,
    //             'endAngle'   => 90,
    //             'offsetY'    => 10,
    //         ];
    //     }

    //     $nodata   = isset($p['data']['nodata']) && $p['data']['nodata'];

    //     $fg_color      = Toolbox::getFgColor($p['color']);
    //     $dark_bg_color = Toolbox::getFgColor($p['color'], 80);
    //     $dark_fg_color = Toolbox::getFgColor($p['color'], 40);

    //     $palette_style = "";
    //     if ($p['use_gradient']) {
    //         $palette_style = self::getCssGradientPalette(
    //             $p['color'],
    //             $nb_series,
    //             "#{$chart_id}"
    //         );
    //     }

    //     // Chart title
    //     $chart_title = $p['label'];

    //     // legend
    //     $show_legend = $p['legend'] ? true : false;

    //     return TemplateRenderer::getInstance()->render('@carbon/dashboard/pie.html.twig', [
    //         'no_data'       => $nodata ? 'true' : 'false',
    //         'chart_type'    => $p['type'],
    //         'plot_options'  => json_encode($options),
    //         'chart_id'      => $chart_id,
    //         'icon'          => $p['icon'],
    //         'label_class'   => $p['label'],
    //         'color'         => $p['color'],
    //         'chart_title'   => $chart_title,
    //         'show_legend'   => $show_legend,
    //         'palette_style' => $palette_style,
    //         'fg_color'      => $fg_color,
    //         'dark_bg_color' => $dark_bg_color,
    //         'dark_fg_color' => $dark_fg_color,
    //         'series'        => json_encode($p['series']),
    //         'labels'        => json_encode($p['labels']),
    //     ]);
    // }

    // public static function donut(
    //     array $params = [],
    //     array $labels = [],
    //     array $series = []
    // ): string {
    //     return self::pie(
    //         array_merge($params, ['donut' => true]),
    //         $labels,
    //         $series
    //     );
    // }

    // public static function halfDonut(
    //     array $params = [],
    //     array $labels = [],
    //     array $series = []
    // ): string {
    //     return self::pie(
    //         array_merge($params, ['donut' => true, 'half' => true]),
    //         $labels,
    //         $series
    //     );
    // }

    // public static function DisplayGraphCarbonEmissionPerType(array $params = []): string
    // {
    //     return self::halfDonut($params);
    // }

    public static function DisplayGraphUsageCarbonEmissionPerMonth(array $params = [], array $crit = []): string
    {
        $default = [
            'url'     => '',
            'label'   => '',
            'alt'     => '',
            'color'   => '#FFFFFF',
            'icon'    => '',
            'id'      => 'plugin_carbon_usage_carbon_emissions_' . mt_rand(),
            'filters' => [], // TODO: Not implemented yet (is this useful ?)
        ];
        $p = array_merge($default, $params);

        $apex_data = [
            'chart' => [
                'type' => 'line',
                'height' => 350,
            ],
            'colors' => ['#BBDA50', '#A00'],
            // 'title' => [
                // 'text' => __('Consumed energy and carbon emission', 'carbon'),
            // ],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => false,
                    'columnWidth' => '55%',
                    'endingShape' => 'rounded'
                ],
            ],
            'dataLabels' => [
                'enabled' => false,
                'enabledOnSeries' => [0, 1],
                'style' => [
                    'colors' => ['#145161', '#800'],
                ],
            ],
            'labels' => [],
            'stroke' => [
                'width' => [0, 4],
                'curve' => 'smooth'
            ],
            'series' => [
                [
                    'name' =>  __('Carbon emission', 'carbon'),
                    'type' => 'bar',
                    'data' => []
                ],
                [
                    'name' => __('Consumed energy', 'carbon'),
                    'type' => 'line',
                    'data' => []
                ],
            ],
            'xaxis' => [
                'categories' => []
            ],
            'yaxis' => [
                [
                    'title' => ['text' => __('Carbon emission', 'carbon')],
                ], [
                    'opposite' => true,
                    'title' => ['text' => __('Consumed energy', 'carbon')],
                ]
            ],
            'markers' => [
                'size' => [3, 3],
            ],
            'tooltip' => [
                'enabled' => true,
            ],
        ];
        $data = Provider::getUsageCarbonEmissionPerMonth($params, $crit);
        foreach ($data['series'] as $key => $serie) {
            $apex_data['series'][$key]['name'] = $serie['name'];
            $apex_data['series'][$key]['data'] = $serie['data'];
            $apex_data['series'][$key]['type'] = $serie['type'];
        }
        // $apex_data['series'] = $data['data']['series'];
        $apex_data['labels'] = $data['labels'];
        $apex_data['xaxis']['categories'] = $data['labels'];

        return TemplateRenderer::getInstance()->render('@carbon/components/graph-carbon-emission-per-month.html.twig', [
            'id' => $p['id'],
            'color' => $p['color'],
            'fg_color' => Toolbox::getFgColor($p['color']),
            'fg_hover_color' => Toolbox::getFgColor($p['color'], 15),
            'fg_hover_border' => Toolbox::getFgColor($p['color'], 30),
            'data' => $apex_data,
        ]);
    }

    public static function DisplayGraphUsageCarbonEmissionPerModel(array $params = []): string
    {
        $default = [
            'url'     => '',
            'label'   => '',
            'alt'     => '',
            'color'   => '',
            'icon'    => '',
            'id'      => 'plugin_usage_carbon_emissions_per_model_' . mt_rand(),
            'filters' => [], // TODO: Not implemented yet (is this useful ?)
        ];
        $p = array_merge($default, $params);

        $data = [
            'colors' => ['#146151', '#FEEC5C', '#BBDA50', '#F78343', '#97989C'],
            'chart' => [
                'type' => 'donut',
            ],
            'plotOptions' => [
                'pie' => [
                    'startAngle' => -90,
                    'endAngle' => 90,
                    'offsetY' => 10
                ]
            ],
            'grid' => [
                'padding' => [
                    'bottom' => -80
                ]
            ],
            'responsive' => [[
                'breakpoint' => 480,
                'options' => [
                    'chart' => [
                        'width' => 200
                    ],
                    'legend' => [
                        'position' => 'bottom'
                    ]
                ]
            ]
            ],
            'subtitle' => [
                'style' => []
            ],
            'series' => [],
            'labels' => [],
        ];
        $data = array_merge($data, Provider::getSumUsageEmissionsPerModel($params));

        return TemplateRenderer::getInstance()->render('@carbon/components/graph-carbon-emission-per-model.html.twig', [
            'id' => $p['id'],
            'color' => $p['color'],
            'fg_color' => Toolbox::getFgColor($p['color']),
            'fg_hover_color' => Toolbox::getFgColor($p['color'], 15),
            'fg_hover_border' => Toolbox::getFgColor($p['color'], 30),
            'data' => $data,
        ]);
    }

    public static function DisplayMonthlyCarbonEmission(array $params = []): string
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

        $last_month = Report::getCarbonEmissionLastMonth();
        $last_month_emissions = 0;
        if (count($last_month['series'][0]['data']) > 0) {
            $last_month_emissions = array_pop($last_month['series'][0]['data'])['y'];
        }
        $penultimate_month_emissions = 0;
        $comparison_text = '';
        if (count($last_month['series'][0]['data']) > 0) {
            $penultimate_month_emissions = array_pop($last_month['series'][0]['data'])['y'];
            $percentage_change = (($last_month_emissions - $penultimate_month_emissions) / $last_month_emissions) * 100;
            $comparison_text = '= 0.00 %';
            if ($percentage_change > 0) {
                $comparison_text = '↑ ' . Html::formatNumber(abs($percentage_change)) . ' %';
            } else if ($percentage_change < 0) {
                $comparison_text = '↓ ' . Html::formatNumber(abs($percentage_change)) . ' %';
            }
        }
        $last_month_emissions .=  ' ' . $last_month['series'][0]['unit'];
        $penultimate_month_emissions .=  ' ' . $last_month['series'][0]['unit'];
        return TemplateRenderer::getInstance()->render('@carbon/components/monthly-carbon-emission-card.html.twig', [
            'id' => $p['id'],
            'color' => $p['color'],
            'fg_color' => Toolbox::getFgColor($p['color']),
            'fg_hover_color' => Toolbox::getFgColor($p['color'], 15),
            'fg_hover_border' => Toolbox::getFgColor($p['color'], 30),
            'last_month_emissions' => $last_month_emissions,
            'last_month' => $last_month['xaxis']['categories'][1],
            'penultimate_month_emissions' => $penultimate_month_emissions,
            'penultimate_month' => $last_month['xaxis']['categories'][0],
            'variation' => $comparison_text,
        ]);
    }

    /**
     * display a big number widget with the total carbon emission
     *
     * @param array $params
     * @return string html of the widget
     */
    public static function DisplayTotalCarbonEmissionYearToDate(array $params = []): string
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

        $p['number'] = Report::getTotalCarbonEmission();
        return TemplateRenderer::getInstance()->render('@carbon/components/total-carbon-emission-card.html.twig', [
            'id' => $p['id'],
            'color' => $p['color'],
            'fg_color' => Toolbox::getFgColor($p['color']),
            'fg_hover_color' => Toolbox::getFgColor($p['color'], 15),
            'fg_hover_border' => Toolbox::getFgColor($p['color'], 30),
            'number' => $p['number']['value'],
            'date_interval' => $p['number']['date_interval'],
        ]);
    }

    /**
     * Show complete staistics for unhandled computers
     *
     * @param array $params
     * @return string
     */
    public static function DisplayUnhandledComputersRatio(array $params = []): string
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

        $p['handled'] = Provider::getHandledComputersCount();
        $p['unhandled'] = Provider::getUnhandledComputersCount();
        return TemplateRenderer::getInstance()->render('@carbon/components/unhandled-computers-card.html.twig', [
            'id' => $p['id'],
            'color' => $p['color'],
            'fg_color' => Toolbox::getFgColor($p['color']),
            'fg_hover_color' => Toolbox::getFgColor($p['color'], 15),
            'fg_hover_border' => Toolbox::getFgColor($p['color'], 30),
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
    public static function DisplayUnhandledMonitorsRatio(array $params = []): string
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

        $p['handled'] = Provider::getHandledMonitorsCount();
        $p['unhandled'] = Provider::getUnhandledMonitorsCount();
        return TemplateRenderer::getInstance()->render('@carbon/components/unhandled-monitors-card.html.twig', [
            'id' => $p['id'],
            'color' => $p['color'],
            'fg_color' => Toolbox::getFgColor($p['color']),
            'fg_hover_color' => Toolbox::getFgColor($p['color'], 15),
            'fg_hover_border' => Toolbox::getFgColor($p['color'], 30),
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
    public static function DisplayUnhandledNetworkEquipmentsRatio(array $params = []): string
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

        $p['handled'] = Provider::getHandledNetworkEquipmentsCount();
        $p['unhandled'] = Provider::getUnhandledNetworkEquipmentsCount();
        return TemplateRenderer::getInstance()->render('@carbon/components/unhandled-network-equipments-card.html.twig', [
            'id' => $p['id'],
            'color' => $p['color'],
            'fg_color' => Toolbox::getFgColor($p['color']),
            'fg_hover_color' => Toolbox::getFgColor($p['color'], 15),
            'fg_hover_border' => Toolbox::getFgColor($p['color'], 30),
            'handled' => $p['handled'],
            'unhandled' => $p['unhandled'],
        ]);
    }

    public static function DisplayInformationVideo(array $params = []): string
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

        return TemplateRenderer::getInstance()->render('@carbon/components/information-video-card.html.twig', [
            'id' => $p['id'],
            'color' => $p['color'],
            'fg_color' => Toolbox::getFgColor($p['color']),
            'fg_hover_color' => Toolbox::getFgColor($p['color'], 15),
            'fg_hover_border' => Toolbox::getFgColor($p['color'], 30),
        ]);
    }

    public static function DisplayInformationMethodology(array $params = []): string
    {
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

        return TemplateRenderer::getInstance()->render('@carbon/components/information-block.html.twig', [
            'id' => $p['id'],
            'color' => $p['color'],
            'fg_color' => Toolbox::getFgColor($p['color']),
            'fg_hover_color' => Toolbox::getFgColor($p['color'], 15),
            'fg_hover_border' => Toolbox::getFgColor($p['color'], 30),
        ]);
    }
}
