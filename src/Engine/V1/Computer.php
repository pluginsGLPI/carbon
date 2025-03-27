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

use Computer as GlpiComputer;
use ComputerModel;
use ComputerType as GlpiComputerType;
use DBmysql;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\UsageInfo;
use GlpiPlugin\Carbon\ComputerType;

/**
 * Compute CO2 emission of a computer
 */
class Computer extends AbstractSwitchable
{
    protected static string $itemtype = GlpiComputer::class;
    protected static string $type_itemtype  = GlpiComputerType::class;
    protected static string $model_itemtype = ComputerModel::class;
    protected static string $plugin_type_itemtype = ComputerType::class;


    public function getUsageProfile(): ?ComputerUsageProfile
    {
        /** @var DBmysql $DB */
        global $DB;

        $computers_table = GlpiComputer::getTable();
        $usageinfo_table = UsageInfo::getTable();
        $computerUsageProfile_table = ComputerUsageProfile::getTable();

        $request = [
            'SELECT' => ComputerUsageProfile::getTableField('id'),
            'FROM' => $computers_table,
            'INNER JOIN' => [
                $usageinfo_table => [
                    'FKEY'   => [
                        $computers_table  => 'id',
                        $usageinfo_table => 'items_id',
                        [
                            'AND' => [
                                UsageInfo::getTableField('itemtype') => GlpiComputer::class,
                            ]
                        ]
                    ]
                ],
                $computerUsageProfile_table => [
                    'FKEY'   => [
                        $usageinfo_table  => 'plugin_carbon_computerusageprofiles_id',
                        $computerUsageProfile_table => 'id',
                    ]
                ]
            ],
            'WHERE' => [
                GlpiComputer::getTableField('id') => $this->items_id,
            ],
        ];

        $result = $DB->request($request);

        if ($result->numrows() == 1) {
            return ComputerUsageProfile::getById($result->current()['id']);
        }

        return null;
    }
}
