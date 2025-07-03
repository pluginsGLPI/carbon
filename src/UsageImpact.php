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

class UsageImpact extends CommonDBChild
{
    public static $itemtype = 'itemtype';
    public static $items_id = 'items_id';

    public static $rightname = 'carbon:report';

    public static function getTypeName($nb = 0)
    {
        return _n("Usage impact", "Usage impacts", $nb, 'carbon');
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
            'id'                 => SearchOptions::USAGE_IMPACT_DATE,
            'table'              => self::getTable(),
            'field'              => 'date',
            'name'               => __('Date')
        ];

        $tab[] = [
            'id'                 => SearchOptions::USAGE_IMPACT_GWP,
            'table'              => self::getTable(),
            'field'              => 'gwp',
            'name'               => __('Global warming potential', 'carbon')
        ];

        $tab[] = [
            'id'                 => SearchOptions::USAGE_IMPACT_GWP_QUALITY,
            'table'              => self::getTable(),
            'field'              => 'gwp_quality',
            'name'               => __('Global warming potential quality', 'carbon')
        ];

        $tab[] = [
            'id'                 => SearchOptions::USAGE_IMPACT_ADP,
            'table'              => self::getTable(),
            'field'              => 'adp',
            'name'               => __('Abiotic depletion potential', 'carbon')

        ];

        $tab[] = [
            'id'                 => SearchOptions::USAGE_IMPACT_ADP_QUALITY,
            'table'              => self::getTable(),
            'field'              => 'adp_quality',
            'name'               => __('Abiotic depletion potential quality', 'carbon')
        ];

        $tab[] = [
            'id'                 => SearchOptions::USAGE_IMPACT_PE,
            'table'              => self::getTable(),
            'field'              => 'pe',
            'name'               => __('Primary energy quality', 'carbon')

        ];

        $tab[] = [
            'id'                 => SearchOptions::USAGE_IMPACT_PE_QUALITY,
            'table'              => self::getTable(),
            'field'              => 'pe_quality',
            'name'               => __('Primary energy quality', 'carbon')
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
     * Get impact value in a human r eadable format, selecting the best unit
     */
    public function getHumanReadableImpact(string $field): string
    {
        switch ($field) {
            case 'gwp':
                return Toolbox::getWeight($this->fields[$field]) . 'CO2eq';
            case 'adp':
                return Toolbox::getWeight($this->fields[$field]) . 'Sbeq';
            case 'pe':
                return Toolbox::getEnergy($this->fields[$field] / 3600); // Convert J into Wh
        }

        return '';
    }
}
