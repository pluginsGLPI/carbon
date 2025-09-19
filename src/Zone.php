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

use CommonDropdown;
use CommonDBTM;
use CommonGLPI;
use DateTime;
use DBmysql;
use DbUtils;
use Glpi\Toolbox\Sanitizer;
use Location;
use LogicException;
use Session;

/**
 * Usage profile of a computer
 */
class Zone extends CommonDropdown
{
    public static function getTypeName($nb = 0)
    {
        return _n("Carbon intensity zone", "Carbon intensity zones", $nb, 'carbon');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canUpdate(): bool
    {
        return true;
    }

    public static function canDelete(): bool
    {
        return false;
    }

    public static function canPurge(): bool
    {
        return false;
    }

    public function defineTabs($options = [])
    {
        $tabs = parent::defineTabs($options);
        $this->addStandardTab(Source::class, $tabs, $options);
        return $tabs;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$withtemplate) {
            $nb = 0;
            /** @var CommonDBTM $item */
            switch ($item->getType()) {
                case Source::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = (new DbUtils())->countElementsInTable(
                            Source_Zone::getTable(),
                            [Source::getForeignKeyField() => $item->getID()]
                        );
                    }
                    return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
            }
        }

        return '';
    }

    public function prepareInputForUpdate($input)
    {
        unset($input['name']);

        return $input;
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        /** @var CommonDBTM $item  */
        switch ($item->getType()) {
            case Source::class:
                Source_Zone::showForSource($item);
        }

        return true;
    }

    public function getAdditionalFields()
    {
        return [
            [
                'name'   => 'plugin_carbon_sources_id_historical',
                'label'  => __('Data source for historical calculation', 'carbon'),
                'type'   => 'dropdownValue',
                'list'   => true
            ]
        ];
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'            => SearchOptions::HISTORICAL_DATA_SOURCE,
            'table'         => Source::getTable(),
            'field'         => 'name',
            'name'          => __('Data source for historical calculation', 'carbon'),
            'datatype'      => 'dropdown',
            'joinparams'         => [
                'beforejoin'    => [
                    'table'         => Source_Zone::getTable(),
                    'joinparams'    => [
                        'jointype'      => 'child',
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'            => SearchOptions::HISTORICAL_DATA_DL_ENABLED,
            'table'         => Source_Zone::getTable(),
            'field'         => 'is_download_enabled',
            'name'          => __('Download enabled', 'carbon'),
            'datatype'      => 'bool',
        ];

        return $tab;
    }

    /**
     * Get a zone by a location criteria
     *
     * @param CommonDBTM $item
     * @return Zone|null
     */
    public static function getByLocation(CommonDBTM $item): ?Zone
    {
        /** @var DBmysql $DB */
        global $DB;

        if ($item->isNewItem()) {
            return null;
        }

        if (($item->fields['country'] ?? '') == '' && ($item->fields['state'] ?? '') == '') {
            return null;
        }

        // TODO: support translations
        $location_table = Location::getTable();
        $zone_table = Zone::getTable();
        $request = [
            'SELECT' => Zone::getTableField('id'),
            'FROM'   => $zone_table,
            'INNER JOIN' => [
                $location_table => [
                    'FKEY' => [
                        $location_table => 'state',
                        $zone_table => 'name',
                    ],
                ],
            ],
            'WHERE'  => [
                Location::getTableField('id') => $item->getID(),
            ]
        ];
        $iterator = $DB->request($request);

        if ($iterator->count() !== 1) {
            // no state found, fallback to country
            $request['INNER JOIN'][$location_table]['FKEY'][$location_table] = 'country';
            $iterator = $DB->request($request);
            if ($iterator->count() !== 1) {
                // Give up
                return null;
            }
        }

        $zone_id = $iterator->current()['id'];
        $zone = Zone::getById($zone_id);
        if ($zone === false) {
            return null;
        }

        return $zone;
    }

    /**
     * Get a zone by an asset criteria
     *
     * @param CommonDBTM $item
     * @return Zone|null
     */
    public static function getByAsset(CommonDBTM $item): ?Zone
    {
        /** @var DBmysql $DB */
        global $DB;

        if (!isset($item->fields[Location::getForeignKeyField()])) {
            return null;
        }

        if ($item->isNewItem()) {
            return null;
        }

        // TODO: support translations
        $location_table = Location::getTable();
        $zone_table = Zone::getTable();
        $item_table = $item::getTable();
        $state_field = Location::getTableField('state');
        $request = [
            'SELECT' => Zone::getTableField('id'),
            'FROM'   => $zone_table,
            'INNER JOIN' => [
                $location_table => [
                    'FKEY' => [
                        $location_table => 'state',
                        $zone_table => 'name',
                    ],
                ],
                $item_table => [
                    'FKEY' => [
                        $item_table => 'locations_id',
                        $location_table => 'id',
                    ],
                ],
            ],
            'WHERE'  => [
                $state_field => ['<>', ''],
                $item::getTableField('id') => $item->getID(),
            ]
        ];
        $iterator = $DB->request($request);

        if ($iterator->count() !== 1) {
            // no state found, fallback to country
            $request['INNER JOIN'][$location_table]['FKEY'][$location_table] = 'country';
            unset($request['WHERE'][$state_field]);
            $request['WHERE'][Location::getTableField('country')] = ['<>', ''];
            $iterator = $DB->request($request);
            if ($iterator->count() !== 1) {
                // Give up
                return null;
            }
        }

        $zone_id = $iterator->current()['id'];
        $zone = Zone::getById($zone_id);
        if ($zone === false) {
            return null;
        }

        return $zone;
    }

    /**
     * Check if the zone has a historical data source
     *
     * @return bool
     */
    public function hasHistoricalData(): bool
    {
        if ($this->isNewItem()) {
            return false;
        }
        if (!isset($this->fields['plugin_carbon_sources_id_historical'])) {
            return false;
        }
        $source = new Source();
        if (!$source->getFromDB($this->fields['plugin_carbon_sources_id_historical'])) {
            // source does not exists
            return false;
        }

        return $source->fields['is_fallback'] === 0;
    }

    /**
     * Get the zone the asset belongs to
     * Location's country must match a zone name
     *
     * @param CommonDBTM $item
     * @param null|DateTime $date Date for which the zone must be found
     * @param bool $use_country Do not search by state first
     * @return bool
     */
    public function getByItem(CommonDBTM $item, ?DateTime $date = null, bool $use_country = false): bool
    {
        if ($item->isNewItem()) {
            return false;
        }

        // TODO: use date to find where was the asset at the given date
        if ($date === null) {
            $item_table = $item->getTable();
            $location_table = Location::getTable();
            $zone_table = Zone::getTable();

            $request = [
                'INNER JOIN' => [
                    $location_table => [
                        'FKEY' => [
                            $zone_table => 'name',
                            $location_table => 'state',
                        ],
                    ],
                    $item_table => [
                        'FKEY' => [
                            $item_table => Location::getForeignKeyField(),
                            $location_table => 'id',
                        ],
                    ]
                ],
                'WHERE' => [
                    $item_table . '.id' => $item->getID()
                ]
            ];
            $found = false;
            if (!$use_country) {
                $found = $this->getFromDBByRequest($request);
            }

            if ($found) {
                return true;
            }

            // no state found, fallback to country
            $request['INNER JOIN'][$location_table]['FKEY'][$location_table] = 'country';
            return $this->getFromDBByRequest($request);
        }

        throw new LogicException('Not implemented yet');
    }
}
