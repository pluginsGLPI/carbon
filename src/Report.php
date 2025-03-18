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

namespace GlpiPlugin\Carbon;

use CommonDBTM;
use DateTime;
use DateTimeImmutable;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Carbon\Dashboard\Provider;
use Glpi\Dashboard\Grid as DashboardGrid;
use Html;

class Report extends CommonDBTM
{
    public static $rightname = 'carbon:report';
    protected static $notable   = true;

    public static function getTypeName($nb = 0)
    {
        return _n("Carbon report", "Carbon reports", $nb, 'carbon');
    }

    public static function getIcon(): string
    {
        return 'fa-solid fa-solar-panel';
    }

    public static function getMenuContent()
    {
        $menu = [];

        if (self::canView()) {
            $menu = [
                'title' => Report::getTypeName(0),
                'shortcut' => Report::getMenuShorcut(),
                'page' => Report::getSearchURL(false),
                'icon' => Report::getIcon(),
                'lists_itemtype' => Report::getType(),
                'links' => [
                    'search' => Report::getSearchURL(),
                    'lists' => '',
                ]
            ];
        }

        return $menu;
    }

    public function getRights($interface = 'central')
    {
        $values = parent::getRights();

        return array_intersect_key($values, [READ => true, PURGE => true]);
    }

    public static function showInstantReport(): void
    {
        // $carbon_emission_per_month = Provider::getCarbonEmissionPerMonth();

        ob_start();
        $dashboard = new DashboardGrid('plugin_carbon_board', 33, 0, 'mini_core');
        $dashboard_html = ob_get_clean();

        $total_carbon_emission = Report::getTotalCarbonEmission();

        // TODO: the following block duplicates code in Widget::DisplayMonthlyCarbonEmission()
        {
            $last_month = Report::getCarbonEmissionLastMonth();
            $last_month_emissions = 0;
            if (count($last_month['series'][0]['data']) > 0) {
                $last_month_emissions = array_pop($last_month['series'][0]['data'])['y'];
            }
            $penultimate_month_emissions = 0;
            if (count($last_month['series'][0]['data']) > 0) {
                $penultimate_month_emissions = array_pop($last_month['series'][0]['data'])['y'];
                $percentage_change = (($last_month_emissions - $penultimate_month_emissions) / $last_month_emissions) * 100;
                $comparison_text = '= 0.00 %';
                if ($percentage_change > 0) {
                    $comparison_text = '↑ ' . Html::formatNumber(abs($percentage_change));
                } else if ($percentage_change < 0) {
                    $comparison_text = '↓ ';
                }
            }
            $last_month_emissions .=  ' ' . $last_month['series'][0]['unit'];
            $penultimate_month_emissions .=  ' ' . $last_month['series'][0]['unit'];
        }

        TemplateRenderer::getInstance()->display('@carbon/quick-report.html.twig', [
            'total_carbon_emission' => $total_carbon_emission,
            'last_month_emissions' => $last_month_emissions,
            'last_month' => $last_month['date_interval'][1],
            'penultimate_month_emissions' => $penultimate_month_emissions,
            'penultimate_month' => $last_month['date_interval'][0],
            'variation' => $comparison_text,
            // following has to be deleted
            'dashboard' => $dashboard_html,
            'handled'   => Provider::getHandledComputersCount(),
            'unhandled' => Provider::getUnhandledComputersCount(),
        ]);
    }

    public static function getTotalCarbonEmission(array $params = []): array
    {
        if (!isset($params['args']['apply_filters']['dates'][0]) || !isset($params['args']['apply_filters']['dates'][1])) {
            list($start_date, $end_date) = (new Toolbox())->yearToLastMonth(new DateTimeImmutable('now'));
            $params['args']['apply_filters']['dates'][0] = $start_date->format('Y-m-d\TH:i:s.v\Z');
            $params['args']['apply_filters']['dates'][1] = $end_date->format('Y-m-d\TH:i:s.v\Z');
        } else {
            $start_date = DateTime::createFromFormat('Y-m-d\TH:i:s.v\Z', $params['args']['apply_filters'][0]);
            $end_date   = DateTime::createFromFormat('Y-m-d\TH:i:s.v\Z', $params['args']['apply_filters'][1]);
        }

        $value = Provider::getTotalCarbonEmission($params);

        // Prepare date format
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
        $response = [
            'value'    => $value,
            'date_interval' => [
                $start_date->format($date_format),
                $end_date->format($date_format),
            ],
        ];

        return $response;
    }

