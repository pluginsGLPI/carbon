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

use CommonDBChild;
use DateInterval;
use DateTimeInterface;
use Entity;
use Location;

class CarbonEmission extends CommonDBChild
{
    public static $itemtype = 'itemtype';
    public static $items_id = 'items_id';

    public static function getTypeName($nb = 0)
    {
        return _n("Carbon Emission", "Carbon Emissions", $nb, 'carbon');
    }

    public function prepareInputForAdd($input)
    {
        $input = parent::prepareInputForAdd($input);
        if ($input === false || count($input) === 0) {
            return false;
        }
        return $input;
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false, // implicit field is id
            'datatype'           => 'number'
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'items_id',
            'name'               => __('Associated item ID'),
            'massiveaction'      => false,
            'datatype'           => 'specific',
            'additionalfields'   => ['itemtype']
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => $this->getTable(),
            'field'              => 'itemtype',
            'name'               => _n('Type', 'Types', 1),
            'massiveaction'      => false,
            'datatype'           => 'itemtypename',
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => self::getTable(),
            'field'              => 'entities_id',
            'name'               => Entity::getTypeName(1)
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => 'glpi_locations',
            'field'              => 'name',
            'linkfield'          => 'locations_id',
            'name'               => Location::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => SearchOptions::CARBON_EMISSION_DATE,
            'table'              => self::getTable(),
            'field'              => 'date',
            'name'               => __('Date')
        ];

        $tab[] = [
            'id'                 => SearchOptions::CARBON_EMISSION_ENERGY_PER_DAY,
            'table'              => self::getTable(),
            'field'              => 'energy_per_day',
            'name'               => __('Energy', 'carbon'),
            'unit'               => 'KWh',
        ];

        $tab[] = [
            'id'                 => SearchOptions::CARBON_EMISSION_PER_DAY,
            'table'              => self::getTable(),
            'field'              => 'emission_per_day',
            'name'               => __('Emission', 'carbon'),
            'unit'               => 'gCO<sub>2</sub>eq',
        ];

        $tab[] = [
            'id'                 => SearchOptions::CARBON_EMISSION_ENERGY_QUALITY,
            'table'              => self::getTable(),
            'field'              => 'energy_quality',
            'name'               => __('Energy quality', 'carbon')

        ];

        $tab[] = [
            'id'                 => SearchOptions::CARBON_EMISSION_EMISSION_QUALITY,
            'table'              => self::getTable(),
            'field'              => 'emission_quality',
            'name'               => __('Emission quality', 'carbon')
        ];

        $tab[] = [
            'id'                 => SearchOptions::CARBON_EMISSION_CALC_DATE,
            'table'              => self::getTable(),
            'field'              => 'date_mod',
            'name'               => __('Date of evaluation', 'carbon')
        ];

        $tab[] = [
            'id'                 => SearchOptions::CARBON_EMISSION_ENGINE,
            'table'              => self::getTable(),
            'field'              => 'engine',
            'name'               => __('Engine', 'carbon')
        ];

        $tab[] = [
            'id'                 => SearchOptions::CARBON_EMISSION_ENGINE_VER,
            'table'              => self::getTable(),
            'field'              => 'engine_version',
            'name'               => __('Engine version', 'carbon')
        ];

        return $tab;
    }

    /**
     * Gets date intervals where data are missing
     * Gaps are returned as an array of start and end
     * where start is the 1st msising date and end is the last missing date
     *
     * @param integer $id
     * @param DateTimeInterface|null $start
     * @param DateTimeInterface|null $stop
     * @return array
     */
    public function findGaps(string $itemtype, int $id, ?DateTimeInterface $start, ?DateTimeInterface $stop = null): array
    {
        $criteria = [
            'itemtype' => $itemtype,
            'items_id' => $id,
        ];
        $interval = new DateInterval('P1D');
        return Toolbox::findTemporalGapsInTable(self::getTable(), $start, $interval, $stop, $criteria);
    }
}
