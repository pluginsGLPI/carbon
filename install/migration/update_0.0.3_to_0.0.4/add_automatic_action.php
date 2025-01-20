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
