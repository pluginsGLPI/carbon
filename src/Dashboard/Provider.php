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

namespace GlpiPlugin\Carbon\Dashboard;

use Computer;
use ComputerModel;
use ComputerType as GlpiComputerType;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DBmysql;
use DbUtils;
use Glpi\Dashboard\Filter;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\EmbodiedImpact;
use GlpiPlugin\Carbon\Toolbox;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\Zone;
use GlpiPlugin\Carbon\Impact\History\Computer as ComputerHistory;
use GlpiPlugin\Carbon\SearchOptions;
use GlpiPlugin\Carbon\UsageImpact;
use GlpiPlugin\Carbon\UsageInfo;
use Monitor;
use NetworkEquipment;
use QueryExpression; // TODO: when GLPI 11.0 is required, use Glpi\DBAL\QueryExpression instead
use QuerySubQuery; // TODO: when GLPI 11.0 is required, use Glpi\DBAL\QuerySubQuery instead
use Search;
use Session;
use Toolbox as GlpiToolbox;

class Provider
{
    /**
     * Get the sum of values of a field in a table, limited to conditions
     *
     * @param string $table table to read
     * @param string $field table field to use for the sum
     * @param array $params additional parameters (filters)
     * @param array $crit   additional criteria
     * @return ?float
     */
    public static function getSum(string $table, string $field, array $params = [], array $crit = []): ?float
    {
        /** @var DBmysql $DB */
        global $DB;

        $request = [
            'SELECT'    => [
                'SUM' => "$field AS total"
            ],
            'FROM'      => $table,
            'WHERE'     => $crit,
        ];

        $request = array_merge_recursive(
            $request,
            self::getFiltersCriteria($table, $params['apply_filters'] ?? [])
        );

        $result = $DB->request($request);
        if ($result->numrows() == 1) {
            return $result->current()['total'] ?? 0;
        }

        return null;
    }

    /**
     * Returns sum of of carbon emission grouped by computer model without time limitation.
     *
     * @param array $params
     * @param array $where
     *
     * @return array of:
     *   - mixed  'number': sum for the model
     *   - string 'url': url to redirect when clicking on the slice
     *   - string 'label': name of the computer model
     */
    public static function getSumUsageEmissionsPerModel(array $params = [], array $where = [])
    {
        /** @var DBmysql $DB */
        global $DB;

        $computermodels_table = ComputerModel::getTable();
        $carbonemissions_table = CarbonEmission::getTable();

        $sql_year_month = "DATE_FORMAT(`date`, '%Y-%m')";
        $entity_restrict = (new DbUtils())->getEntitiesRestrictCriteria($carbonemissions_table, '', '', 'auto');
        $subrequest = [
            'SELECT'    => [
                ComputerModel::getTableField('id'),
                ComputerModel::getTableField('name'),
                /**  */
                @new QueryExpression("$sql_year_month as `date`"),
                'SUM' => 'emission_per_day AS monthly_emission_per_model',
                @new QueryExpression('COUNT(DISTINCT ' . CarbonEmission::getTableField('items_id') . ') AS nb_computers_per_model'),
            ],
            'FROM'      => $computermodels_table,
            'INNER JOIN' => [
                $carbonemissions_table => [
                    'FKEY'   => [
                        $carbonemissions_table => 'models_id',
                        $computermodels_table  => 'id'
                    ]
                ],
            ],
            'WHERE' => [
                CarbonEmission::getTableField('itemtype') => Computer::class
            ] + $entity_restrict + $where,
            'GROUPBY' => [
                ComputerModel::getTableField('id'),
                @new QueryExpression($sql_year_month)
            ],
            'ORDER'   => ComputerModel::getTableField('name'),
        ];

        $request = [
            'SELECT'    => [
                'id',
                'name',
                'AVG' => 'monthly_emission_per_model AS total_per_model',
                'MAX' => 'nb_computers_per_model AS nb_computers_per_model'
            ],
            'FROM' => @new QuerySubQuery($subrequest, 'montly_per_model'),
            'WHERE' => [],
            'GROUPBY' => ['id'],
            'ORDERBY' => 'total_per_model DESC',
            'LIMIT'   => $params['limit'] ?? 9999
        ];

        if (!empty($where)) {
            $filter_criteria = self::getFiltersCriteria(Computer::getTable(), []);
            $request['WHERE'] = $request['WHERE'] + $filter_criteria;
        }
        $result = $DB->request($request);

        $emissions = [];
        foreach ($result as $row) {
            $emissions[$row['id']] = $row['total_per_model'];
        }
        $co2eq = __('CO₂eq', 'carbon');
        $units = [
            __('g', 'carbon')  .  ' ' . $co2eq,
            __('Kg', 'carbon') .  ' ' . $co2eq,
            __('t', 'carbon')  .  ' ' . $co2eq,
            __('Kt', 'carbon') .  ' ' . $co2eq,
            __('Mt', 'carbon') .  ' ' . $co2eq,
            __('Gt', 'carbon') .  ' ' . $co2eq,
            __('Tt', 'carbon') .  ' ' . $co2eq,
            __('Pt', 'carbon') .  ' ' . $co2eq,
            __('Et', 'carbon') .  ' ' . $co2eq,
            __('Zt', 'carbon') .  ' ' . $co2eq,
            __('Yt', 'carbon') .  ' ' . $co2eq,
        ];
        $emissions = Toolbox::scaleSerie($emissions, $units);
        $models_id = null;
        $search_criteria = [
            'criteria' => [
                [
                    'field'      => 40,
                    'searchtype' => 'equals',
                    'value'      => &$models_id // Reference to $models_id !
                ],
            ],
            'reset'    => 'reset'
        ];

        foreach ($result as $row) {
            $count = $row['nb_computers_per_model'];
            $data['series'][] = (float) $emissions['serie'][$row['id']];
            $data['labels'][] = $row['name'] . " (" . $row['nb_computers_per_model'] . " " . Computer::getTypeName($count) . ")";
            $models_id = $row['id'];
            $data['url'][] = Computer::getSearchURL() . '?' . GlpiToolbox::append_params($search_criteria);
        }

        $data['unit'] = $emissions['unit'];

        return [
            'data' => $data
        ];
    }

