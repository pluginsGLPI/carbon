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

use CommonDBRelation;
use CommonGLPI;
use CommonDBTM;
use CronTask;
use DBmysql;
use GlpiPlugin\Carbon\Application\View\Extension\DataHelpersExtension;
use Glpi\Application\View\TemplateRenderer;
use Html;

class CarbonIntensitySource_Zone extends CommonDBRelation
{
    public static $itemtype_1 = CarbonIntensitySource::class; // Type ref or field name (must start with itemtype)
    public static $items_id_1 = 'plugin_carbon_carbonintensitysources_id'; // Field name
    public static $checkItem_1_Rights = self::HAVE_SAME_RIGHT_ON_ITEM;

    public static $itemtype_2 = Zone::class; // Type ref or field name (must start with itemtype)
    public static $items_id_2 = 'plugin_carbon_zones_id'; // Field name
    public static $checkItem_2_Rights = self::HAVE_SAME_RIGHT_ON_ITEM;

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() === CarbonIntensitySource::class) {
            return self::createTabEntry(Zone::getTypeName(), 0);
        }
        return self::createTabEntry(CarbonIntensitySource::getTypeName(), 0);
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'          => '5',
            'table'       => CarbonIntensitySource::getTable(),
            'field'       => 'name',
            'name'        => __('Source name', 'carbon'),
            'datatype'    => 'dropdown',
        ];

        $tab[] = [
            'id'          => '6',
            'table'       => Zone::getTable(),
            'field'       => 'name',
            'name'        => __('Zone name', 'carbon'),
            'datatype'    => 'dropdown',
        ];

        $tab[] = [
            'id'          => '7',
            'table'       => CarbonIntensitySource::getTable(),
            'field'       => 'is_download_enabled',
            'name'        => __('Download enabled', 'carbon'),
            'datatype'    => 'bool',
        ];

        $tab[] = [
            'id'          => '8',
            'table'       => self::getTable(),
            'field'       => 'code',
            'name'        => __('Code', 'carbon'),
            'datatype'    => 'string',
        ];

        return $tab;
    }

    public static function showForSource(CommonDBTM $item)
    {
        /** @var DBmysql $DB */
        global $DB;

        $item_id = $item->getID();

        if (!$item->can($item_id, READ)) {
            return;
        }
        $canedit = $item->canEdit($item_id);

        $source_table = CarbonIntensitySource::getTable();
        $zone_table = Zone::getTable();
        $source_zone_table = self::getTable();
        $iterator = $DB->request([
            'SELECT' => [
                $zone_table => 'name',
                $source_zone_table => ['id', 'is_download_enabled'],
                CarbonIntensitySource::getTableField('name') . ' AS historical_source_name',
                $source_table => 'is_fallback'
            ],
            'FROM' => $source_zone_table,
            'INNER JOIN' => [
                $source_table => [
                    'FKEY' => [
                        $source_zone_table => 'plugin_carbon_carbonintensitysources_id',
                        $source_table => 'id',
                    ],
                ],
                $zone_table => [
                    'FKEY' => [
                        $source_zone_table => 'plugin_carbon_zones_id',
                        $zone_table => 'id',
                    ],
                ],
            ],
            'WHERE' => [
                CarbonIntensitySource::getTableField('id') => $item_id,
            ],
            'ORDER'     => ['name ASC'],
        ]);

        $total = $iterator->count();

        $entries = [];
        foreach ($iterator as $data) {
            $is_download_enabled = __('Not downloadable', 'carbon') . Html::showToolTip(__('This is a fallback source, there is no real-time data available', 'carbon'), ['display' => false]);
            if ($data['is_fallback'] == 0) {
                $is_download_enabled = self::getToggleLink($data['id'], $data['is_download_enabled']);
            }
            $entries[] = [
                'itemtype'               => CarbonIntensitySource::class,
                'id'                     => $item->getID(),
                'name'                   => $data['name'],
                'historical_source_name' => $data['historical_source_name'],
                'is_download_enabled'    => $is_download_enabled,
            ];
        }

        $renderer = TemplateRenderer::getInstance();
        $extensions = $renderer->getEnvironment()->getExtensions();
        if (!isset($extensions[DataHelpersExtension::class])) {
            $renderer->getEnvironment()->addExtension(new DataHelpersExtension());
        }
        $renderer->display('@carbon/pages/CarbonIntensitySource/tab_zone.html.twig', [
            'is_tab' => true,
            'nopager' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'name' => __('Name'),
                'is_download_enabled' => __('Download enabled', 'carbon'),
                'historical_source_name' => __('Source for historical', 'carbon'),
            ],
            'formatters' => [
                'is_download_enabled' => 'raw_html',
            ],
            'footers' => [
                ['', '', '', __('Total'), $total, '']
            ],
            'footer_class' => 'fw-bold',
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . mt_rand(),
            ],
            'automatic_actions_url' => CronTask::getSearchURL(),
        ]);

        if (count($entries) !== 0) {
            // At least 1 entry then add JS to toggle the state of zones
            echo Html::scriptBlock('
                var plugin_carbon_toggleZone = function (id) {
                    fetch(CFG_GLPI["root_doc"] + "/" + GLPI_PLUGINS_PATH.carbon + "/ajax/toggleZoneDownload.php?id=" + id).then(response => {
                        if (response.status === 200) {
                            reloadTab();
                        }
                    });
                };
            ');
        }
    }

    public static function showForZone(CommonDBTM $item)
    {
        /** @var DBmysql $DB */
        global $DB;

        $item_id = $item->getID();

        if (!$item->can($item_id, READ)) {
            return;
        }
        $canedit = $item->canEdit($item_id);

        $source_table = CarbonIntensitySource::getTable();
        $zone_table = Zone::getTable();
        $source_zone_table = self::getTable();
        $iterator = $DB->request([
            'SELECT' => [
                $source_table => 'name',
                $source_zone_table => ['id', 'is_download_enabled']
            ],
            'FROM' => $source_zone_table,
            'INNER JOIN' => [
                $source_table => [
                    'FKEY' => [
                        $source_zone_table => 'plugin_carbon_carbonintensitysources_id',
                        $source_table => 'id',
                    ],
                ],
                $zone_table => [
                    'FKEY' => [
                        $source_zone_table => 'plugin_carbon_zones_id',
                        $zone_table => 'id',
                    ],
                ],
            ],
            'WHERE' => [
                Zone::getTableField('id') => $item_id,
            ],
            'ORDER'     => ['name ASC'],
        ]);

        $total = $iterator->count();
        $entries = [];
        foreach ($iterator as $data) {
            $entries[] = [
                'itemtype'   => CarbonIntensitySource::class,
                'id'         => $item->getID(),
                'name'       => $data['name'],
                'is_download_enabled' => self::getToggleLink($data['id'], $data['is_download_enabled']),
            ];
        }

        $renderer = TemplateRenderer::getInstance();
        $extensions = $renderer->getEnvironment()->getExtensions();
        if (!isset($extensions[DataHelpersExtension::class])) {
            $renderer->getEnvironment()->addExtension(new DataHelpersExtension());
        }
        $renderer->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nopager' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'name' => __('Name'),
                'is_download_enabled' => __('Download enabled', 'carbon'),
            ],
            'formatters' => [
                'is_download_enabled' => 'raw_html',
            ],
            'footers' => [
                ['', '', '', __('Total'), $total, '']
            ],
            'footer_class' => 'fw-bold',
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . mt_rand(),
            ]
        ]);

        if (count($entries) !== 0) {
            // At least 1 entry then add JS to toggle the state of zones
            echo Html::scriptBlock('
                var plugin_carbon_toggleZone = function (id) {
                    fetch(CFG_GLPI["root_doc"] + "/" + GLPI_PLUGINS_PATH.carbon + "/ajax/toggleZoneDownload.php?id=" + id).then(response => {
                        if (response.status === 200) {
                            reloadTab();
                        }
                    });
                };
            ');
        }
    }

    /**
     * Get the zone code from a source name and a zone name
     *
     * @param string $source_name
     * @param string $zone_name
     * @return string
     */
    public function getFromDbBySourceAndZone(string $source_name, string $zone_name): ?string
    {
        /** @var DBmysql $DB */
        global $DB;

        $zone_table = Zone::getTable();
        $source_table = CarbonIntensitySource::getTable();
        $source_zone_table = self::getTable();
        $request = [
            'SELECT' => CarbonIntensitySource_Zone::getTableField('code'),
            'FROM'   => $source_zone_table,
            'INNER JOIN' => [
                $source_table => [
                    'ON' => [
                        $source_table => 'id',
                        $source_zone_table => CarbonIntensitySource::getForeignKeyField(),
                    ]
                ],
                $zone_table => [
                    'ON' => [
                        $zone_table => 'id',
                        $source_zone_table => Zone::getForeignKeyField(),
                    ]
                ]
            ],
            'WHERE' => [
                CarbonIntensitySource::getTableField('name') => $source_name,
                Zone::getTableField('name') => $zone_name,
            ],
            'LIMIT' => '1'
        ];
        $iterator = $DB->request($request);
        $zone_code = $iterator->current()['code'] ?? null;

        return $zone_code;
    }

    protected static function getToggleLink(int $zone_id, ?string $state)
    {
        $state = $state == 0 ? __('No') : __('Yes');
        $link = '<a href="javascript:void(0)" onclick="plugin_carbon_toggleZone(' . $zone_id . ')" title="' . __('Enable / Disable', 'carbon') . '">' . $state . '</a>';
        return $link;
    }

    public function toggleZone(?bool $state = null): bool
    {
        // Check if the source is a fallback source
        $source = new CarbonIntensitySource();
        $source->getFromDB($this->fields['plugin_carbon_carbonintensitysources_id']);
        if ($source->fields['is_fallback'] === 1) {
            // Fallback sources cannot be toggled
            return false;
        }
        if ($state === null) {
            $state = $this->fields['is_download_enabled'];
            $state = $state == 0 ? 1 : 0;
        }

        $input = [
            'id' => $this->getID(),
            'is_download_enabled' => $state
        ];
        return $this->update($input) !== false;
    }
}
