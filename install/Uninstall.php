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

namespace GlpiPlugin\Carbon;

use Config;
use DisplayPreference;
use Migration;
use ProfileRight;
use CronTask as GlpiCronTask;
use GlpiPlugin\Carbon\CronTask;

class Uninstall
{
    private Migration $migration;

    public function __construct(Migration $migration)
    {
        $this->migration = $migration;
    }

    public function uninstall()
    {
        $this->deleteTables();
        $this->deleteConfig();
        $this->deleteAutomaticActions();
        $this->deleteRights();
        $this->deleteDisplayPrefs();

        return true;
    }

    private function deleteTables()
    {
        global $DB;

        $iterator = $DB->listTables('glpi_plugin_carbon_%');
        foreach ($iterator as $table) {
            $DB->dropTable($table['TABLE_NAME']);
        }
    }

    private function deleteConfig()
    {
        $config = new Config();
        if (!$config->deleteByCriteria(['context' => 'plugin:carbon'])) {
            throw new \RuntimeException('Error while deleting config');
        }
    }

    private function deleteRights()
    {
        $profile_right = new ProfileRight();
        if (
            !$profile_right->deleteByCriteria([
                'name' => ['LIKE', 'carbon:%'],
            ])
        ) {
            throw new \RuntimeException('Error while deleting rights');
        }
    }

    public function deleteAutomaticActions()
    {
        $actions = [
            CronTask::class,
        ];

        foreach ($actions as $itemtype) {
            $cron_task = new GlpiCronTask();
            $cron_task->deleteByCriteria([
                'itemtype' => $itemtype,
            ]);
        }
    }

    private function deleteDisplayPrefs()
    {
        $displayPreference = new DisplayPreference();
        if (!$displayPreference->deleteByCriteria(['itemtype' => CarbonIntensity::class])) {
            throw new \RuntimeException('Error while deleting display preferences');
        }
    }
}
