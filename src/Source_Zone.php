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
use Location as GlpiLocation;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use Html;
use InvalidArgumentException;

class Source_Zone extends CommonDBRelation
{
    public static $itemtype_1 = Source::class; // Type ref or field name (must start with itemtype)
    public static $items_id_1 = 'plugin_carbon_sources_id'; // Field name
    public static $checkItem_1_Rights = self::HAVE_SAME_RIGHT_ON_ITEM;

    public static $itemtype_2 = Zone::class; // Type ref or field name (must start with itemtype)
    public static $items_id_2 = 'plugin_carbon_zones_id'; // Field name
    public static $checkItem_2_Rights = self::HAVE_SAME_RIGHT_ON_ITEM;

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() === Source::class) {
            return self::createTabEntry(Zone::getTypeName(), 0);
        }
        return self::createTabEntry(Source::getTypeName(), 0);
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'          => '5',
            'table'       => Source::getTable(),
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
            'table'       => Source::getTable(),
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

        $source_table = Source::getTable();
        $zone_table = Zone::getTable();
        $source_zone_table = self::getTable();
        $iterator = $DB->request([
            'SELECT' => [
                $zone_table => 'name',
                $source_zone_table => ['id', 'is_download_enabled'],
                Source::getTableField('name') . ' AS historical_source_name',
                $source_table => 'fallback_level'
            ],
            'FROM' => $source_zone_table,
            'INNER JOIN' => [
                $source_table => [
                    'FKEY' => [
                        $source_zone_table => 'plugin_carbon_sources_id',
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
                Source::getTableField('id') => $item_id,
            ],
            'ORDER'     => ['name ASC'],
        ]);

        $total = $iterator->count();

        $entries = [];
        foreach ($iterator as $data) {
            $is_download_enabled = __('Not downloadable', 'carbon') . Html::showToolTip(__('This is a fallback source, there is no real-time data available', 'carbon'), ['display' => false]);
            if ($data['fallback_level'] == 0) {
                $is_download_enabled = self::getToggleLink($data['id'], $data['is_download_enabled']);
            }
            $entries[] = [
                'itemtype'               => Source::class,
                'id'                     => $item->getID(),
                'name'                   => $data['name'],
                'historical_source_name' => $data['historical_source_name'],
                'is_download_enabled'    => $is_download_enabled,
            ];
        }

        $renderer = TemplateRenderer::getInstance();
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
                    fetch(CFG_GLPI["root_doc"] + "/plugins/carbon/ajax/toggleZoneDownload.php?id=" + id).then(response => {
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

        $source_table = Source::getTable();
        $source_fk = Source::getForeignKeyField();
        $zone_table = Zone::getTable();
        $zone_fk = Zone::getForeignKeyField();
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
                        $source_zone_table => $source_fk,
                        $source_table => 'id',
                    ],
                ],
                $zone_table => [
                    'FKEY' => [
                        $source_zone_table => $zone_fk,
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
                'itemtype'   => Source::class,
                'id'         => $item->getID(),
                'name'       => $data['name'],
                'is_download_enabled' => self::getToggleLink($data['id'], $data['is_download_enabled']),
            ];
        }

        $renderer = TemplateRenderer::getInstance();
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
                    fetch(CFG_GLPI["root_doc"] + "/plugins/carbon/ajax/toggleZoneDownload.php?id=" + id).then(response => {
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
     * @return bool
     */
    public function getFromDbBySourceAndZone(string $source_name, string $zone_name): bool
    {
        /** @var DBmysql $DB */
        global $DB;

        $zone_table = Zone::getTable();
        $source_table = Source::getTable();
        $source_zone_table = self::getTable();
        $request = [
            'SELECT' => Source_Zone::getTable() . '.id',
            'FROM'   => $source_zone_table,
            'INNER JOIN' => [
                $source_table => [
                    'ON' => [
                        $source_table => 'id',
                        $source_zone_table => Source::getForeignKeyField(),
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
                Source::getTableField('name') => $source_name,
                Zone::getTableField('name') => $zone_name,
            ],
            'LIMIT' => '1'
        ];
        $iterator = $DB->request($request);
        if ($iterator->count() !== 1) {
            return false;
        }
        $id = $iterator->current()['id'];
        return $this->getFromDB($id);
    }

    /**
     * get the source_zone with fallback source for the given source_zone
     * excluding Ember - Energy Institute source
     *
     * @param Source_Zone $source_zone realtime source-zone
     * @return bool
     */
    public function getFallbackFromDB(Source_Zone $source_zone): bool
    {
        $source_zone_table = Source_Zone::getTable();
        $source_table = Source::getTable();
        $source_fk = Source::getForeignKeyField();
        $request = [
            'SELECT' => 'id',
            'FROM' => $source_zone_table,
            'INNER JOIN' => [
                // the source_zone row matching the $source_zone argument
                $source_zone_table . ' AS realtime_sources_zones' => [
                    'ON' => [
                        $source_zone_table => 'plugin_carbon_zones_id',
                        'realtime_sources_zones' => 'plugin_carbon_zones_id',
                        ['AND' => [$source_zone_table . '.id' => ['<>', new QueryExpression('`realtime_sources_zones`.`id`')]]]
                    ]
                ],
                // The source associated to the source_zone argument (to find the fallback_level)
                $source_table . ' AS realtime_source' => [
                    'ON' => [
                        'realtime_sources_zones' => 'plugin_carbon_sources_id',
                        'realtime_source' => 'id',
                    ]
                ],
                // The fallback source (to compare its fallback_level against the other source)
                $source_table => [
                    'ON' => [
                        $source_table => 'id',
                        $source_zone_table => $source_fk,
                        [
                            'AND' => [
                                Source::getTableField('fallback_level') => ['>', new QueryExpression('`realtime_source`.`fallback_level`')],
                            ]
                        ]
                    ]
                ],
            ],
            'WHERE' => [
                'realtime_sources_zones.id' => $source_zone->getID(),
            ],
            'ORDER' => Source::getTableField('fallback_level'),
            'LIMIT' => 1
        ];

        return $this->getFromDBByRequest($request);
    }

    /**
     * Get HTML link to enable / disable the download of carbon intensity data for a source and a zone
     *
     * @param integer $zone_id
     * @param string|null $state
     * @return string
     */
    protected static function getToggleLink(int $zone_id, ?string $state): string
    {
        $state = $state == 0 ? __('No') : __('Yes');
        $link = '<a href="javascript:void(0)" onclick="plugin_carbon_toggleZone(' . $zone_id . ')" title="' . __('Enable / Disable', 'carbon') . '">' . $state . '</a>';
        return $link;
    }

    /**
     * Sets or toggles the download for a zone
     *
     * @param boolean|null $state if not null, don't toggle and force the state of the download
     * @return boolean true if the update succeeded
     */
    public function toggleZone(?bool $state = null): bool
    {
        // Check if the source is a fallback source
        $source = new Source();
        $source->getFromDB($this->fields['plugin_carbon_sources_id']);
        if ($source->fields['fallback_level'] > 0) {
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

    /**
     * Get a source_zone by a item criteria.
     * If the item is a location, get the source_zone by location relation
     * If the item is something else, get the source_zone by its associated location
     *
     * @param CommonDBTM $item
     * @return bool
     */
    public function getFromDbByItem(CommonDBTM $item): bool
    {
        if ($item->isNewItem()) {
            return false;
        }

        // Prepare WHERE clause depending of the type of the item
        $where = [];
        if ($item->getType() === GlpiLocation::class) {
            $where = [
                Location::getTableField('locations_id') => $item->getID(),
            ];
        } elseif (isset($item->fields['locations_id'])) {
            $where = [
                Location::getTableField('locations_id') => $item->fields['locations_id'],
            ];
        } else {
            throw new InvalidArgumentException('Invalid item');
        }

        $location_table = Location::getTable();
        $source_zone_table = Source_Zone::getTable();
        $request = [
            'INNER JOIN' => [
                $location_table => [
                    'FKEY' => [
                        $location_table => 'plugin_carbon_sources_zones_id',
                        $source_zone_table => 'id'
                    ]
                ],
            ],
            'WHERE' => $where,
        ];

        return $this->getFromDBByRequest($request);
    }

    /**
     * get or create an item
     *
     * @param array $params
     * @param array $where
     * @return self|null
     */
    public function getOrCreate(array $params, array $where): ?self
    {
        if (!$this->getFromDBByCrit($where)) {
            $this->add(array_merge($where, $params));
            return $this;
        }

        $this->update(array_merge($where, $params, ['id' => $this->getID()]));
        return $this;
    }

    /**
     * Show gaps in carbon intensities stored for the source and zone of the current instance
     *
     * @return void
     */
    public function showGaps()
    {
        $canedit = false;
        $oldest_asset_date = (new Toolbox())->getOldestAssetDate();
        $carbon_intensity = new CarbonIntensity();
        $zone_id = $this->fields['plugin_carbon_zones_id'];
        $entries = $carbon_intensity->findGaps(
            $this->fields['plugin_carbon_sources_id'],
            $this->fields['plugin_carbon_zones_id'],
            $oldest_asset_date
        );
        $total = $entries->count();

        $renderer = TemplateRenderer::getInstance();
        $template = <<<TWIG
        {% import "components/form/fields_macros.html.twig" as fields %}
        {{ fields.smallTitle(zone_name) }}
TWIG;
        $zone = Zone::getById($zone_id);
        echo $renderer->renderFromStringTemplate($template, ['zone_name' => $zone->fields['name']]);
        $renderer->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nopager' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'start' => __('Start'),
                'end' => __('End', 'carbon'),
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
    }
}
