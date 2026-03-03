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

use GlpiPlugin\Carbon\EmbodiedImpact;

/** @var DBmysql $DB */
/** @var Migration $migration */

$display_pref = new DisplayPreference();

$plugin_so_base = 128000;
$group_base = 1200;

//
// EmbodiedImpact search options
//

$rows = $display_pref->find([
    'itemtype' => EmbodiedImpact::class,
    'num'      => 5 // Was GWP, now recalculate flag
]);
$id = $plugin_so_base + $group_base;
foreach ($rows as $row) {
    $row['num'] = $id;
    $display_pref->update($row);
}

$id = $plugin_so_base + $group_base + 2;
$rows = $display_pref->find([
    'itemtype' => EmbodiedImpact::class,
    'num'      => 6 // Was ADP, now recalculate flag
]);
foreach ($rows as $row) {
    $row['num'] = $id;
    $display_pref->update($row);
}

$id = $plugin_so_base + $group_base + 4;
$rows = $display_pref->find([
    'itemtype' => EmbodiedImpact::class,
    'num'      => 7 // Was PE, now recalculate flag
]);
foreach ($rows as $row) {
    $row['num'] = 1204;
    $display_pref->update($row);
}

//
// UsageImpact search options
//
$rows = $display_pref->find([
    'itemtype' => EmbodiedImpact::class,
    'num'      => 128701 // usage GWP
]);
$id = $plugin_so_base + $group_base;
foreach ($rows as $row) {
    $row['num'] = $id;
    $display_pref->update($row);
}

$rows = $display_pref->find([
    'itemtype' => EmbodiedImpact::class,
    'num'      => 128702 // usage GWP qiality
]);
$id = $plugin_so_base + $group_base + 1;
foreach ($rows as $row) {
    $row['num'] = $id;
    $display_pref->update($row);
}

$rows = $display_pref->find([
    'itemtype' => EmbodiedImpact::class,
    'num'      => 128703 // usage ADP
]);
$id = $plugin_so_base + $group_base + 2;
foreach ($rows as $row) {
    $row['num'] = $id;
    $display_pref->update($row);
}

$rows = $display_pref->find([
    'itemtype' => EmbodiedImpact::class,
    'num'      => 128704 // usage ADP qiality
]);
$id = $plugin_so_base + $group_base + 3;
foreach ($rows as $row) {
    $row['num'] = $id;
    $display_pref->update($row);
}

$rows = $display_pref->find([
    'itemtype' => EmbodiedImpact::class,
    'num'      => 128705 // usage PE
]);
$id = $plugin_so_base + $group_base + 4;
foreach ($rows as $row) {
    $row['num'] = $id;
    $display_pref->update($row);
}

$rows = $display_pref->find([
    'itemtype' => EmbodiedImpact::class,
    'num'      => 128706 // usage PE qiality
]);
$id = $plugin_so_base + $group_base + 5;
foreach ($rows as $row) {
    $row['num'] = $id;
    $display_pref->update($row);
}
