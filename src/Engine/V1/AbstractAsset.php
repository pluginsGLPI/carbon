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

namespace GlpiPlugin\Carbon\Engine\V1;

use CommonDBTM;
use DateInterval;
use DateTime;
use DateTimeInterface;
use DateTimeImmutable;
use DBmysqlIterator;
use DbUtils;
use DBmysql;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\CarbonIntensitySource_Zone;
use GlpiPlugin\Carbon\DataTracking\TrackedInt;
use GlpiPlugin\Carbon\Zone;
use QueryExpression;

abstract class AbstractAsset implements EngineInterface
{
    protected static string $itemtype;
    protected static string $type_itemtype;
    protected static string $model_itemtype;
    protected static string $plugin_type_itemtype;

    protected int $items_id;

    public function __construct(int $items_id)
    {
        $types = [
            static::$itemtype,
            static::$type_itemtype,
            static::$model_itemtype,
            static::$plugin_type_itemtype,
        ];
        foreach ($types as $type) {
            if ($type === '') {
                throw new \LogicException('Itemtype not set');
            }
            if (!is_subclass_of($type, CommonDBTM::class)) {
                throw new \LogicException('Itemtype does not inherits from ' . CommonDBTM::class);
            }
        }

        $this->items_id = $items_id;
    }

    /**
     * get all carbon intensities during the day, between 2 hours boundaries
     *
     * @param DateTimeImmutable $start_time
     * @param DateInterval $length
     * @param Zone $zone
     * @return DBmysqlIterator
     */
    protected function requestCarbonIntensitiesPerDay(DateTimeImmutable $start_time, DateInterval $length, Zone $zone): DBmysqlIterator
    {
        /** @var DBmysql $DB */
        global $DB;

        // Find start tine and end time taking into account that
        // start time may contain a non-zero minute and second, resolution of carbon emission is 1h
        // stop time  may contain a non-zero minute and second, resolution of carbon emission is 1h
        $start_date_s = $start_time->format('Y-m-d H:00:00'); // may be can use directly concatenation
        $stop_date = clone $start_time;
        $stop_date = $stop_date->add($length);
        $stop_date_s = $stop_date->format('Y-m-d H:i:s'); // idem, may be can use directly concatenation

        $intensities_table = CarbonIntensity::getTable();

        /**
         * Keep the lowest data quality of the set of intensities
         */
        $request = [
            'SELECT' => [
                CarbonIntensity::getTableField('intensity') . ' AS intensity',
                CarbonIntensity::getTableField('date') . ' AS date',
                'MIN' => CarbonIntensity::getTableField('data_quality') . ' AS data_quality'
            ],
            'FROM' => $intensities_table,
            'WHERE' => [
                'AND' => [
                    CarbonIntensity::getTableField('plugin_carbon_zones_id') => $zone->getID(),
                    CarbonIntensity::getTableField('plugin_carbon_carbonintensitysources_id') => $zone->fields['plugin_carbon_carbonintensitysources_id_historical'],
                    [CarbonIntensity::getTableField('date') => ['>=', $start_date_s]],
                    [CarbonIntensity::getTableField('date') => ['<', $stop_date_s]],
                ],
            ],
            'GROUP' => [CarbonIntensity::getTableField('date')],
            'ORDER' => CarbonIntensity::getTableField('date') . ' ASC',
        ];

        return $DB->request($request);
    }

    /**
     * Returns the declared power for an asset
     * @return TrackedInt
     */
    public function getPower(): TrackedInt
    {
        /** @var DBmysql $DB */
        global $DB;

        $dbUtils = new DbUtils();
        $itemtype = static::$itemtype;
        $itemtype_plugin_types_table = $dbUtils->getTableForItemType(static::$plugin_type_itemtype);
        $itemtype_models_table = $dbUtils->getTableForItemType(static::$model_itemtype);
        $items_table = $itemtype::getTable();
        $type_fk = static::$type_itemtype::getForeignKeyField();
        $model_fk = static::$model_itemtype::getForeignKeyField();
        $model_power_consumption_field = DBMysql::QuoteName(CommonDBTM::getTableField('power_consumption', static::$model_itemtype));
        $type_power_consumption_field = DBMysql::QuoteName(CommonDBTM::getTableField('power_consumption', static::$plugin_type_itemtype));

        $request = [
            'SELECT'    => [
                $itemtype::getTableField('id') . ' AS items_id',
                new QueryExpression('COALESCE(IF(' . $model_power_consumption_field . ' > 0, ' . $model_power_consumption_field . ', NULL),'
                    . $type_power_consumption_field . ', 0) AS `power_consumption`'),
            ],
            'FROM'      => $items_table, // Asset to evaluate
            'LEFT JOIN' => [
                $itemtype_plugin_types_table => [ // Data for the type of the asset
                    'FKEY'   => [
                        $itemtype_plugin_types_table  => $type_fk,
                        $items_table => $type_fk,
                    ]
                ],
                $itemtype_models_table => [ // Data for the model of the asset
                    'FKEY'   => [
                        $items_table => $model_fk,
                        $itemtype_models_table  => 'id',
                    ]
                ]
            ],
            'WHERE' => [
                $itemtype::getTableField('id') => $this->items_id,
            ],
        ];
        $result = $DB->request($request);

        if ($result->numrows() === 1) {
            $power = $result->current()['power_consumption'];
            return new TrackedInt($power, null, TrackedInt::DATA_QUALITY_MANUAL);
        }

        return new TrackedInt(0, null, TrackedInt::DATA_QUALITY_MANUAL);
    }

