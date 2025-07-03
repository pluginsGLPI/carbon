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

use GlpiPlugin\Carbon\ComputerUsageProfile;

/** @var DBmysql $DB */
global $DB;

Plugin::loadLang('carbon');
$usage_profiles = [
    [
        'name'       => __('Always on', 'carbon'),
        'time_start' => '00:00:00',
        'time_stop'  => '23:59:00',
        'day_1'      => '1',
        'day_2'      => '1',
        'day_3'      => '1',
        'day_4'      => '1',
        'day_5'      => '1',
        'day_6'      => '1',
        'day_7'      => '1',
    ], [
        'name'       => __('Office hours', 'carbon'),
        'time_start' => '09:00:00',
        'time_stop'  => '18:00:00',
        'day_1'      => '1',
        'day_2'      => '1',
        'day_3'      => '1',
        'day_4'      => '1',
        'day_5'      => '1',
        'day_6'      => '0',
        'day_7'      => '0',
    ],
];
$dbUtil = new DbUtils();
$usage_profile_table = $dbUtil->getTableForItemType(ComputerUsageProfile::class);
foreach ($usage_profiles as $input) {
    $query = $DB->buildInsert($usage_profile_table, $input);
    $DB->doQuery($query);
}