    public static function getSumEmissionsPerType(array $where = [])
    {
        /** @var DBmysql $DB */
        global $DB;

        $glpicomputertypes_table = GlpiComputerType::getTable();
        $carbonemissions_table = CarbonEmission::getTable();

        $entity_restrict = (new DbUtils())->getEntitiesRestrictCriteria($carbonemissions_table, '', '', 'auto');
        $request = [
            'SELECT'    => [
                GlpiComputerType::getTableField('id'),
                GlpiComputerType::getTableField('name'),
                'SUM' => 'emission_per_day AS total_per_type',
                @new QueryExpression('COUNT(DISTINCT ' . CarbonEmission::getTableField('items_id') . ') AS nb_computers_per_type'),
            ],
            'FROM'      => $glpicomputertypes_table,
            'INNER JOIN' => [
                $carbonemissions_table => [
                    'FKEY'   => [
                        $carbonemissions_table => 'types_id',
                        $glpicomputertypes_table  => 'id'
                    ]
                ],
            ],
            'WHERE' => [
                CarbonEmission::getTableField('itemtype') => Computer::class
            ] + $entity_restrict,
            'GROUPBY' => GlpiComputerType::getTableField('id'),
            'ORDER'   => GlpiComputerType::getTableField('name'),
        ];

        if (!empty($where)) {
            $request['WHERE'] = $request['WHERE'] + $where;
        }
        $result = $DB->request($request);

        $data = [];
        foreach ($result as $row) {
            $count = $row['nb_computers_per_type'];
            $data[] = [
                'number' => number_format($row['total_per_type'], PLUGIN_CARBON_DECIMALS, ',', ''),
                'url' => GlpiComputerType::getFormURLWithID($row['id']),
                'label' => $row['name'] . " (" . $row['nb_computers_per_type'] . " " . Computer::getTypeName($count) . ")",
            ];
        }

        return $data;
    }

