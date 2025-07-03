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
use DateTime;
use DateTimeImmutable;
use GlpiPlugin\Carbon\Toolbox;
use Monitor;
use NetworkEquipment;
use Session;

class DemoProvider
{
    public static function getEmbodiedGlobalWarming(array $params = []): array
    {
        $value = 616000000;
        $value = Toolbox::getWeight($value) . __('CO₂eq', 'carbon');

        $params['icon'] = 'fa-solid fa-temperature-arrow-up';

        return [
            'number' => $value,
            'label'  => $params['label'],
            'icon'   => $params['icon'],
        ];
    }

    public static function getEmbodiedPrimaryEnergy(array $params = []): array
    {
        $value = 491000000;
        $value = Toolbox::getEnergy($value / 3600);

        $params['icon'] = 'fa-solid fa-fire-flame-simple';

        return [
            'number' => $value,
            'label'  => $params['label'],
            'icon'   => $params['icon'],
        ];
    }

    public static function getEmbodiedAbioticDepletion(array $params = [], array $crit = []): array
    {
        $default_params = [
            'label' => __('Embodied abiotic depletion potential', 'carbon'),
            'icon'  => 'fa-solid fa-temperature-arrow-up',
        ];
        $params = array_merge($default_params, $params);

        $value = 12.748;
        $value = Toolbox::getWeight($value) . __('Sbeq', 'carbon');

        return [
            'number'     => $value,
            'label'      => $params['label'],
            'icon'       => $params['icon'],
        ];
    }

    public static function getUsageAbioticDepletion(array $params = [], array $crit = []): array
    {
        $default_params = [
            'label' => __('Usage abiotic depletion potential', 'carbon'),
            'icon'  => 'fa-solid fa-temperature-arrow-up',
        ];
        $params = array_merge($default_params, $params);

        $value = 2.86;
        $value = Toolbox::getWeight($value) . __('Sbeq', 'carbon');

        return [
            'number'     => $value,
            'label'      => $params['label'],
            'icon'       => $params['icon'],
        ];
    }

    public static function getUsageCarbonEmissionPerMonth(array $params = [], array $crit = []): array
    {
        $default_params = [
            'label' => __('Usage carbon emission per month', 'carbon'),
            'icon'  => 'fas fa-computer',
        ];
        $params = array_merge($default_params, $params);

        list($start_date, $end_date) = (new Toolbox())->yearToLastMonth(new DateTimeImmutable('now'));
        $params['args']['apply_filters']['dates'][0] = $start_date->format('Y-m-d\TH:i:s.v\Z');
        $params['args']['apply_filters']['dates'][1] = $end_date->format('Y-m-d\TH:i:s.v\Z');

        $data = [
            'series' => [
                0 => [
                    'name' => __('Carbon emission', 'carbon') . ' (' . 'Kg' . __('CO₂eq', 'carbon') . ')',
                    'data' => [
                        ['x' => '2024-04', 'y' => 1.881],
                        ['x' => '2024-05', 'y' => 1.647],
                        ['x' => '2024-06', 'y' => 1.666],
                        ['x' => '2024-07', 'y' => 1.966],
                        ['x' => '2024-08', 'y' => 2.063],
                        ['x' => '2024-09', 'y' => 2.305],
                        ['x' => '2024-10', 'y' => 2.085],
                        ['x' => '2024-11', 'y' => 4.424],
                        ['x' => '2024-12', 'y' => 3.871],
                        ['x' => '2025-01', 'y' => 4.224],
                        ['x' => '2025-02', 'y' => 4.076],
                        ['x' => '2025-03', 'y' => 3.428],
                    ],
                    'unit' => 'KgCO₂eq',
                ],
                1 => [
                    'name' => __('Consumed energy', 'carbon') . ' (' . 'KWh' . ')',
                    'data' => [
                        ['x' => '2024-04', 'y' => 123.444],
                        ['x' => '2024-05', 'y' => 127.746],
                        ['x' => '2024-06', 'y' => 122.040],
                        ['x' => '2024-07', 'y' => 127.746],
                        ['x' => '2024-08', 'y' => 127.044],
                        ['x' => '2024-09', 'y' => 122.742],
                        ['x' => '2024-10', 'y' => 127.746],
                        ['x' => '2024-11', 'y' => 122.742],
                        ['x' => '2024-12', 'y' => 127.044],
                        ['x' => '2025-01', 'y' => 127.746],
                        ['x' => '2025-02', 'y' => 114.840],
                        ['x' => '2025-03', 'y' => 126.342],
                    ],
                    'unit' => 'KWh',
                ],
            ],
            'xaxis' => [
                'categories' => [
                    '2024-04',
                    '2024-05',
                    '2024-06',
                    '2024-07',
                    '2024-08',
                    '2024-09',
                    '2024-10',
                    '2024-11',
                    '2024-12',
                    '2025-01',
                    '2025-02',
                    '2025-03',
                ],
            ],
            'labels' => [
                '2024-04',
                '2024-05',
                '2024-06',
                '2024-07',
                '2024-08',
                '2024-09',
                '2024-10',
                '2024-11',
                '2024-12',
                '2025-01',
                '2025-02',
                '2025-03',
            ],
        ];

        return [
            'data'  => $data,
            'label' => $params['label'],
            'icon'  => $params['icon'],
        ];
    }

