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
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use DBmysql;
use Entity;
use Location;
use QueryExpression;
use QuerySubQuery;

class CarbonEmission extends CommonDBChild
{
    public static $itemtype = 'itemtype';
    public static $items_id = 'items_id';

    public static function getTypeName($nb = 0)
    {
        return _n("Carbon Emission", "Carbon Emissions", $nb, 'carbon emission');
    }

    public function prepareInputForAdd($input)
    {
        $input = parent::prepareInputForAdd($input);
        if ($input === false || count($input) === 0) {
            return false;
        }
        // $date = new DateTime($input['date']);
        // $date->setTime(0, 0, 0);
        // $input['date'] = $date->format('Y-m-d');
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
