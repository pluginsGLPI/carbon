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
use DateTime;
use DateInterval;
use DbUtils;
use Glpi\Dashboard\Filter;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\EnvironnementalImpact;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\CarbonIntensityZone;
use Location;
use QueryExpression;

class Provider
{
    public static function getSum(string $table, string $field, array $params = [])
    {
        global $DB;

        $request = [
            'SELECT'    => [
                'SUM' => "$field AS total"
            ],
            'FROM'      => $table,
        ];

        $request = array_merge_recursive(
            $request,
            self::getFiltersCriteria($table, $params['args']['apply_filters'])
        );

        $result = $DB->request($request);
        if ($result->numrows() == 1) {
            return $result->current()['total'] ?? 0;
        }

        return false;
    }

    /**
     * Returns sum of of carbon emission grouped by computer model without time limitation.
     *
     * @return array of:
     *   - mixed  'number': sum for the model
     *   - string 'url': url to redirect when clicking on the slice
     *   - string 'label': name of the computer model
     */
    public static function getSumEmissionsPerModel(array $where = [])
    {
        global $DB;

        $computermodels_table = ComputerModel::getTable();
        $carbonemissions_table = CarbonEmission::getTable();

        $entity_restrict = (new DbUtils())->getEntitiesRestrictCriteria($carbonemissions_table, '', '', 'auto');
        $request = [
            'SELECT'    => [
                ComputerModel::getTableField('id'),
                ComputerModel::getTableField('name'),
                'SUM' => 'emission_per_day AS total_per_model',
                new QueryExpression('COUNT(DISTINCT ' . CarbonEmission::getTableField('items_id') . ') AS nb_computers_per_model'),
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
            ] + $entity_restrict,
            'GROUPBY' => ComputerModel::getTableField('id'),
            'ORDER'   => ComputerModel::getTableField('name'),
        ];

        if (!empty($where)) {
            $request['WHERE'] = $request['WHERE'] + $where;
        }
        $result = $DB->request($request);

        $data = [];
        foreach ($result as $row) {
            $count = $row['nb_computers_per_model'];
            $data[] = [
                'number' => CarbonEmission::getWeight($row['total_per_model'], PLUGIN_CARBON_DECIMALS),
                'url' => ComputerModel::getFormURLWithID($row['id']),
                'label' => $row['name'] . " (" . $row['nb_computers_per_model'] . " " . Computer::getTypeName($count) . ")",
            ];
        }

        return $data;
    }

