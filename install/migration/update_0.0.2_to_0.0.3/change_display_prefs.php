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

/** @var DBmysql $DB */
global $DB;

$itemtype = 'GlpiPlugin\\Carbon\\CarbonIntensity';
$map = [
    4 => 10401,
    5 => 10402,
    6 => 10403,
];
foreach ($map as $src => $dst) {
    /** @var Migration $migration  */
    $migration->changeSearchOption($itemtype, $src, $dst);
}

$itemtype = 'GlpiPlugin\\Carbon\\ComputerUsageProfile';
$map = [
    6   => 10101,
    7   => 10102,
    201 => 10110,
    202 => 10111,
    203 => 10112,
    204 => 10113,
    205 => 10114,
    206 => 10115,
    207 => 10116,
];
foreach ($map as $src => $dst) {
    $migration->changeSearchOption($itemtype, $src, $dst);
}

$itemtype = 'GlpiPlugin\\Carbon\\CarbonIntensityZone';
$map = [
    11 => 10301,
];
foreach ($map as $src => $dst) {
    $migration->changeSearchOption($itemtype, $src, $dst);
}

$itemtype = 'GlpiPlugin\\Carbon\\EnvironnementalImpact';
$map = [
    5 => 10201,
    6 => 10202,
];
foreach ($map as $src => $dst) {
    $migration->changeSearchOption($itemtype, $src, $dst);
}
