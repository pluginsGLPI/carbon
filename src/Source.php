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
use DbUtils;
use DBmysql;
use Session;

class Source extends CommonDropdown
{
    public static function getTypeName($nb = 0)
    {
        return _n("Carbon intensity source", "Carbon intensity sources", $nb, 'carbon');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canUpdate(): bool
    {
        return false;
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
        $this->addStandardTab(Zone::class, $tabs, $options);
        return $tabs;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$withtemplate) {
            $nb = 0;
            /** @var CommonDBTM $item */
            switch ($item->getType()) {
                case Zone::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = (new DbUtils())->countElementsInTable(
                            Source_Zone::getTable(),
                            [self::getForeignKeyField() => $item->getID()]
                        );
                    }
                    return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
            }
        }

        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        /** @var CommonDBTM $item */
        switch ($item->getType()) {
            case Zone::class:
                Source_Zone::showForZone($item);
        }

        return true;
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'is_fallback',
            'name'               => __('Is a fallback source'),
            'massiveaction'      => false,
            'datatype'           => 'boolean',
        ];

        return $tab;
    }

    /**
     * Get an array of source names for downloadable data
     *
     * @return array
     */
    public function getDownloadableSources(): array
    {
        /** @var DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['id', 'name'],
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'is_fallback' => 0,
            ]
        ]);
        return iterator_to_array($iterator);
    }
}
