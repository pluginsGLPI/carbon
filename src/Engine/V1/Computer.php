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
                GlpiComputer::getTableField('id') => $this->item->getID(),
            ],
        ];

        $result = $DB->request($request);

        if ($result->numrows() == 1) {
            return ComputerUsageProfile::getById($result->current()['id']);
        }

        return null;
    }
}