    /**
     * Get the sum of power of all computers per model
     *
     * @param array $where
     * @return array
     */
    public static function getSumPowerPerModel(array $where = [])
    {
        /** @var DBmysql $DB  */
        global $DB;

        $computers_table = Computer::getTable();
        $computermodels_table = ComputerModel::getTable();
        $glpiComputertypes_table = GlpiComputerType::getTable();
        $computertype_table = ComputerType::getTable();

        $entity_restrict = (new DbUtils())->getEntitiesRestrictCriteria($computers_table, '', '', 'auto');
        $request = [
            'SELECT'    => [
                ComputerModel::getTableField('id'),
                ComputerModel::getTableField('name'),
                'SUM' => ComputerModel::getTableField('power_consumption') . ' AS total_per_model',
                'COUNT' => Computer::getTableField('id') . ' AS nb_computers_per_model',
            ],
            'FROM'      => $computermodels_table,
            'INNER JOIN' => [
                $computers_table => [
                    'FKEY'   => [
                        $computermodels_table  => 'id',
                        $computers_table => 'computermodels_id',
                    ]
                ],
                $glpiComputertypes_table => [
                    'FKEY'   => [
                        $computers_table  => 'computertypes_id',
                        $glpiComputertypes_table => 'id',
                    ]
                ],
                $computertype_table => [
                    'FKEY' => [
                        $glpiComputertypes_table => 'id',
                        $computertype_table => 'computertypes_id',
                    ]
                ]
            ],
            'WHERE' => $entity_restrict,
            'GROUPBY' => ComputerModel::getTableField('id'),
            'ORDER'   => ComputerModel::getTableField('name'),
        ];
        if (!empty($where)) {
            $request['WHERE'] = $request['WHERE'] + $where;
        }
        $result = $DB->request($request);

        $data = [];
        foreach ($result as $row) {
            $data[] = [
                'number' => number_format($row['total_per_model'], PLUGIN_CARBON_DECIMALS),
                'url' => ComputerModel::getFormURLWithID($row['id']),
                'label' => $row['name'] . " (" . $row['nb_computers_per_model'] . " computers)",
            ];
        }

        return $data;
    }

    public static function getHandledAssetsRatio(array $params = [])
    {
        $default_params = [
            'label' => __('handled assets ratio', 'carbon'),
            'icon'  => '',
        ];
        $params = array_merge($default_params, $params);

        $data = [];
        foreach (PLUGIN_CARBON_TYPES as $itemtype) {
            $itemtype_name = $itemtype::getTypeName(Session::getPluralNumber());
            $itemtype_name = strtolower($itemtype_name);

            $handled = self::getHandledAssetCount($itemtype, true, $params);
            $unhandled = self::getHandledAssetCount($itemtype, false, $params);

            $total = $handled['number'] + $unhandled['number'];
            if ($total === 0) {
                continue;
            }

            $handled_percent = ($handled['number'] / $total) * 100;
            $data[] = [
                'number' => number_format($handled_percent, 0),
                'url'    => $unhandled['url'],
                'label'  => $itemtype_name,
            ];
        }

        return [
            'data' => $data,
            'label' => $params['label'],
            'icon'  => $params['icon'],
        ];
    }

    /**
     * Get count of handled and unhandled assets
     *
     * Handled assets are assets with enough data to calculate usage carbon emission
     *
     * @param array|string $itemtypes types of asets to count
     * @param array $params
     * @return array
     */
    public static function getHandledAssetsCounts($itemtypes, array $params = []): array
    {
        $default_params = [
            'label' => __('Handled assets ratio', 'carbon'),
            'icon'  => '',
        ];
        $params = array_merge($default_params, $params);

        if (count($params['args']['itemtypes'] ?? []) === 0) {
            $itemtypes = PLUGIN_CARBON_TYPES;
        } else {
            $itemtypes = array_intersect(PLUGIN_CARBON_TYPES, $params['args']['itemtypes']);
        }

        $data = [
            'labels' => [],
            'series' => []
        ];
        foreach ($itemtypes as $itemtype) {
            $itemtype_name = $itemtype::getTypeName(Session::getPluralNumber());
            $itemtype_name = strtolower($itemtype_name);

            $handled = self::getHandledAssetCount($itemtype, true, $params);
            $unhandled = self::getHandledAssetCount($itemtype, false, $params);

            $data['labels'][] = $itemtype_name;
            $data['series'][0]['name'] = __('Handled', 'carbon');
            $data['series'][0]['data'][] = [
                'value' => $handled['number'],
                'url'   => $handled['url']
            ];
            $data['series'][1]['name'] = __('Unhandled', 'carbon');
            $data['series'][1]['data'][] = [
                'value' => $unhandled['number'],
                'url'   => $unhandled['url'],
            ];
        }

        return [
            'data' => $data,
            'label' => $params['label'],
            'icon'  => $params['icon'],
        ];
    }

