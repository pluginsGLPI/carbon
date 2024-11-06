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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Dashboard\Widget as GlpiDashboardWidget;
use Toolbox;

class Widget extends GlpiDashboardWidget
{
    public static function WidgetTypes(): array
    {
        global $CFG_GLPI;

        $types = [
            'graphpertype' => [
                'label'    => __('Carbon Emission Per Type', 'carbon'),
                'function' => 'GlpiPlugin\\Carbon\\Dashboard\\Widget::DisplayGraphCarbonEmissionPerType',
                'limit'    => true,
                'width'    => 12,
                'height'   => 10,
            ],
            'graphpermonth' => [
                'label'    => __('Carbon Emission Per Month', 'carbon'),
                'function' => 'GlpiPlugin\\Carbon\\Dashboard\\Widget::DisplayGraphCarbonEmissionPerMonth',
                'width'    => 16,
                'height'   => 12,
            ],
            'totalcarbonemission' => [
                'label'    => __('Total Carbon Emission', 'carbon'),
                'function' => 'GlpiPlugin\\Carbon\\Dashboard\\Widget::DisplayTotalCarbonEmission',
                'width'    => 5,
                'height'   => 4,
            ],
            'monthlycarbonemission' => [
                'label'    => __('Monthly Carbon Emission', 'carbon'),
                'function' => 'GlpiPlugin\\Carbon\\Dashboard\\Widget::DisplayMonthlyCarbonEmission',
                'width'    => 5,
                'height'   => 4,
            ],
            'unhandledcomputers' => [
                'label'    => __('Unhandled Computers', 'carbon'),
                'function' => 'GlpiPlugin\\Carbon\\Dashboard\\Widget::DisplayUnhandledComputers',
                'width'    => 5,
                'height'   => 4,
            ],
            'apex_lines' => [
                'label'    => __('Multiple lines', 'carbon'),
                'function' => 'GlpiPlugin\\Carbon\\Dashboard\\Widget::multipleLines',
                'image'    => $CFG_GLPI['root_doc'] . '/pics/charts/line.png',
                'width'    => 5,
                'height'   => 4,
            ]
        ];

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
        $label_class = '';
        $categories  = json_encode($labels);
        $series      = json_encode($series);
        $yaxis       = json_encode($yaxis);
        $stroke      = json_encode($stroke);
        $class       = count($p['filters']) > 0 ? " filter-" . implode(' filter-', $p['filters']) : "";

        return TemplateRenderer::getInstance()->render('@carbon/dashboard/multiple-lines.html.twig', [
            'class'       => $class,
            'label_class' => $label_class,
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

    /**
     * Display a widget with a pie chart
     *
     * @param array $params contains these keys:
     * - array  'data': represents the slices to display
     *    - int    'number': number of the slice
     *    - string 'url': url to redirect when clicking on the slice
     *    - string 'label': title of the slice
     * - string 'label': global title of the widget
     * - string 'alt': tooltip
     * - string 'color': hex color of the widget
     * - string 'icon': font awesome class to display an icon side of the label
     * - string 'id': unique dom identifier
     * - bool   'use_gradient': gradient or generic palette
     * - int    'limit': the number of slices
     * - bool 'donut': do we want a "holed" pie
     * - bool 'gauge': do we want an half pie
     * - array  'filters': array of filter's id to apply classes on widget html
     *
     * @return string html of the widget
     */
    public static function pie(
        array $params = []
    ): string {
        $default = [
            'type'         => 'pie',
            'data'         => [],
            'label'        => '',
            'alt'          => '',
            'color'        => '',
            'icon'         => '',
            'donut'        => false,
            'half'         => false,
            'legend'       => false,
            'use_gradient' => false,
            'limit'        => 99999,
            'filters'      => [],
            'rand'         => mt_rand(),
        ];
        $p = array_merge($default, $params);
        $p['cache_key'] = $p['cache_key'] ?? $p['rand'];

        $chart_id = "chart-{$p['cache_key']}";

        $nb_slices = min($p['limit'], count($p['series']));
        array_splice($p['series'], $nb_slices);
        array_splice($p['labels'], $nb_slices);
        $nb_series = min($p['limit'], count($p['series']));

        $options = ['pie' => [
            'startAngle' => 0,
            'endAngle'   => 360,
            'offsetY'    => 0,
        ]
        ];
        if ($p['donut']) {
            $p['type'] = 'donut';
        }
        if ($p['half']) {
            $options['pie'] = [
                'startAngle' => -90,
                'endAngle'   => 90,
                'offsetY'    => 10,
            ];
        }

        $nodata   = isset($p['data']['nodata']) && $p['data']['nodata'];

        $fg_color      = Toolbox::getFgColor($p['color']);
        $dark_bg_color = Toolbox::getFgColor($p['color'], 80);
        $dark_fg_color = Toolbox::getFgColor($p['color'], 40);

        $palette_style = "";
        if ($p['use_gradient']) {
            $palette_style = self::getCssGradientPalette(
                $p['color'],
                $nb_series,
                "#{$chart_id}"
            );
        }

        $label_class = '';

        // Chart title
        $chart_title = $p['label'];

        // legend
        $show_legend = $p['legend'] ? true : false;

        return TemplateRenderer::getInstance()->render('@carbon/dashboard/pie.html.twig', [
            'no_data'       => $nodata ? 'true' : 'false',
            'chart_type'    => $p['type'],
            'plot_options'  => json_encode($options),
            'chart_id'      => $chart_id,
            'label_class'   => $label_class,
            'icon'          => $p['icon'],
            'label_class'   => $p['label'],
            'color'         => $p['color'],
            'chart_title'   => $chart_title,
            'show_legend'   => $show_legend,
            'palette_style' => $palette_style,
            'fg_color'      => $fg_color,
            'dark_bg_color' => $dark_bg_color,
            'dark_fg_color' => $dark_fg_color,
            'series'        => json_encode($p['series']),
            'labels'        => json_encode($p['labels']),
        ]);
    }

    public static function donut(
        array $params = [],
        array $labels = [],
        array $series = []
    ): string {
        return self::pie(
            array_merge($params, ['donut' => true]),
            $labels,
            $series
        );
    }

    public static function halfDonut(
        array $params = [],
        array $labels = [],
        array $series = []
    ): string {
        return self::pie(
            array_merge($params, ['donut' => true, 'half' => true]),
            $labels,
            $series
        );
    }

    public static function DisplayGraphCarbonEmissionPerType(array $params = []): string
    {
        return self::halfDonut($params);

        return TemplateRenderer::getInstance()->render('@carbon/components/graph-carbon-emission-per-model.html.twig');
    }

    public static function DisplayGraphCarbonEmissionPerMonth(): string
    {
        return TemplateRenderer::getInstance()->render('@carbon/components/graph-carbon-emission-per-month.html.twig');
    }

    public static function DisplayMonthlyCarbonEmission(): string
    {
        return TemplateRenderer::getInstance()->render('@carbon/components/monthly-carbon-emission-card.html.twig');
    }

    public static function DisplayTotalCarbonEmission(): string
    {
        return TemplateRenderer::getInstance()->render('@carbon/components/total-carbon-emission-card.html.twig');
    }


    public static function DisplayUnhandledComputers(): string
    {
        // $params = [
        //     'handled' => Provider::getHandledComputersCount(),
        //     'unhandled' => Provider::getUnhandledComputersCount(),
        // ];
        return TemplateRenderer::getInstance()->render('@carbon/components/unhandled-computers-card.html.twig');
    }
}
