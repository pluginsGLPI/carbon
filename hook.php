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

use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\Install;
use GlpiPlugin\Carbon\Uninstall;
use GlpiPlugin\Carbon\EnvironnementalImpact;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\CarbonIntensityZone;
use GlpiPlugin\Carbon\NetworkEquipmentType;
use ComputerType as GlpiComputerType;
use NetworkEquipmentType as GlpiNetworkEquipmentType;

/**
 * Plugin install process
 *
 * @return boolean
 */
function plugin_carbon_install(array $args = []): bool
{
    if (!is_readable(__DIR__ . '/install/Install.php')) {
        return false;
    }
    require_once(__DIR__ . '/install/Install.php');
    $version = Install::detectVersion();
    $install = new Install(new Migration(PLUGIN_CARBON_VERSION));

    try {
        return $install->upgrade($version, $args);
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
function plugin_carbon_uninstall(): bool
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
        ComputerUsageProfile::class  => ComputerUsageProfile::getTypeName(),
        CarbonIntensitySource::class => CarbonIntensitySource::getTypeName(),
        CarbonIntensityZone::class   => CarbonIntensityZone::getTypeName(),
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

    if ($itemtype === Computer::class) {
        $sopt[] = [
            'id' => 2222,
            'table'        => ComputerType::getTable(),
            'field'        => 'power_consumption',
            'name'         => __('Power consumption (W)', 'power consumption (W)'),
            'datatype'     => 'number',
            'linkfield'    => 'computers_id',
            'joinparams' => [
                'jointype' => 'child',
                'beforejoin' => [
                    'table' => GlpiComputerType::getTable(),
                    'joinparams' => [
                        'jointype' => 'child',
                    ]
                ]
            ]
        ];

        $sopt[] = [
            'id' => 2223,
            'table'        => ComputerUsageProfile::getTable(),
            'field'        => 'name',
            'name'         => ComputerUsageProfile::getTypeName(),
            'datatype'     => 'itemlink',
            'linkfield'    => 'computers_id',
            'joinparams' => [
                'jointype' => 'empty',
                'beforejoin' => [
                    'table'    => EnvironnementalImpact::getTable(),
                    'joinparams' => [
                        'jointype' => 'child',
                    ]
                ]
            ]
        ];
    }

    if ($itemtype  == NetworkEquipment::class) {
        $sopt[] = [
            'id' => 2222,
            'table'        => NetworkEquipmentType::getTable(),
            'field'        => 'power_consumption',
            'name'         => __('Power consumption (W)', 'power consumption (W)'),
            'datatype'     => 'number',
            'linkfield'    => 'networkequipments_id',
            'joinparams' => [
                'jointype' => 'child',
                'beforejoin' => [
                    'table' => GlpiNetworkEquipmentType::getTable(),
                    'joinparams' => [
                        'jointype' => 'child',
                    ]
                ]
            ]
        ];

        return $sopt;
    }

    return $sopt;
}