    public static function getSumEmissionsPerType(array $where = [])
    {
        global $DB;

        $glpicomputertypes_table = GlpiComputerType::getTable();
        $carbonemissions_table = CarbonEmission::getTable();

        $entity_restrict = (new DbUtils())->getEntitiesRestrictCriteria($carbonemissions_table, '', '', 'auto');
        $request = [
            'SELECT'    => [
                GlpiComputerType::getTableField('id'),
                GlpiComputerType::getTableField('name'),
                'SUM' => 'emission_per_day AS total_per_type',
                new QueryExpression('COUNT(DISTINCT ' . CarbonEmission::getTableField('items_id') . ') AS nb_computers_per_type'),
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
                'number' => number_format($row['total_per_type'], PLUGIN_CARBON_DECIMALS),
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
     * @return void
     */
    public static function getSumPowerPerModel(array $where = [])
    {
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

    /**
     * Counts the computers which are missing data to compute their
     * environnemental impact
     *
     * @param array $where
     * @return integer|null : count of computers or null if an error occurred
     */
    public static function getUnhandledComputersCount(array $where = []): ?int
    {
        $total = (new DbUtils())->countElementsInTableForMyEntities(Computer::getTable(), $where);
        $complete_computers_count = self::getHandledComputersCount($where);

        if ($complete_computers_count === null || $complete_computers_count > $total) {
            return null;
        }

        return $total - $complete_computers_count;
    }

    /**
     * Count the computers having all required data to computer carbon intensity
     *
     * @param array $where
     * @return array
     */
    public static function getHandledComputersQuery(array $where = []): array
    {
        $computers_table = Computer::getTable();
        $computermodels_table = ComputerModel::getTable();
        $glpiComputertypes_table = GlpiComputerType::getTable();
        $computertypes_table = ComputerType::getTable();
        $location_table = Location::getTable();
        $environnementalimpact_table = EnvironnementalImpact::getTable();
        $computerUsageProfile_table = ComputerUsageProfile::getTable();

        $request = [
            'SELECT' => [
                Computer::getTableField('*'),
            ],
            'FROM' => $computers_table,
            'INNER JOIN' => [
                $computermodels_table => [
                    'FKEY'   => [
                        $computers_table  => 'computermodels_id',
                        $computermodels_table => 'id',
                    ]
                ],
                $glpiComputertypes_table => [
                    'FKEY'   => [
                        $computers_table  => 'computertypes_id',
                        $glpiComputertypes_table => 'id',
                    ]
                ],
                $computertypes_table => [
                    'FKEY'   => [
                        $computertypes_table  => 'computertypes_id',
                        $glpiComputertypes_table => 'id',
                        ['AND' => [
                            'NOT' => [GlpiComputerType::getTableField('id') => null]
                        ],
                        ]
                    ]
                ],
                $location_table => [
                    'FKEY'   => [
                        $computers_table  => 'locations_id',
                        $location_table => 'id',
                    ]
                ],
                $environnementalimpact_table => [
                    'FKEY'   => [
                        $computers_table  => 'id',
                        $environnementalimpact_table => 'computers_id',
                    ]
                ],
                $computerUsageProfile_table => [
                    'FKEY'   => [
                        $environnementalimpact_table  => 'plugin_carbon_computerusageprofiles_id',
                        $computerUsageProfile_table => 'id',
                    ]
                ]
            ],
            'WHERE' => [
                'AND' => [
                    'is_deleted' => 0,
                    ['NOT' => [Location::getTableField('latitude') => '']],
                    ['NOT' => [Location::getTableField('longitude') => '']],
                    ['NOT' => [Location::getTableField('latitude') => null]],
                    ['NOT' => [Location::getTableField('longitude') => null]],
                    ComputerUsageProfile::getTableField('average_load') => ['>', 0],
                    [
                        'OR' => [
                            ComputerType::getTableField('power_consumption') => ['>', 0],
                            ComputerModel::getTableField('power_consumption') => ['>', 0],
                        ],
                    ],
                ],
            ]
        ];

        $entity_restrict = (new DbUtils())->getEntitiesRestrictCriteria($computers_table, '', '', 'auto');
        $request['WHERE'] += $where + $entity_restrict;

        return $request;
    }

    /**
     * Count the computers having all required data to computer carbon intensity
     *
     * @param array $where
     * @return integer|null
     */
    public static function getHandledComputersCount(array $where = []): ?int
    {
        global $DB;

        $request = self::getHandledComputersQuery($where);
        $request['SELECT'] = [
            'COUNT' => Computer::getTableField('id') . ' AS nb_computers',
        ];

        $result = $DB->request($request);

        if ($result->numrows() == 1) {
            return $result->current()['nb_computers'];
        }

        return null;
    }

    /**
     * Get total power of assets having all required data to compute carbon intensity
     *
     * @return integer|null
     */
    public static function getTotalPower(): ?int
    {
        global $DB;

        $request = Provider::getHandledComputersQuery();
        $request['SELECT'] = [
            'SUM' => ComputerType::getTableField('power_consumption') . ' AS total',
        ];

        $result = $DB->request($request);

        if ($result->numrows() == 1) {
            return $result->current()['total'] ?? 0;
        }

        return null;
    }

    /**
     * Get total CO2 emissions for last month (all assets)
     *
     * @return float|null
     */
    public static function getTotalCarbonEmission(): ?float
    {
        global $DB;

        $end_date = new DateTime('now');
        $end_date->setDate($end_date->format('Y'), $end_date->format('m'), 1);

        $start_date = clone $end_date;
        $start_date = $start_date->sub(new DateInterval('P2D'));
        $end_date->setDate($end_date->format('Y'), $end_date->format('m'), 1);

        $end_date = $end_date->format('Y-m-d');
        $start_date = $start_date->format('Y-m-d');
        $emission_table = CarbonEmission::getTable();
        $entity_restrict = (new DbUtils())->getEntitiesRestrictCriteria($emission_table, '', '', 'auto');
        $request = [
            'SELECT' => [
                'SUM' => 'emission_per_day AS total',
            ],
            'FROM'  => CarbonEmission::getTable(),
            'WHERE' => [
                ['date' => ['>=', $start_date]],
                ['date' => ['<', $end_date]],
            ] + $entity_restrict
        ];
        $result = $DB->request($request);

        if ($result->numrows() == 1) {
            return number_format($result->current()['total'] ?? 0, PLUGIN_CARBON_DECIMALS);
        }

        return null;
    }

    public static function getCarbonIntensity(array $params): array
    {
        global $DB;

        $source = new CarbonIntensitySource();
        $zone = new CarbonIntensityZone();
        $source->getFromDBByCrit([
            'name' => 'RTE'
        ]);
        $zone->getFromDBByCrit([
            'name' => 'France'
        ]);

        $request = [
            'SELECT' => [
                CarbonIntensity::getTable() => [
                    'intensity',
                    'date',
                ]
            ],
            'FROM'  => CarbonIntensity::getTable(),
            'WHERE' => [
                // [CarbonIntensity::getTableField('date') => ['>=', date('2024-03-01')]],
                // [CarbonIntensity::getTableField('date') => ['<', date('2024-03-03')]],
                CarbonIntensitySource::getForeignKeyField('sources_id') => $source->getID(),
                CarbonIntensityZone::getForeignKeyField('zones_id') => $zone->getID(),
            ]
        ];

        $filters = self::getFiltersCriteria(CarbonIntensity::getTable(), $params['apply_filters']);
        $request = array_merge_recursive(
            $request,
            $filters
        );

        $data = [
            'labels' => [],
            'series' => [
                [
                    'name' => sprintf('%s %s', $source->fields['name'], $zone->fields['name']),
                    'data' => [],
                ]
            ],
        ];
        $rows = $DB->request($request);
        foreach ($rows as $row) {
            $data['labels'][]            = $row['date'];
            $data['series'][0]['data'][] = number_format($row['intensity'], PLUGIN_CARBON_DECIMALS);
        }

        return $data;
    }

    /**
     * Get carbon emission per month for all assets in the current entity
     *
     * @return array
     */
    public static function getCarbonEmissionPerMonth(): array
    {
        global $DB;

        $emissions_table = CarbonEmission::getTable();

        $start_date = new DateTime('now');
        $start_date->modify('-1 year');

        $dbUtils = new DbUtils();
        $entityRestrict = $dbUtils->getEntitiesRestrictCriteria($emissions_table, '', '', 'auto');
        $year_month = new QueryExpression("DATE_FORMAT(`date`, '%Y-%m')");
        $request = [
            'SELECT'    => [
                'SUM' => CarbonEmission::getTableField('emission_per_day') . ' AS total_emission_per_month',
                new QueryExpression("DATE_FORMAT(`date`, '%Y-%m') as `date`")
            ],
            'FROM'    => $emissions_table,
            'GROUPBY' => $year_month,
            'ORDER'   => $year_month,
            'WHERE'   => [
                'date' => ['>=', $start_date->format('Y-m-d')],
            ] + $entityRestrict,
        ];

        $data = [
            'labels' => [],
            'series' => [
                [
                    'name' => __("gCO₂eq", "carbon"),
                    'data' => []
                ],
            ]
        ];

        $result = $DB->request($request);
        foreach ($result as $row) {
            $date = new DateTime($row['date']);
            $data['labels'][] = $date->format('Y-m');
            $data['series'][0]['data'][] = number_format($row['total_emission_per_month'], PLUGIN_CARBON_DECIMALS);
        }

        return $data;
    }

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
}
