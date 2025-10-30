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

namespace GlpiPlugin\Carbon;

use Config;
use DBmysql;
use DisplayPreference;
use Migration;
use ProfileRight;
use CronTask as GlpiCronTask;
use Glpi\Dashboard\Dashboard;
use GlpiPlugin\Carbon\CronTask;

class Uninstall
{
    public function uninstall()
    {
        $this->deleteTables();
        $this->deleteConfig();
        $this->deleteAutomaticActions();
        $this->deleteRights();
        $this->deleteDisplayPrefs();
        $this->deleteDashboard();

        return true;
    }

    private function deleteTables()
    {
        /** @var DBmysql $DB */
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

    private function deleteDashboard()
    {
        $dashboard_key = 'plugin_carbon_board';
        $dashboard = new Dashboard();
        $dashboard->getFromDB($dashboard_key);
        if ($dashboard->isNewItem()) {
            return;
        }
        $dashboard->delete($dashboard->fields);
    }
}
