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

function update005to006(Migration $migration)
{
    global $DB;

    $updateresult       = true;
    $from_version       = '0.0.5';
    $to_version         = '0.0.6';
    $update_dir = __DIR__ . "/update_{$from_version}_to_{$to_version}/";

    //TRANS: %s is the number of new version
    $migration->displayTitle(sprintf(__('Update to %s'), $to_version));
    $migration->setVersion($to_version);

    // New tables from enpty.sql file after the migration
    // If a script requires a new table, it may create it by itself

    $update_scripts = scandir($update_dir);
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