    /**
     * Count the assets having all required data to compute carbon intensity
     *
     * @param string $itemtype the itemtype to count
     * @param bool   $handled : true if we want to count handled assets, false to count unhandled assets
     * @param array  $params
     * @return array count of items
     */
    public static function getHandledAssetCount(string $itemtype, bool $handled, array $params = []): array
    {
        $itemtype_name = $itemtype::getTypeName(Session::getPluralNumber());
        $itemtype_name = strtolower($itemtype_name);
        $label = $handled ?
            __("plugin carbon - handled %s", 'carbon')
            : __("plugin carbon - unhandled %s", 'carbon');
        $default_params = [
            'label' => sprintf($label, $itemtype_name),
            'icon'  => '',
        ];
        $params = array_merge($default_params, $params);

        $search_criteria = [
            'criteria' => [
                [
                    'field'      => SearchOptions::IS_HISTORIZABLE,
                    'searchtype' => 'equals',
                    'value'      => $handled ? 1 : 0
                ],
            ],
            'reset'    => 'reset'
        ];
        $itemtype_table = (new DbUtils())->getTableForItemType($itemtype);
        // Exploit defaultWhere to inject WHERE criterias from dashboard filters
        $filter_criteria = self::getFiltersCriteria($itemtype_table, $params['apply_filters'] ?? []);
        $search_data = Search::prepareDatasForSearch($itemtype, $search_criteria);
        Search::constructSQL($search_data);
        Search::constructData($search_data, true);

        $search_url = GlpiToolbox::getItemTypeSearchURL($itemtype);
        $count = $search_data['data']['totalcount'] ?? null;
        $url = $search_url . '?' . GlpiToolbox::append_params($search_criteria);

        return [
            'number' => $count,
            'url'    => $url,
            'label'  => $params['label'],
            'icon'   => $params['icon'],
        ];
    }

    /**
     * Get total power of assets having all required data to compute carbon intensity
     *
     * @param array $params
     * @return array
     */
    public static function getUsagePower(array $params = []): array
    {
        /** @var DBmysql $DB */
        global $DB;

        $default_params = [
            'label' => __("plugin carbon - Total usage power consumption", 'carbon'),
            'icon'  => "fa-solid fa-plug",
        ];
        $params = array_merge($default_params, $params);

        $total_power = 0;
        $types_computed = 0;
        foreach (PLUGIN_CARBON_TYPES as $itemtype) {
            $type_history = 'GlpiPlugin\\Carbon\\Impact\\History\\' . $itemtype;
            if (!class_exists($type_history)) {
                trigger_error(
                    sprintf(
                        "Class %s does not exist; unable to compute total usage power consumption for itemtype %s",
                        $type_history,
                        $itemtype
                    ),
                    E_USER_WARNING
                );
                continue;
            }
            $itemtype_type = 'GlpiPlugin\\Carbon\\' . $itemtype . 'Type';
            $itemtype_model = $itemtype . 'Model';
            $model_power_field =  DBMysql::quoteName($itemtype_model::getTableField('power_consumption'));
            $type_power_field = DBMysql::quoteName($itemtype_type::getTableField('power_consumption'));
            $request = (new $type_history())->getEvaluableQuery();
            // If a value is set in a model, it cannut be  reset to NULL, then let's consoder 0 as NULL
            // and coalesce model, power and 0 to implement precedence
            $sum_coalesce = @new QueryExpression(
                'SUM(COALESCE('
                    . 'IF(' . $model_power_field . ' > 0, ' . $model_power_field . ', NULL),'
                    . 'IF(' . $type_power_field  . ' > 0, ' . $type_power_field  . ', NULL),'
                    . '0'
                . ')) AS total'
            );
            $request['SELECT'] = [
                $sum_coalesce,
            ];
            $result = $DB->request($request);
            if ($result->numrows() != 1) {
                continue;
            }
            $total_power += ($result->current()['total'] ?? 0);
            $types_computed++;
        }
        if ($types_computed == 0) {
            return [
                'number' => 'N/A',
                'label'  => $params['label'],
                'icon'   => $params['icon'],
            ];
        }
        $total_power = Toolbox::getPower($total_power);

        return [
            'number' => $total_power,
            'label'  => $params['label'],
            'icon'   => $params['icon'],
        ];
    }

