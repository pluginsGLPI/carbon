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
                return Toolbox::getPower($this->fields[$field] / 3600) . 'h'; // Convert J into Wh
        }

        return '';
    }
}
