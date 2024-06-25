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
use DBUtils;
use DisplayPreference;
use Migration;
use ProfileRight;
use CronTask;
use GlpiPlugin\Carbon\ComputerPower;
use GlpiPlugin\Carbon\CarbonEmission;

class Uninstall
{
    private Migration $migration;

    public function __construct(Migration $migration)
    {
        $this->migration = $migration;
    }

    public function uninstall()
    {
        global $DB;

        $itemtypesWihTable = [
            CarbonEmission::class,
            CarbonIntensity::class,
            CarbonIntensitySource::class,
            CarbonIntensityZone::class,
            ComputerPower::class,
            ComputerType::class,
            ComputerUsageProfile::class,
            EnvironnementalImpact::class,
        ];

        $DbUtils = new DBUtils();
        foreach ($itemtypesWihTable as $itemtype) {
            // Check if table exists, needed for forced install use case
            $table = $DbUtils->getTableForItemType($itemtype);
            if ($DB->tableExists($table)) {
                $DB->dropTable($table);
            }
        }

        $this->deleteConfig();
        $this->deleteAutomaticActions();
        $this->deleteRights();
        $this->deleteDisplayPrefs();

        return true;
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
            ComputerPower::class,
            CarbonEmission::class,
        ];

        foreach ($actions as $itemtype) {
            $cron_task = new CronTask();
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
