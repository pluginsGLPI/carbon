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

function update001to100(Migration $migration)
{
    /** @var DBmysql $DB */
    global $DB;

    $updateresult       = true;
    $from_version       = '0.0.1';
    $to_version         = '1.0.0';
    $update_dir = __DIR__ . "/update_{$from_version}_to_{$to_version}/";

    //TRANS: %s is the number of new version
    $migration->displayTitle(sprintf(__('Update to %s'), $to_version));
    $migration->setVersion($to_version);

    // New tables from enpty.sql file after the migration
    // If a script requires a new table, it may create it by itself

    $update_scripts = scandir($update_dir);
    natcasesort($update_scripts);
    foreach ($update_scripts as $update_script) {
        if (preg_match('/\.php$/', $update_script) !== 1) {
            continue;
        }
        require $update_dir . $update_script;
    }

    $dbFile = plugin_carbon_getSchemaPath($to_version);
    if ($dbFile === null || !$DB->runFile($dbFile)) {
        $migration->displayWarning("Error creating tables : " . $DB->error(), true);
        $updateresult = false;
    }

    // ************ Keep it at the end **************
    $migration->executeMigration();

    return $updateresult;
}
