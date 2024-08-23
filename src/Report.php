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

class Report extends CommonDBTM
{
    public static $rightname = 'carbon:report';

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

        return array_intersect_key($values, [READ => true]);
    }

    public static function showInstantReport(): void
    {
        TemplateRenderer::getInstance()->display('@carbon/quick-report.html.twig');
    }

    public static function getTotalCarbonEmission(array $params = []): string
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

        return json_encode($response);
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
        $data = Provider::getCarbonEmissionPerMonth($params, $crit);

        // Prepare date format
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

        return json_encode($data);
    }

    public static function getCarbonEmissionLastMonth(array $params): string
    {
        // Force dates filter to 2 last complete months
        $end_date = new DateTime();
        $end_date->setTime(0, 0, 0, 0);
        $end_date->setDate($end_date->format('Y'), $end_date->format('m'), 0); // Last day of previous month
        $start_date = clone $end_date;
        $start_date->setDate($end_date->format('Y'), $end_date->format('m'), 0);
        $start_date->setDate($start_date->format('Y'), $start_date->format('m'), 1);

        $params['args']['apply_filters']['dates'][0] = $start_date->format('Y-m-d\TH:i:s.v\Z');
        $params['args']['apply_filters']['dates'][1] = $end_date->format('Y-m-d\TH:i:s.v\Z');
        $data = Provider::getCarbonEmissionPerMonth($params);

        // Prepare date format
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

        return json_encode($data);
    }

    public static function getHandledComputersCount(array $params = []): string
    {
        return Provider::getHandledComputersCount($params);
    }
}
