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

use Computer;
use ComputerModel;
use ComputerType as GlpiComputerType;
use QueryExpression;

class ComputerPower
{
    public static function getTypeName($nb = 0)
    {
        return _n("Computer power", "Computers power", $nb, 'power');
    }

    /**
     * Returns the declared power for a computer
     */
    public static function getPower(int $computer_id): int
    {
        global $DB;

        $computertypes_table = ComputerType::getTable();
        $computermodels_table = ComputerModel::getTable();
        $computers_table = Computer::getTable();
        $computermodels_table = ComputerModel::getTable();

        $request = [
            'SELECT'    => [
                Computer::getTableField('id') . ' AS computer_id',
                new QueryExpression('COALESCE('
                    . ComputerModel::getTableField('power_consumption') . ', ' . ComputerType::getTableField('power_consumption') . ', 0) AS power_consumption'),
            ],
            'FROM'      => $computers_table,
            'LEFT JOIN' => [
                $computertypes_table => [
                    'FKEY'   => [
                        $computertypes_table  => 'computertypes_id',
                        $computers_table => 'computertypes_id',
                    ]
                ],
                $computermodels_table => [
                    'FKEY'   => [
                        $computers_table => 'computermodels_id',
                        $computermodels_table  => 'id',
                    ]
                ]
            ],
            'WHERE' => [
                Computer::getTableField('id') => $computer_id,
            ],
        ];
        $result = $DB->request($request);

        if ($result->numrows() == 1) {
            $power = $result->current()['power_consumption'];
            return $power;
        }

        return 0;
    }

    /**
     * @deprecated uses the old data model for powers
     */
    public static function computePowerForComputer(int $computer_id)
    {
        global $DB;

        $computers_table = Computer::getTable();
        $computermodels_table = ComputerModel::getTable();
        $powermodels_computermodels_table = PowerModel_ComputerModel::getTable();
        $powermodels_table = PowerModel::getTable();

        $request = [
            'SELECT'    => [
                Computer::getTableField('id') . ' AS computer_id',
                PowerModel::getTableField('power'),
                //                ComputerModel::getTableField('name') . ' AS computermodel_name',
                //                PowerModel::getTableField('name') . 'AS powermodel_name',
            ],
            'FROM'      => $computers_table,
            'INNER JOIN' => [
                $computermodels_table => [
                    'FKEY'   => [
                        $computermodels_table  => 'id',
                        $computers_table => 'computermodels_id',
                    ]
                ],
                $powermodels_computermodels_table => [
                    'FKEY'   => [
                        $computermodels_table  => 'id',
                        $powermodels_computermodels_table => 'computermodels_id',
                    ]
                ],
                $powermodels_table => [
                    'FKEY'   => [
                        $powermodels_computermodels_table  => 'plugin_carbon_powermodels_id',
                        $powermodels_table => 'id',
                    ]
                ]
            ],
            'WHERE' => [
                Computer::getTableField('id') => $computer_id,
            ],
        ];
        $result = $DB->request($request);

        if ($result->numrows() == 1) {
            $params = [
                'computers_id' => $computer_id,
                'power' => $result->current()['power'],
            ];
            $where = [
                'computers_id' => $computer_id,
            ];
            return $DB->updateOrInsert(self::getTable(), $params, $where);
        }

        return false;
    }

    /**
     * @deprecated uses the old data model for powers
     */
    public static function computePowerForAllComputers()
    {
        global $DB;

        $computers_table = Computer::getTable();

        $request = [
            'SELECT'    => [
                Computer::getTableField('id') . ' AS computer_id',
            ],
            'FROM'      => $computers_table,
        ];
        $result = $DB->request($request);

        $computers_count = $result->numrows();
        foreach ($result as $computer) {
            self::computePowerForComputer($computer['computer_id']);
        }

        return $computers_count;
    }
}