    public static function getTotalEmbodiedCarbonEmission(array $params = []): array
    {
        if (!isset($params['args']['apply_filters']['dates'][0]) || !isset($params['args']['apply_filters']['dates'][1])) {
            list($start_date, $end_date) = (new Toolbox())->yearToLastMonth(new DateTimeImmutable('now'));
            $params['args']['apply_filters']['dates'][0] = $start_date->format('Y-m-d\TH:i:s.v\Z');
            $params['args']['apply_filters']['dates'][1] = $end_date->format('Y-m-d\TH:i:s.v\Z');
        } else {
            $start_date = DateTime::createFromFormat('Y-m-d\TH:i:s.v\Z', $params['args']['apply_filters'][0]);
            $end_date   = DateTime::createFromFormat('Y-m-d\TH:i:s.v\Z', $params['args']['apply_filters'][1]);
        }

        $value = Provider::getTotalEmbodiedGwp($params);

        // Prepare date format
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
        $response = [
            'value'    => $value['number'],
            'date_interval' => [
                $start_date->format($date_format),
                $end_date->format($date_format),
            ],
        ];

        return $response;
    }

    public static function getCarbonEmissionPerMonth(array $params = [], array $crit = []): string
    {
        if (!isset($params['args']['apply_filters']['dates'][0]) || !isset($params['args']['apply_filters']['dates'][1])) {
            list($start_date, $end_date) = (new Toolbox())->yearToLastMonth(new DateTimeImmutable('now'));
            $params['args']['apply_filters']['dates'][0] = $start_date->format('Y-m-d\TH:i:s.v\Z');
            $params['args']['apply_filters']['dates'][1] = $end_date->format('Y-m-d\TH:i:s.v\Z');
        } else {
            $start_date = DateTime::createFromFormat('Y-m-d\TH:i:s.v\Z', $params['args']['apply_filters'][0]);
            $end_date   = DateTime::createFromFormat('Y-m-d\TH:i:s.v\Z', $params['args']['apply_filters'][1]);
        }
        $data = Provider::getCarbonEmissionPerMonth($params['args'], $crit);

        // Prepare date format
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
        $data['date_interval'] = [
            $start_date->format($date_format),
            $end_date->format($date_format),
        ];
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
        foreach ($data['data']['series'] as $key => $serie) {
            $apex_data['series'][$key]['data'] = $serie['data'];
            $apex_data['series'][$key]['name'] = $serie['name'];
            $apex_data['series'][$key]['type'] = $serie['type'];
        }
        // $apex_data['series'] = $data['data']['series'];
        $apex_data['labels'] = $data['data']['labels'];
        $apex_data['xaxis']['categories'] = $data['data']['labels'];
        return json_encode($apex_data);
    }

    public static function getCarbonEmissionLastMonth(array $params = []): array
    {
        // Force dates filter to 2 last complete months
        $end_date = new DateTime();
        $end_date->setTime(0, 0, 0, 0);
        $end_date->setDate((int) $end_date->format('Y'), (int) $end_date->format('m'), 0); // Last day of previous month
        $start_date = clone $end_date;
        $start_date->setDate((int) $end_date->format('Y'), (int) $end_date->format('m'), 0);
        $start_date->setDate((int) $start_date->format('Y'), (int) $start_date->format('m'), 1);

        $params['args']['apply_filters']['dates'][0] = $start_date->format('Y-m-d\TH:i:s.v\Z');
        $params['args']['apply_filters']['dates'][1] = $end_date->format('Y-m-d\TH:i:s.v\Z');
        $data = Provider::getCarbonEmissionPerMonth($params['args']);

        // Prepare date format
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
        $data['data']['date_interval'] = [
            $start_date->format($date_format),
            $end_date->format($date_format),
        ];

        return $data['data'];
    }

    public static function getHandledComputersCount(array $params = []): array
    {
        return Provider::getHandledComputersCount($params);
    }
}