    /**
     * Get usage CO2 emissions
     *
     * @param array $params
     * @return array
     */
    public static function getUsageCarbonEmission(array $params = []): array
    {
        $default_params = [
            'label' => __('plugin carbon - Usage carbon emission', 'carbon'),
            'icon'  => 'fa-solid fa-temperature-arrow-up',
        ];
        $params = array_merge($default_params, $params);
        $crit = [
            'itemtype' => PLUGIN_CARBON_TYPES,
        ];

        $gwp = self::getSum(CarbonEmission::getTable(), 'emission_per_day', $params, $crit);
        if ($gwp === null) {
            $gwp = 'N/A';
        } else {
            $gwp = Toolbox::getWeight($gwp) . __('CO₂eq', 'carbon');
        }

        return [
            'number' => $gwp,
            'label'  => $params['label'],
            'icon'   => $params['icon'],
        ];
    }

    /**
     * Get the usage carbon emission of the 12 last elapsed months
     *
     * @param array $params
     * @return array
     */
    public static function getUsageCarbonEmissionYearToDate(array $params = []): array
    {
        list($start_date, $end_date) = (new Toolbox())->yearToLastMonth(new DateTimeImmutable('now'));
        $params['apply_filters'] = [
            'dates' => [
                $start_date->format('Y-m-d\TH:i:s.v\Z'),
                $end_date->format('Y-m-d\TH:i:s.v\Z'),
            ]
        ];
        return self::getUsageCarbonEmission($params);
    }

    public static function getCarbonIntensity(array $params): array
    {
        /** @var DBmysql $DB */
        global $DB;

        $source = new CarbonIntensitySource();
        $zone = new Zone();
        $source->getFromDBByCrit([
            'name' => 'RTE'
        ]);
        $zone->getFromDBByCrit([
            'name' => 'France'
        ]);

        $request = [
            // 'SELECT' => [
                // CarbonIntensity::getTableField('intensity'),
                // CarbonIntensity::getTableField('date'),
            // ],
            'FROM'  => CarbonIntensity::getTable(),
            'WHERE' => [
                CarbonIntensitySource::getForeignKeyField() => $source->getID(),
                Zone::getForeignKeyField() => $zone->getID(),
            ],
        ];

        $filters = self::getFiltersCriteria(CarbonIntensity::getTable(), $params['apply_filters'] ?? []);
        $request = array_merge_recursive(
            $request,
            $filters
        );

        // Limit deepness
        $count_request = $request;
        // unset($count_request['SELECT']);
        $count_request['COUNT'] = 'c';
        $count = $DB->request($count_request);
        if ($count->numrows() !== 1) {
            throw new \RuntimeException("Failed to count carbon intensity samples");
        }
        $date = CarbonIntensity::getTableField('date');
        $intensity = CarbonIntensity::getTableField('intensity');
        $count = $count->current()['c'];
        if ($count > 365 * 5 * 24) {
            $date = $DB->quoteName($date);
            $date = "DATE_FORMAT($date, '%Y')";
            $intensity = ['AVG' => "$intensity AS `intensity`"];
            $request['GROUPBY'] = @new QueryExpression($date);
            $request['LIMIT'] = 10;
        } else if ($count > 365 * 24) {
            $date = $DB->quoteName($date);
            $date = "DATE_FORMAT($date, '%Y-%m')";
            $intensity = ['AVG' => "$intensity AS `intensity`"];
            $request['GROUPBY'] = @new QueryExpression($date);
        } else if ($count > 30 * 24) {
            $date = $DB->quoteName($date);
            $date = "DATE_FORMAT($date, '%Y-%m-%d')";
            $intensity = ['AVG' => "$intensity AS `intensity`"];
            $request['GROUPBY'] = @new QueryExpression($date);
        } else {
            $intensity = [$intensity];
        }

        unset($request['COUNT']);
        $request['SELECT'] = array_merge(
            [new QueryExpression("$date AS `date`")],
            $intensity
        );
        $request['ORDERBY'] = 'date';
        $rows = $DB->request($request);

        $data = [
            'labels' => [],
            'series' => [
                [
                    'name' => sprintf('%s %s', $source->fields['name'], $zone->fields['name']),
                    'data' => [],
                ]
            ],
        ];
        foreach ($rows as $row) {
            $data['labels'][]            = $row['date'];
            $data['series'][0]['data'][] = number_format($row['intensity'], PLUGIN_CARBON_DECIMALS);
        }

        return $data;
    }

