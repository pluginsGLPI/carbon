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
