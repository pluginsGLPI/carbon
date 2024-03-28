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

use GlpiPlugin\Carbon\Power;
use GlpiPlugin\Carbon\PowerModel;
use GlpiPlugin\Carbon\PowerModel_ComputerModel;
use GlpiPlugin\Carbon\PowerModelCategory;
use GlpiPlugin\Carbon\PowerData;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\Config;

use DBUtils;

/**
 * Plugin install process
 *
 * @return boolean
 */
function plugin_carbon_install()
{
    global $DB;

    $config = new Config();
    $config->setConfigurationValues('plugin:carbon', ['configuration' => false]);

    $migration = new Migration(PLUGIN_CARBON_VERSION);

    $dbFile = plugin_carbon_getSchemaPath();
    if ($dbFile === null || !$DB->runFile($dbFile)) {
        $migration->displayWarning("Error creating tables : " . $DB->error(), true);
        die('Giving up');
     }

    PowerData::install($migration);
    Config::install($migration);

    CronTask::Register(
        Power::class,
        'ComputePowersTask',
        DAY_TIMESTAMP,
        [
            'mode' => CronTask::MODE_INTERNAL,
            'allowmode' => CronTask::MODE_INTERNAL + CronTask::MODE_EXTERNAL,
            'logs_lifetime' => 30,
            'comment' => __('Computes power consumption of computers', 'carbon'),
        ]
    );

    CronTask::Register(
        CarbonEmission::class,
        'ComputeCarbonEmissionsTask',
        DAY_TIMESTAMP,
        [
            'mode' => CronTask::MODE_INTERNAL,
            'allowmode' => CronTask::MODE_INTERNAL + CronTask::MODE_EXTERNAL,
            'logs_lifetime' => 30,
            'comment' => __('Computes carbon emissions of computers', 'carbon'),
        ]
    );

    return true;
}

/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_carbon_uninstall()
{
    global $DB;

    $migration = new Migration(PLUGIN_CARBON_VERSION);
    $itemtypesWihTable = [
        CarbonEmission::class,
        PowerModelCategory::class,
        PowerModel::class,
        PowerModel_ComputerModel::class,
        Power::class,
    ];
    $DbUtils = new DBUtils();
    foreach ($itemtypesWihTable as $itemtype) {
        $DB->dropTable($DbUtils->getTableForItemType($itemtype));
    }

    PowerData::uninstall($migration);
    Config::uninstall($migration);

    return true;
}

function plugin_carbon_getDropdown()
{
    return [PowerModelCategory::class => __('Carbon Plugin - Power model categories', 'carbon')];
}

function plugin_carbon_getAddSearchOptions($itemtype)
{
    $sopt = [];

    if (in_array($itemtype, PLUGIN_CARBON_TYPES)) {
        $sopt[] = [
            'id' => 2222,
            'table'        => Power::getTable(),
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
    }

    return $sopt;
}