    /**
     * Get the filters criteria
     *
     * @param string $table
     * @param array $apply_filters
     *
     * @return array
     */
    public static function getFiltersCriteria(string $table = "", array $apply_filters = []): array
    {
        $where = [];
        $join  = [];

        $filters = Filter::getRegisteredFilterClasses();

        foreach ($filters as $filter) {
            if (!$filter::canBeApplied($table) || !array_key_exists($filter::getId(), $apply_filters)) {
                continue;
            }
            $filter_criteria = $filter::getCriteria($table, $apply_filters[$filter::getId()]);
            if (isset($filter_criteria['WHERE'])) {
                $where = array_merge($where, $filter_criteria['WHERE']);
            }
            if (isset($filter_criteria['JOIN'])) {
                $join = array_merge($join, $filter_criteria['JOIN']);
            }
        }

        $criteria = [];
        if (count($where)) {
            $criteria['WHERE'] = $where;
        }
        if (count($join)) {
            $criteria['LEFT JOIN'] = $join;
        }

        return $criteria;
    }

    public static function getEmbodiedGlobalWarming(array $params = []): array
    {
        $default_params = [
            'label' => __('Total embodied global warming potential', 'carbon'),
            'icon'  => 'fa-solid fa-temperature-arrow-up',
        ];
        $params = array_merge($default_params, $params);

        $crit = [
            'itemtype' => PLUGIN_CARBON_TYPES,
        ];
        $value = self::getSum(EmbodiedImpact::getTable(), 'gwp', $params, $crit);
        if ($value === null) {
            $value = 'N/A';
        } else {
            $value = Toolbox::getWeight($value) . __('CO₂eq', 'carbon');
        }

        return [
            'number'     => $value,
            'label'      => $params['label'],
            'icon'       => $params['icon'],
        ];
    }

    /**
     * Get the primary energy consumed to build assets
     *
     * @param array $params
     * @return array
     */
    public static function getEmbodiedPrimaryEnergy(array $params = []): array
    {
        $crit = [
            'itemtype' => PLUGIN_CARBON_TYPES,
        ];
        $value = self::getSum(EmbodiedImpact::getTable(), 'pe', $params, $crit);
        if ($value === null) {
            $value = 'N/A';
        } else {
            // Convert into Watt.hour
            $value = Toolbox::getEnergy($value / 3600);
        }

        $params['icon'] = 'fa-solid fa-fire-flame-simple';

        return [
            'number' => $value,
            'label'  => $params['label'],
            'icon'   => $params['icon'],
        ];
    }

    /**
     * Total embodied abiotic depletion potential in antimony equivalent
     *
     * @param array $params
     * @param array $crit
     * @return array
     */
    public static function getEmbodiedAbioticDepletion(array $params = [], array $crit = []): array
    {
        $default_params = [
            'label' => __('Embodied abiotic depletion potential', 'carbon'),
            'icon'  => 'fa-solid fa-temperature-arrow-up',
        ];
        $params = array_merge($default_params, $params);

        if (count($crit['itemtype'] ?? []) === 0) {
            $crit['itemtype'] = PLUGIN_CARBON_TYPES;
        } else {
            $crit['itemtype'] = array_intersect($crit['itemtype'], PLUGIN_CARBON_TYPES);
        }
        $value = self::getSum(EmbodiedImpact::getTable(), 'adp', $params, $crit);
        if ($value === null) {
            $value = 'N/A';
        } else {
            $value = Toolbox::getWeight($value) . __('Sbeq', 'carbon');
        }

        return [
            'number'     => $value,
            'label'      => $params['label'],
            'icon'       => $params['icon'],
        ];
    }

    /**
     * Get usage abiotic depletion potential in antimony equivalent
     *
     * @param array $params
     * @param array $crit
     * @return array
     */
    public static function getUsageAbioticDepletion(array $params = [], array $crit = []): array
    {
        $default_params = [
            'label' => __('Usage abiotic depletion potential', 'carbon'),
            'icon'  => 'fa-solid fa-temperature-arrow-up',
        ];
        $params = array_merge($default_params, $params);
        if (count($crit['itemtype'] ?? []) === 0) {
            $crit['itemtype'] = PLUGIN_CARBON_TYPES;
        } else {
            $crit['itemtype'] = array_intersect($crit['itemtype'], PLUGIN_CARBON_TYPES);
        }

        $value = self::getSum(UsageImpact::getTable(), 'adp', $params, $crit);
        if ($value === null) {
            $value = 'N/A';
        } else {
            $value = Toolbox::getWeight($value) . __('Sbeq', 'carbon');
        }

        return [
            'number' => $value,
            'label'  => $params['label'],
            'icon'   => $params['icon'],
        ];
    }

