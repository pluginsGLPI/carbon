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

namespace GlpiPlugin\Carbon;

use CommonDBTM;
use DateTime;
use DateTimeImmutable;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Carbon\Dashboard\Provider;
use Glpi\Dashboard\Grid as DashboardGrid;
use Plugin;

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

        return array_intersect_key($values, [READ => true, UPDATE => true, PURGE => true]);
    }

    public static function showInstantReport(): void
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        ob_start();
        $dashboard = new DashboardGrid('plugin_carbon_board', 24, 22, 'mini_core');
        $dashboard->show(true);
        $dashboard_html = ob_get_clean();

        $messages = [];
        if (Config::isDemoMode()) {
            $exit_demo_url = '/plugins/carbon/front/report.php?disable_demo=1';

            // TRANS: %s are replaced with an HTML anchor : <a> and </a>
            $message = sprintf(
                __('Demo mode enabled. The data below are not representative of the assets in the database. %sDisable demo mode%s', 'carbon'),
                '<a href="' . $exit_demo_url . '">',
                '</a>'
            );
            $messages = [
                'infos' => [$message],
            ];
        }

        $header_pic_url = $CFG_GLPI['root_doc'] . '/plugins/carbon/images/illustration_bridge.png';
        $footer_pic_url = $CFG_GLPI['root_doc'] . '/plugins/carbon/images/illustration-footer.png';
        TemplateRenderer::getInstance()->display('@carbon/quick-report.html.twig', [
            'dashboard' => $dashboard_html,
            'messages'  => $messages,
            'header_pic_url' => $header_pic_url,
            'footer_pic_url' => $footer_pic_url,
        ]);
    }

    public static function getUsageCarbonEmission(array $params = []): array
    {
        if (!isset($params['args']['apply_filters']['dates'][0]) || !isset($params['args']['apply_filters']['dates'][1])) {
            list($start_date, $end_date) = (new Toolbox())->yearToLastMonth(new DateTimeImmutable('now'));
            $params['apply_filters']['dates'][0] = $start_date->format('Y-m-d\TH:i:s.v\Z');
            $params['apply_filters']['dates'][1] = $end_date->format('Y-m-d\TH:i:s.v\Z');
        } else {
            $start_date = DateTime::createFromFormat('Y-m-d\TH:i:s.v\Z', $params['args']['apply_filters'][0]);
            $end_date   = DateTime::createFromFormat('Y-m-d\TH:i:s.v\Z', $params['args']['apply_filters'][1]);
        }

        $value = Provider::getUsageCarbonEmission($params)['number'];

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
        // modify the end date to use an included boundary for display
        $end_date->setDate((int) $end_date->format('Y'), (int) $end_date->format('m'), 0);
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

        $value = Provider::getEmbodiedGlobalWarming($params);

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
}
