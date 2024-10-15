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

class Widget
{
    public static function WidgetTypes(): array
    {

        $types = [
            'graphpertype' => [
                'label'    => __('Carbon Emission Per Type', 'carbon'),
                'function' => 'GlpiPlugin\Carbon\Dashboard\Widget::DisplayGraphCarbonEmissionPerType',
                'width'    => '12',
                'height'   => '10',
            ],
            'graphpermonth' => [
                'label'    => __('Carbon Emission Per Month', 'carbon'),
                'function' => 'GlpiPlugin\Carbon\Dashboard\Widget::DisplayGraphCarbonEmissionPerMonth',
                'width'    => '16',
                'height'   => '12',
            ],
            'totalcarbonemission' => [
                'label'    => __('Total Carbon Emission', 'carbon'),
                'function' => 'GlpiPlugin\Carbon\Dashboard\Widget::DisplayTotalCarbonEmission',
                'width'    => '5',
                'height'   => '4',
            ],
            'monthlycarbonemission' => [
                'label'    => __('Monthly Carbon Emission', 'carbon'),
                'function' => 'GlpiPlugin\Carbon\Dashboard\Widget::DisplayMonthlyCarbonEmission',
                'width'    => '5',
                'height'   => '4',
            ],
            'unhandledcomputers' => [
                'label'    => __('Unhandled Computers', 'carbon'),
                'function' => 'GlpiPlugin\Carbon\Dashboard\Widget::DisplayUnhandledComputers',
                'width'    => '5',
                'height'   => '4',
            ],
        ];

        return $types;
    }

    public static function DisplayGraphCarbonEmissionPerType(): string
    {

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

        return TemplateRenderer::getInstance()->render('@carbon/components/unhandled-computers-card.html.twig');
    }
}
