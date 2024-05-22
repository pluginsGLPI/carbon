<?php

/**
 * -------------------------------------------------------------------------
 * carbon plugin for GLPI
 * Copyright (C) 2022 by the carbon Development Team.
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
 *
 * --------------------------------------------------------------------------
 */

use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\ComputerPower;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\Config;
use GlpiPlugin\Carbon\Install;
use GlpiPlugin\Carbon\Uninstall;
use GlpiPlugin\Carbon\Report;
use GlpiPlugin\Carbon\EnvironnementalImpact;

/**
 * Plugin install process
 *
 * @return boolean
 */
function plugin_carbon_install()
{
    if (!is_readable(__DIR__ . '/install/Install.php')) {
        return false;
    }
    require_once(__DIR__ . '/install/Install.php');
    $install = new Install(new Migration(PLUGIN_CARBON_VERSION));
    try {
        $install->install();
    } catch (\Exception $e) {
        $backtrace = Toolbox::backtrace(false);
        trigger_error($e->getMessage() . PHP_EOL . $backtrace, E_USER_WARNING);
        return false;
    }

    return true;
}

/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_carbon_uninstall()
{
    if (!is_readable(__DIR__ . '/install/Uninstall.php')) {
        return false;
    }
    require_once(__DIR__ . '/install/Uninstall.php');
    $uninstall = new Uninstall(new Migration(PLUGIN_CARBON_VERSION));
    try {
        $uninstall->uninstall();
    } catch (\Exception $e) {
        $backtrace = Toolbox::backtrace(false);
        trigger_error($e->getMessage() . PHP_EOL . $backtrace, E_USER_WARNING);
        return false;
    }

    return true;
}

function plugin_carbon_getDropdown()
{
    return [
        ComputerUsageProfile::class => __('Carbon Plugin - Computer usage profiles', 'carbon'),
    ];
}

/**
 * Undocumented function
 *
 * @param [type] $itemtype
 * @return array
 */
function plugin_carbon_getAddSearchOptionsNew($itemtype): array
{
    $sopt = [];

    if (!in_array($itemtype, PLUGIN_CARBON_TYPES)) {
        return $sopt;
    }

    $sopt[] = [
        'id' => 2222,
        'table'        => ComputerType::getTable(),
        'field'        => 'power',
        'name'         => __('Power (W)', 'power (W)'),
        'datatype'     => 'number',
        'linkfield'    => 'computers_id',
        'joinparams' => [
            'jointype' => 'child'
        ]
    ];
    $sopt[] = [
        'id' => 2223,
        'table'        => CarbonEmission::getTable(),
        'field'        => 'emission_per_day',
        'name'         => __('Carbon emission (kgCO2)', 'carbon emission (kgC02)'),
        'datatype'     => 'number',
        'linkfield'    => 'computers_id',
        'joinparams' => [
            'jointype' => 'child'
        ]
    ];

    return $sopt;
}
