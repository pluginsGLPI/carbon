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

use GlpiPlugin\Carbon\CronTask;
use CronTask as GlpiCronTask;

$automatic_actions = [
    [
        'itemtype'  => CronTask::class,
        'name'      => 'LocationCountryCode',
        'frequency' => DAY_TIMESTAMP,
        'options'   => [
            'mode' => GlpiCronTask::MODE_EXTERNAL,
            'allowmode' => GlpiCronTask::MODE_INTERNAL + GlpiCronTask::MODE_EXTERNAL,
            'logs_lifetime' => 30,
            'comment' => __('Find the Alpha3 country code (ISO3166) of locations', 'carbon'),
            'param'   => 10, // Maximum rows to generate per execution
        ]
    ],
    [
        'itemtype'  => CronTask::class,
        'name'      => 'UsageImpact',
        'frequency' => DAY_TIMESTAMP,
        'options'   => [
            'mode' => GlpiCronTask::MODE_EXTERNAL,
            'allowmode' => GlpiCronTask::MODE_INTERNAL + GlpiCronTask::MODE_EXTERNAL,
            'logs_lifetime' => 30,
            'comment' => __('Compute carbon emissions of computers', 'carbon'),
            'param'   => 10000, // Maximum rows to generate per execution
        ]
    ],
    [
        'itemtype'  => CronTask::class,
        'name'      => 'DownloadRte',
        'frequency' => DAY_TIMESTAMP,
        'options'   => [
            'mode' => GlpiCronTask::MODE_EXTERNAL,
            'allowmode' => GlpiCronTask::MODE_INTERNAL + GlpiCronTask::MODE_EXTERNAL,
            'logs_lifetime' => 30,
            'comment' => __('Collect carbon intensities from RTE', 'carbon'),
            'param'   => 10000, // Maximum rows to generate per execution
        ]
    ],
    [
        'itemtype'  => CronTask::class,
        'name'      => 'DownloadElectricityMap',
        'frequency' => DAY_TIMESTAMP / 2, // Twice a day
        'options'   => [
            'mode' => GlpiCronTask::MODE_EXTERNAL,
            'allowmode' => GlpiCronTask::MODE_INTERNAL + GlpiCronTask::MODE_EXTERNAL,
            'logs_lifetime' => 30,
            'comment' => __('Collect carbon intensities from ElectricityMap', 'carbon'),
            'param'   => 10000, // Maximum rows to generate per execution
        ]
    ],
    [
        'itemtype'  => CronTask::class,
        'name'      => 'EmbodiedImpact',
        'frequency' => DAY_TIMESTAMP,
        'options'   => [
            'mode' => GlpiCronTask::MODE_EXTERNAL,
            'allowmode' => GlpiCronTask::MODE_INTERNAL + GlpiCronTask::MODE_EXTERNAL,
            'logs_lifetime' => 30,
            'comment' => __('Compute embodied impact of assets', 'carbon'),
            'param'   => 10000, // Maximum rows to generate per execution
        ]
    ],
];

foreach ($automatic_actions as $action) {
    $task = new GlpiCronTask();
    if ($task->getFromDBByCrit(['name' => $action['name']]) !== false) {
        $task->delete(['id' => $task->getID()]);
    }
    $success = GlpiCronTask::Register(
        $action['itemtype'],
        $action['name'],
        $action['frequency'],
        $action['options']
    );
    if (!$success) {
        throw new \RuntimeException('Error while creating automatic action: ' . $action['name']);
    }
}
