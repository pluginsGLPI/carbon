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

use CommonDropdown;
use CommonDBTM;
use CommonGLPI;
use DBmysql;
use DbUtils;
use Location;
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
        $this->addStandardTab(CarbonIntensitySource::class, $tabs, $options);
        return $tabs;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$withtemplate) {
            $nb = 0;
            /** @var CommonDBTM $item */
            switch ($item->getType()) {
                case CarbonIntensitySource::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = (new DbUtils())->countElementsInTable(
                            CarbonIntensitySource_Zone::getTable(),
                            [CarbonIntensitySource::getForeignKeyField() => $item->getID()]
                        );
                    }
                    return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
            }
        }

        return '';
    }

    public function prepareInputForAdd($input)
    {
        // Check that historizable source is not a fallback
        if (!$this->checkSourceProvidesHistory($input)) {
            return [];
        }
        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        unset($input['name']);

        // Check that historizable source is not a fallback
        if (!$this->checkSourceProvidesHistory($input)) {
            return [];
        }
        return $input;
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        /** @var CommonDBTM $item  */
        switch ($item->getType()) {
            case CarbonIntensitySource::class:
                CarbonIntensitySource_Zone::showForSource($item);
        }

        return true;
    }

    public function getAdditionalFields()
    {
        return [
            [
                'name'   => 'plugin_carbon_carbonintensitysources_id_historical',
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
            'table'         => CarbonIntensitySource::getTable(),
            'field'         => 'name',
            'name'          => __('Data source for historical calculation', 'carbon'),
            'datatype'      => 'dropdown',
            'joinparams'         => [
                'beforejoin'    => [
                    'table'         => CarbonIntensitySource_Zone::getTable(),
                    'joinparams'    => [
                        'jointype'      => 'child',
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'            => SearchOptions::HISTORICAL_DATA_DL_ENABLED,
            'table'         => CarbonIntensitySource_Zone::getTable(),
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
     * Validate if the source given in the input provides an history
     *
     * @param array $input
     * @return boolean
     */
    private function checkSourceProvidesHistory(array $input): bool
    {
        if (!isset($input['plugin_carbon_carbonintensitysources_id_historical'])) {
            return true;
        }

        if (CarbonIntensitySource::isNewID($input['plugin_carbon_carbonintensitysources_id_historical'])) {
            return true;
        }
        $source = new CarbonIntensitySource();
        if (!$source->getFromDB($input['plugin_carbon_carbonintensitysources_id_historical'])) {
            // source does not exists
            return false;
        }

        return ($source->fields['is_fallback'] == 0);
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
        if (!isset($this->fields['plugin_carbon_carbonintensitysources_id_historical'])) {
            return false;
        }
        $source = new CarbonIntensitySource();
        if (!$source->getFromDB($this->fields['plugin_carbon_carbonintensitysources_id_historical'])) {
            // source does not exists
            return false;
        }

        return true;
    }
}