    /**
     * Get a carbon intensity for the given zone or fallback to a carbon intensity
     * for the world. Use the latest value before the given date
     *
     * @param DateTimeInterface $day
     * @param Zone $zone
     * @return array|null
     */
    protected function getFallbackCarbonIntensity(DateTimeInterface $day, Zone $zone): ?array
    {
        static $world_not_found_error_triggered = false;

        /** @var DBmysql $DB */
        global $DB;

        $carbon_intensity_table = CarbonIntensity::getTable();
        $carbon_intensity_source_zone_table = CarbonIntensitySource_Zone::getTable();
        $carbon_intensity_source_table = CarbonIntensitySource::getTable();
        $request = [
            'SELECT' => "$carbon_intensity_table.*",
            'FROM' => $carbon_intensity_table,
            'INNER JOIN' => [
                $carbon_intensity_source_zone_table => [
                    'FKEY'   => [
                        $carbon_intensity_table => 'plugin_carbon_zones_id',
                        $carbon_intensity_source_zone_table => 'plugin_carbon_carbonintensitysources_id',
                    ]
                ],
                $carbon_intensity_source_table => [
                    'FKEY'   => [
                        $carbon_intensity_source_zone_table => 'plugin_carbon_carbonintensitysources_id',
                        $carbon_intensity_source_table => 'id',
                    ]
                ]
            ],
            'WHERE' => [
                CarbonIntensitySource::getTableField('is_fallback') => 1,
                CarbonIntensitySource::getTableField('name') => 'Ember - Energy Institute',
                CarbonIntensitySource_Zone::getTableField('plugin_carbon_zones_id') => $zone->getID(),
                CarbonIntensity::getTableField('date') => ['<=', $day->format('Y-m-d H:i:s')],
            ],
            'ORDER' => CarbonIntensity::getTableField('date') . ' DESC',
            'LIMIT' => 1,
        ];
        $result = $DB->request($request);
        if ($result->count() === 1) {
            return $result->current();
            // $intensity = $result->current()['intensity'];
            // return $intensity;
        }

        // No data for the zone, fallback again to carbon intensity for the whole world
        // We assume that electricity is (nearly) immediately consumed then worlwide and yearly
        // carbon intensity generation and consumption is the same
        unset($request['WHERE'][CarbonIntensitySource_Zone::getTableField('plugin_carbon_zones_id')]);
        $carbon_intensity_zone_table = Zone::getTable();
        $request['INNER JOIN'] = [
            $carbon_intensity_zone_table => [
                'FKEY'   => [
                    $carbon_intensity_table => 'plugin_carbon_zones_id',
                    $carbon_intensity_zone_table => 'id',
                ]
            ],
            $carbon_intensity_source_table => [
                'FKEY'   => [
                    $carbon_intensity_table => 'plugin_carbon_carbonintensitysources_id',
                    $carbon_intensity_source_table => 'id',
                ]
            ],
        ];
        $request['WHERE'][Zone::getTableField('name')] = 'World';

        $result = $DB->request($request);
        if ($result->count() === 1) {
            return $result->current();
            // $intensity = $result->current()['intensity'];
            // return $intensity;
        }

        if (!$world_not_found_error_triggered) {
            // Log an error, only once
            //Should not happen as data for countries and workd are added in DB at install time
            $world_not_found_error_triggered = true;
            trigger_error(
                sprintf(
                    'No fallback carbon intensity found for zone %s and date %s',
                    $zone->getField('name'),
                    $day->format('Y-m-d H:i:s')
                )
            );
        }

        return null;
    }
}
