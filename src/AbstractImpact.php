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
use GlpiPlugin\Carbon\Impact\Type;

abstract class AbstractImpact extends CommonDBChild
{
    public static $itemtype = 'itemtype';
    public static $items_id = 'items_id';

    public static $rightname = 'carbon:report';

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
            'table'              => $this->getTable(),
            'field'              => 'recalculate',
            'name'               => __('', 'carbon'),
            'massiveaction'      => false,
            'datatype'           => 'bool',
        ];

        $id = SearchOptions::IMPACT_BASE;
        foreach (Type::getImpactTypes() as $type_id => $type) {
            $id = SearchOptions::IMPACT_BASE + $type_id * 2;
            $tab[] = [
                'id'                 => $id,
                'table'              => $this->getTable(),
                'field'              => $type,
                'name'               => Type::getEmbodiedImpactLabel($type),
                'massiveaction'      => false,
                'datatype'           => 'number',
                'unit'               => implode(' ', Type::getImpactUnit($type)),
            ];
            $id++;

            $tab[] = [
                'id'                 => $id,
                'table'              => $this->getTable(),
                'field'              => "{$type}_quality",
                'name'               => Type::getEmbodiedImpactLabel($type),
                'massiveaction'      => false,
                'datatype'           => 'number',
                'unit'               => implode(' ', Type::getImpactUnit($type)),
            ];
        }

        $tab[] = [
            'id'                 => SearchOptions::CALCULATION_DATE,
            'table'              => self::getTable(),
            'field'              => 'date_mod',
            'name'               => __('Date of evaluation', 'carbon')
        ];

        $tab[] = [
            'id'                 => SearchOptions::CALCULATION_ENGINE,
            'table'              => self::getTable(),
            'field'              => 'engine',
            'name'               => __('Engine', 'carbon')
        ];

        $tab[] = [
            'id'                 => SearchOptions::CALCULATION_ENGINE_VERSION,
            'table'              => self::getTable(),
            'field'              => 'engine_version',
            'name'               => __('Engine version', 'carbon')
        ];

        return $tab;
    }

    /**
     * Get impact value in a human r eadable format, selecting the best unit
     *
     * @param string $field the field of the impact
     */
    public function getHumanReadableImpact(string $field): string
    {
        return Toolbox::getHumanReadableValue(
            $this->fields[$field],
            Type::getImpactUnit($field)
        );
    }
}