    /**
     * get Total abiotic depletion potential
     *
     * @param array $params
     * @param array $crit
     * @return array
     */
    public static function getTotalAbioticDepletion(array $params = [], array $crit = []): array
    {
        $default_params = [
            'label' => __('Total abiotic depletion potential', 'carbon'),
            'icon'  => 'fa-solid fa-temperature-arrow-up',
        ];
        $params = array_merge($default_params, $params);
        if (count($crit['itemtype'] ?? []) === 0) {
            $crit['itemtype'] = PLUGIN_CARBON_TYPES;
        } else {
            $crit['itemtype'] = array_intersect($crit['itemtype'], PLUGIN_CARBON_TYPES);
        }

        $embodied_value = self::getSum(EmbodiedImpact::getTable(), 'adp', $params, $crit);
        $usage_value = self::getSum(UsageImpact::getTable(), 'adp', $params, $crit);

        return [
            'data'  => [
                [
                    'label' => __('Embodied abiotic depletion potential', 'carbon'),
                    'number' => $embodied_value,
                ], [
                    'label' => __('Total usage abiotic depletion potential', 'carbon'),
                    'number'  => $usage_value,
                ],
            ],
            'label' => $params['label'],
            'icon'  => $params['icon'],
        ];
    }

    public static function getTotalGlobalWarming(array $params = [], array $crit = [])
    {
        $default_params = [
            'label' => __('Total global warming potential', 'carbon'),
            'icon'  => 'fa-solid fa-temperature-arrow-up',
        ];
        $params = array_merge($default_params, $params);
        if (count($crit['itemtype'] ?? []) === 0) {
            $crit['itemtype'] = PLUGIN_CARBON_TYPES;
        } else {
            $crit['itemtype'] = array_intersect($crit['itemtype'], PLUGIN_CARBON_TYPES);
        }

        $embodied_value = self::getSum(EmbodiedImpact::getTable(), 'gwp', $params, $crit);
        $usage_value = self::getSum(CarbonEmission::getTable(), 'emission_per_day', $params, $crit);

        return [
            'data'  => [
                [
                    'label' => __('Embodied global warming potential', 'carbon'),
                    'number' => $embodied_value,
                ], [
                    'label' => __('Total usage global warming potential', 'carbon'),
                    'number'  => $usage_value,
                ],
            ],
            'label' => $params['label'],
            'icon'  => $params['icon'],
        ];
    }

