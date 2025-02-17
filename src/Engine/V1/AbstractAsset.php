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

namespace GlpiPlugin\Carbon\Engine\V1;

use CommonDBTM;
use DateInterval;
use DateTime;
use DateTimeInterface;
use DBmysqlIterator;
use DbUtils;
use DbMysql;
use GlpiPlugin\Carbon\CarbonIntensityZone;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\DataTracking\TrackedInt;
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
     * @param DateTimeInterface $start_time
     * @param DateInterval $length
     * @param CarbonIntensityZone $zone
     * @return DBmysqlIterator
     */
    protected function requestCarbonIntensitiesPerDay(DateTimeInterface $start_time, DateInterval $length, CarbonIntensityZone $zone): DBmysqlIterator
    {
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
                    CarbonIntensity::getTableField('plugin_carbon_carbonintensityzones_id') => $zone->getID(),
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
}