    public static function getUsageCarbonEmissionlastTwoMonths(array $params = [])
    {
        $end_date = new DateTime();
        $end_date->setTime(0, 0, 0, 0);
        $end_date->setDate((int) $end_date->format('Y'), (int) $end_date->format('m'), 1); // First day of current month
        $start_date = clone $end_date;
        $start_date = $start_date->sub(new DateInterval("P2M")); // 2 months back from $end_date

        $params['args']['apply_filters']['dates'][0] = $start_date->format('Y-m-d\TH:i:s.v\Z');
        $params['args']['apply_filters']['dates'][1] = $end_date->format('Y-m-d\TH:i:s.v\Z');

        // Prepare date format
        $date_format = 'Y F';
        switch ($_SESSION['glpidate_format'] ?? 0) {
            case 0:
                $date_format = 'Y-m';
                break;
            case 1:
            case 2:
                $date_format = 'm-Y';
                break;
        }

        $data = [
            'series' => [
                0 => [
                    'name' => __('Carbon emission', 'carbon') . ' (' . 'Kg' . __('CO₂eq', 'carbon') . ')',
                    'data' => [
                        [
                            'x' => '2025-02',
                            'y' => 4.076,
                        ], [
                            'x' => '2025-03',
                            'y' => 3.428,
                        ],
                    ],
                    'unit' => 'KgCO₂eq',
                ],
                1 => [
                    'name' => __('Consumed energy', 'carbon') . ' (' . 'KWh' . ')',
                    'data' => [
                        [
                            'x' => 114.840,
                            'y' => '2025-02'
                        ], [
                            'x' => 126.342,
                            'y' => '2025-03'
                        ],
                    ],
                    'unit' => 'KWh',
                ],
            ],
            'xaxis' => [
                'categories' => [
                    '2025-02',
                    '2025-03',
                ],
            ],
            'labels' => [
                '2025-02',
                '2025-03',
            ],
            'date_interval' => [
                $start_date->format($date_format),
                $end_date->format($date_format),
            ]
        ];

        return [
            'data'  => $data,
            'icon'  => 'fas fa-computer',
        ];
    }

    public static function getHandledAssetCount(string $itemtype, bool $handled, array $params = []): array
    {
        $itemtype_name = $itemtype::getTypeName(Session::getPluralNumber());
        $itemtype_name = strtolower($itemtype_name);
        $label = $handled ?
            __("plugin carbon - handled %s", 'carbon')
            : __("plugin carbon - unhandled %s", 'carbon');
        $default_params = [
            'label' => sprintf($label, $itemtype_name),
            'icon'  => '',
        ];
        $params = array_merge($default_params, $params);

        $count = 0;
        switch ($itemtype) {
            case Computer::class:
                $count = $handled ? 15 : 3;
                break;
            case Monitor::class:
                $count = $handled ? 18 : 2;
                break;
            case NetworkEquipment::class:
                $count = $handled ? 3 : 0;
                break;
        }

        return [
            'number' => $count,
            'url'    => '',
            'label'  => $params['label'],
            'icon'   => $params['icon'],
        ];
    }

    public static function getHandledAssetsCounts(array $params = []): array
    {
        $default_params = [
            'label' => __('plugin carbon - handled assets ratio', 'carbon'),
            'icon'  => '',
        ];
        $params = array_merge($default_params, $params);

        if (count($params['args']['itemtypes'] ?? []) === 0) {
            $itemtypes = PLUGIN_CARBON_TYPES;
        } else {
            $itemtypes = array_intersect(PLUGIN_CARBON_TYPES, $params['args']['itemtypes']);
        }

        $data = [
            'labels' => [],
            'series' => []
        ];
        foreach ($itemtypes as $itemtype) {
            $itemtype_name = $itemtype::getTypeName(Session::getPluralNumber());
            $itemtype_name = strtolower($itemtype_name);

            $handled = self::getHandledAssetCount($itemtype, true, $params);
            $unhandled = self::getHandledAssetCount($itemtype, false, $params);

            $data['labels'][] = $itemtype_name;
            $data['series'][0]['name'] = __('Handled', 'carbon');
            $data['series'][0]['data'][] = [
                'value' => $handled['number'],
                'url'   => $handled['url']
            ];
            $data['series'][1]['name'] = __('Unhandled', 'carbon');
            $data['series'][1]['data'][] = [
                'value' => $unhandled['number'],
                'url'   => $unhandled['url'],
            ];
        }

        return [
            'data' => $data,
            'label' => $params['label'],
            'icon'  => $params['icon'],
        ];
    }

    /**
     * Get the usage carbon emission of the 12 last elapsed months
     *
     * @param array $params
     * @return array
     */
    public static function getUsageCarbonEmissionYearToDate(array $params = []): array
    {
        $default_params = [
            'label' => __('plugin carbon - Usage carbon emission', 'carbon'),
            'icon'  => 'fa-solid fa-temperature-arrow-up',
        ];
        $params = array_merge($default_params, $params);

        return [
            'number' => '33.7&nbsp;KgCO₂eq',
            'label'  => $params['label'],
            'icon'   => $params['icon'],
        ];
    }

    public static function getSumUsageEmissionsPerModel(array $params = [], array $where = [])
    {
        $data = [
            'series' => [
                150.753,
                128,
                80,
                75,
                50,
            ],
            'labels' => [
                'Model 1 (10 Computer)',
                'Model 2 (8 Computer)',
                'Model 3 (11 Computer)',
                'Model 4 (7 Computer)',
                'Model 5 (4 Computer)',
            ],
            'url' => [
                '',
            ],
            'unit' => 'g CO₂eq',
        ];

        return [
            'data' => $data
        ];
    }
}