    /**
     * Get carbon emission per month in the current entity
     * @param array $crit   Plugin specific criteria, used to show data for a single item
     * @param array $params
     *
     * @return array
     */
    public static function getUsageCarbonEmissionPerMonth(array $crit = [], array $params = []): array
    {
        /** @var DBmysql $DB */
        global $DB;

        $default_params = [
            'label'   => __('Consumed energy and carbon emission per month', 'carbon'),
            'icon'  => 'fas fa-computer',
            // 'color' => '#ea9999',
            'apply_filters' => [],
        ];
        $params = array_merge($default_params, $params);
        if (!isset($crit['itemtype'])) {
            $crit['itemtype'] = PLUGIN_CARBON_TYPES;
        } else if (is_string($crit['itemtype'])) {
            $crit['itemtype'] = [$crit['itemtype']];
        }
        $crit['itemtype'] = array_intersect($crit['itemtype'], PLUGIN_CARBON_TYPES);

        if (!isset($params['args']['apply_filters']['dates'][0]) || !isset($params['args']['apply_filters']['dates'][1])) {
            list($start_date, $end_date) = (new Toolbox())->yearToLastMonth(new DateTimeImmutable('now'));
            $params['args']['apply_filters']['dates'][0] = $start_date->format('Y-m-d\TH:i:s.v\Z');
            $params['args']['apply_filters']['dates'][1] = $end_date->format('Y-m-d\TH:i:s.v\Z');
        } else {
            $start_date = DateTime::createFromFormat('Y-m-d\TH:i:s.v\Z', $params['args']['apply_filters']['dates'][0]);
            $end_date   = DateTime::createFromFormat('Y-m-d\TH:i:s.v\Z', $params['args']['apply_filters']['dates'][1]);
        }

        $crit[] = [
            'AND' => [
                ['date' => ['>=', $start_date->format('Y-m-d')]],
                ['date' => ['<', $end_date->format('Y-m-d')]],
            ]
        ];

        $emissions_table = CarbonEmission::getTable();

        $dbUtils = new DbUtils();
        $entityRestrict = $dbUtils->getEntitiesRestrictCriteria($emissions_table, '', '', 'auto');
        $sql_year_month = "DATE_FORMAT(`date`, '%Y-%m')";
        $request = [
            'SELECT'    => [
                'SUM' => [
                    CarbonEmission::getTableField('emission_per_day') . ' AS total_emission_per_month',
                    CarbonEmission::getTableField('energy_per_day') . ' AS total_energy_per_month'
                ],
                @new QueryExpression("$sql_year_month as `date`")
            ],
            'FROM'    => $emissions_table,
            'GROUPBY' => @new QueryExpression($sql_year_month),
            'ORDER'   => @new QueryExpression($sql_year_month),
            'WHERE'   => $entityRestrict + $crit,
        ];
        $filter = self::getFiltersCriteria($emissions_table, $params['apply_filters']);
        $request = array_merge_recursive($request, $filter);
        $result = $DB->request($request);

        $data = [
            'series' => [
                0 => [
                    'data' => []
                ],
                1 => [
                    'data' => []
                ],
            ],
            'labels' => [],
        ];

        // Prepare date format
        $date_format = 'Y F';
        switch ($_SESSION['glpidate_format'] ?? 0) {
            case 0:
                $date_format = 'Y-m';
                break;
            case 1:
            case 2:
                $date_format = 'm-Y';
                break;
        }

        foreach ($result as $row) {
            $date = new DateTime($row['date']);
            $date_formatted = $date->format($date_format);
            $data['xaxis']['categories'][] = $date_formatted;
            $data['series'][0]['data'][] = [
                'x' => $date_formatted,
                'y' => $row['total_emission_per_month'],
            ];
            $data['series'][1]['data'][] = [
                'x' => $date_formatted,
                'y' => $row['total_energy_per_month'],
            ];
            $data['labels'][] = $date_formatted;
        }

        // Scale carbon emission
        $units = [
            __('g', 'carbon'),
            __('Kg', 'carbon'),
            __('t', 'carbon'),
            __('Kt', 'carbon'),
            __('Mt', 'carbon'),
            __('Gt', 'carbon'),
            __('Tt', 'carbon'),
            __('Pt', 'carbon'),
            __('Et', 'carbon'),
            __('Zt', 'carbon'),
            __('Yt', 'carbon'),
        ];
        $scaled = Toolbox::scaleSerie($data['series'][0]['data'], $units);
        $data['series'][0]['name'] =  __('Carbon emission', 'carbon') . ' (' . $scaled['unit'] . __('CO₂eq', 'carbon') . ')';
        $data['series'][0]['data'] = $scaled['serie'];
        $data['series'][0]['unit'] = $scaled['unit'] . __('CO₂eq', 'carbon'); // Not supported by apex charts

        // Scale energy consumption
        $units = [
            __('KWh', 'carbon'),
            __('MWh', 'carbon'),
            __('GWh', 'carbon'),
            __('TWh', 'carbon'),
            __('PWh', 'carbon'),
            __('EWh', 'carbon'),
            __('ZWh', 'carbon'),
            __('YWh', 'carbon'),
        ];
        $scaled = Toolbox::scaleSerie($data['series'][1]['data'], $units);
        $data['series'][1]['name'] = __('Consumed energy', 'carbon') . ' (' . $scaled['unit'] . ')';
        $data['series'][1]['data'] = $scaled['serie'];
        $data['series'][1]['unit'] = $scaled['unit']; // Not supported by apex charts

        // $data = self::getCarbonEmissionPerMonth($params['args'], $crit);

        $data['date_interval'] = [
            $start_date->format($date_format),
            $end_date->format($date_format),
        ];

        return [
            'data'  => $data,
            'label' => $params['label'],
            'icon'  => $params['icon'],
        ];
    }

    public static function getUsageCarbonEmissionlastTwoMonths(array $crit = [], array $params = [])
    {
        $end_date = new DateTime();
        $end_date->setTime(0, 0, 0, 0);
        $end_date->setDate((int) $end_date->format('Y'), (int) $end_date->format('m'), 1); // First day of current month
        $start_date = clone $end_date;
        $start_date = $start_date->sub(new DateInterval("P2M")); // 2 months back from $end_date

        $params['args']['apply_filters']['dates'][0] = $start_date->format('Y-m-d\TH:i:s.v\Z');
        $params['args']['apply_filters']['dates'][1] = $end_date->format('Y-m-d\TH:i:s.v\Z');

        return self::getUsageCarbonEmissionPerMonth($crit, $params);
    }
}
