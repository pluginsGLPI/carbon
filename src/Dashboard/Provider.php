<?php

namespace GlpiPlugin\Carbon\Dashboard;

use Computer;
use ComputerModel;
use ComputerType as GlpiComputerType;
use DbUtils;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\EnvironnementalImpact;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use Location;
use Session;

class Provider
{
    public static function getSum(string $table, string $field)
    {
        global $DB;

        $result = $DB->request([
            'SELECT'    => [
                'SUM' => "$field AS total"
            ],
            'FROM'      => $table,
        ]);

        if ($result->numrows() == 1) {
            return $result->current()['total'];
        }

        return false;
    }

    /**
     * Returns sum of a table field grouped by computer model.
     *
     * @return array of:
     *   - mixed  'number': sum for the model
     *   - string 'url': url to redirect when clicking on the slice
     *   - string 'label': name of the computer model
     */
    public static function getSumEmissionsPerModel(array $where = [])
    {
        global $DB;

        $computers_table = Computer::getTable();
        $computermodels_table = ComputerModel::getTable();
        $carbonemissions_table = CarbonEmission::getTable();

        $request = [
            'SELECT'    => [
                ComputerModel::getTableField('id'),
                ComputerModel::getTableField('name'),
                'SUM' => 'emission_per_day AS total_per_model',
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
                $carbonemissions_table => [
                    'FKEY'   => [
                        $computers_table  => 'id',
                        $carbonemissions_table => 'computers_id',
                    ]
                ],
            ],
            'GROUPBY' => ComputerModel::getTableField('id'),
        ];
        if (!empty($where)) {
            $request['WHERE'] = $where;
        }
        $result = $DB->request($request);

        $data = [];
        foreach ($result as $row) {
            $data[] = [
                'number' => $row['total_per_model'],
                'url' => ComputerModel::getFormURLWithID($row['id']),
                'label' => $row['name'] . " (" . $row['nb_computers_per_model'] . " computers)",
            ];
        }

        return $data;
    }

    public static function getSumPowerPerModel(array $where = [])
    {
        global $DB;

        $computers_table = Computer::getTable();
        $computermodels_table = ComputerModel::getTable();
        $glpiComputertypes_table = GlpiComputerType::getTable();

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
                        $glpiComputertypes_table => 'computertypes_id',
                    ]
                ],
            ],
            'GROUPBY' => ComputerModel::getTableField('id'),
        ];
        if (!empty($where)) {
            $request['WHERE'] = $where;
        }
        $result = $DB->request($request);

        $data = [];
        foreach ($result as $row) {
            $data[] = [
                'number' => $row['total_per_model'],
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
     * @return integer|null
     */
    public static function getHandledComputersCount(array $where = []): ?int
    {
        global $DB;

        $computers_table = Computer::getTable();
        $computermodels_table = ComputerModel::getTable();
        $glpiComputertypes_table = GlpiComputerType::getTable();
        $computertypes_table = ComputerType::getTable();
        $location_table = Location::getTable();
        $environnementalimpact_table = EnvironnementalImpact::getTable();
        $computerUsageProfile_table = ComputerUsageProfile::getTable();

        $request = [
            'SELECT' => [
                'COUNT' => Computer::getTableField('id') . ' AS nb_computers',
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
                            'NOT' => [GlpiComputerType::getTableField('id') => null]],
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

        $request['WHERE'] += $where + (new DbUtils())->getEntitiesRestrictCriteria($computers_table, '', '', true);

        $result = $DB->request($request);

        if ($result->numrows() == 1) {
            return $result->current()['nb_computers'];
        }

        return null;
    }
}