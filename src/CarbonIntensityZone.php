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
use DBUtils;
use Location;
use Session;

/**
 * Usage profile of a computer
 */
class CarbonIntensityZone extends CommonDropdown
{
    public static function getTypeName($nb = 0)
    {
        return _n("Carbon intensity zone", "Carbon intensity zones", $nb, 'carbon');
    }

    public static function canCreate()
    {
        return false;
    }

    public static function canUpdate()
    {
        return true;
    }

    public static function canDelete()
    {
        return false;
    }

    public static function canPurge()
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
                        $nb = (new DBUtils())->countElementsInTable(
                            CarbonIntensitySource_CarbonIntensityZone::getTable(),
                            [CarbonIntensitySource::getForeignKeyField() => $item->getID()]
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
        switch ($item->getType()) {
            case CarbonIntensitySource::class:
                CarbonIntensitySource_CarbonIntensityZone::showForSource($item);
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

        $search_option_base = PLUGIN_CARBON_SEARCH_OPTION_BASE + 300;

        $tab[] = [
            'id'                 => $search_option_base + 1,
            'table'              => CarbonIntensitySource::getTable(),
            'field'              => 'plugin_carbon_carbonintensitysources_id_historical',
            'name'               => __('Data source for historical calculation', 'carbon'),
            'datatype'           => 'dropdown',
        ];

        return $tab;
    }

    /**
     * Get a zone by a location criteria
     *
     * @param Location $item
     * @return CarbonIntensityZone|null
     */
    public static function getByLocation(Location $item): ?CarbonIntensityZone
    {
        global $DB;

        if ($item->isNewItem()) {
            return null;
        }

        if ($item->fields['country'] == '') {
            return null;
        }

        // TODO: support translations
        $location_table = Location::getTable();
        $zone_table = CarbonIntensityZone::getTable();
        $iterator = $DB->request([
            'SELECT' => CarbonIntensityZone::getTableField('id'),
            'FROM'   => $zone_table,
            'INNER JOIN' => [
                $location_table => [
                    'FKEY' => [
                        $location_table => 'country',
                        $zone_table => 'name',
                    ],
                ],
            ],
            'WHERE'  => [
                Location::getTableField('id') => $item->getID(),
            ]
        ]);

        if ($iterator->count() !== 1) {
            return null;
        }

        $zone_id = $iterator->current()['id'];
        $zone = CarbonIntensityZone::getById($zone_id);
        if ($zone === false) {
            return null;
        }

        return $zone;
    }

    public static function getByAsset(CommonDBTM $item): ?CarbonIntensityZone
    {
        global $DB;

        if (!isset($item->fields[Location::getForeignKeyField()])) {
            return null;
        }

        if ($item->isNewItem()) {
            return null;
        }

        // TODO: support translations
        $location_table = Location::getTable();
        $zone_table = CarbonIntensityZone::getTable();
        $item_table = $item::getTable();
        $iterator = $DB->request([
            'SELECT' => CarbonIntensityZone::getTableField('id'),
            'FROM'   => $zone_table,
            'INNER JOIN' => [
                $location_table => [
                    'FKEY' => [
                        $location_table => 'country',
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
                Location::getTableField('country') => ['<>', ''],
                $item::getTableField('id') => $item->getID(),
            ]
        ]);

        if ($iterator->count() !== 1) {
            return null;
        }

        $zone_id = $iterator->current()['id'];
        $zone = CarbonIntensityZone::getById($zone_id);
        if ($zone === false) {
            return null;
        }

        return $zone;
    }
}
