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

// Rename Historize task into UsageImpact
$crontask = new GlpiCronTask();
$crontask->getFromDBByCrit([
    'itemtype' => CronTask::class,
    'name'     => 'Historize'
]);

if (!$crontask->isNewItem()) {
    $crontask->update([
        'id'   => $crontask->getID(),
        'name' => 'UsageImpact',
    ]);
}

// Add new tasks
$automatic_actions = [
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
