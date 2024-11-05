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
        return self::cardNumberProvider($params, "unhandled computers", self::getUnhandledComputersCount($params));
    }

    public static function cardHandledComputersCountProvider(array $params = [])
    {
        return self::cardNumberProvider($params, "unhandled computers", self::getHandledComputersCount($params));
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

    public static function getUnhandledComputersCount(array $params = [])
    {
        $unit = ''; // This is a count, no unit

        $total = Provider::getUnhandledComputersCount($params);
        if ($total['number'] === null) {
            return 'N/A';
        }

        return strval($total['number']) . " $unit";
    }

    public static function getHandledComputersCount(array $params = [])
    {
        $unit = ''; // This is a count, no unit

        $total = Provider::getHandledComputersCount($params);
        if ($total['number'] === null) {
            return 'N/A';
        }

        return strval($total['number']) . " $unit";
    }

    /**
     * Returns total carbon emission per computer model.
     *
     * @return array of:
     *   - float  'number': total carbon emission of the model
     *   - string 'url': url to redirect when clicking on the slice
     *   - string 'label': name of the computer model
     */
    public static function getTotalCarbonEmissionPerModel(array $params = [])
    {
        $default_params = [
            'label' => __('Carbon Emission per model', 'carbon'),
            'icon'  => "fas fa-computer",
            'color' => '#ea9999',
        ];
        $params = array_merge($default_params, $params);

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

        $data = array_merge($data, Provider::getSumEmissionsPerModel());
        return $data;

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
