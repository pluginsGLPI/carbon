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
use DBmysql;
use DirectoryIterator;
use Migration;
use Plugin;

class Install
{
    /**
     * GLPI Migration instance
     *
     * @var Migration
     */
    private Migration $migration;

    /**
     * Force upgrade from the previous version to the currrent one
     *
     * @var boolean
     */
    private bool $force_upgrade = false;

    /**
     * Version to upgrade from, when upgrade is forced
     *
     * @var string
     */
    private string $force_upgrade_from_version = '';

    /**
     * Oldest version that can be upgraded
     *
     * @var string
     */
    private const OLDEST_UPGRADABLE_VERSION = '0.0.0';

    /**
     * Regular expression for semver version : 3 numbers separated wit a dot
     *
     * @var string
     */
    private const SEMVER_REGEX = '\d+\.\d+\.(?:\d+|x)';

    public function __construct(Migration $migration)
    {
        $this->migration = $migration;
    }

    /**
     * Get installed version of the plugin, from database information
     */
    public static function detectVersion(): string
    {
        $version = Config::getConfigurationValue('plugin:carbon', 'dbversion');
        if ($version === null) {
            return '0.0.0';
        }

        return $version;
    }

    /**
     * Fresh install of the plugin
     *
     * @param array $args
     * @return boolean
     */
    public function install(array $args = []): bool
    {
        /** @var DBmysql $DB */
        global $DB;

        $dbFile = plugin_carbon_getSchemaPath();
        if ($dbFile === null || !$DB->runFile($dbFile)) {
            $this->migration->displayWarning("Error creating tables : " . $DB->error(), true);
            return false;
        }

        // Execute all install sub tasks
        $install_dir = __DIR__ . '/install/';
        $update_scripts = scandir($install_dir);
        $migration = $this->migration; // Used in called scripts in for loop
        foreach ($update_scripts as $update_script) {
            if (preg_match('/\.php$/', $update_script) !== 1) {
                continue;
            }
            require $install_dir . $update_script;
        }

        Config::setConfigurationValues('plugin:carbon', ['dbversion' => PLUGIN_CARBON_SCHEMA_VERSION]);

        return true;
    }

    /**
     * Run an upgrade of the plugin
     *
     * @param  string $from_version previous version of the plugin
     * @param  array  $args         arguments given in command line
     * @return bool
     */
    public function upgrade(string $from_version, array $args = []): bool
    {
        $oldest_upgradable_version = self::OLDEST_UPGRADABLE_VERSION;
        if (version_compare($from_version, $oldest_upgradable_version, 'lt')) {
            $this->migration->displayError("Upgrade is not supported before $oldest_upgradable_version!");
            return false;
        }

        $this->force_upgrade = array_key_exists('force-upgrade', $args);
        if ($this->force_upgrade) {
            $this->force_upgrade_from_version = PLUGIN_CARBON_VERSION;
            if (array_key_exists('version', $args)) {
                $this->force_upgrade_from_version = $args['version'];
                // Check the version os SEMVER compliant
                $regex = '!^' . self::SEMVER_REGEX . '$!';
                if (preg_match($regex, $this->force_upgrade_from_version) !== 1) {
                    throw new \RuntimeException('Invalid start version for upgrade.');
                }
                if (version_compare($this->force_upgrade_from_version, self::OLDEST_UPGRADABLE_VERSION) < 0) {
                    throw new \RuntimeException('Cannot upgrade from unsupported old version: ' . $this->force_upgrade_from_version . '.');
                }
            }
        }

        ini_set("max_execution_time", "0");
        $migrations = $this->getMigrationsToDo($from_version);
        foreach ($migrations as $file => $data) {
            $function = $data['function'];
            $target_version = $data['target_version'];
            include_once($file);
            if ($function($this->migration, $args)) {
                Config::setConfigurationValues('plugin:carbon', ['dbversion' => $target_version]);
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Get migrations that have to be ran.
     *
     * @param string $current_version
     *
     * @return array
     */
    private function getMigrationsToDo(string $current_version): array
    {
        $pattern = '/^update_(?<source_version>\d+\.\d+\.(?:\d+|x))_to_(?<target_version>\d+\.\d+\.(?:\d+|x))\.php$/';
        $plugin_directory = Plugin::getPhpDir('carbon') . '/install/migration';
        $migration_iterator = new DirectoryIterator($plugin_directory);
        $migrations = [];
        foreach ($migration_iterator as $file) {
            $versions_matches = [];
            if ($file->isDir() || $file->isDot() || preg_match($pattern, $file->getFilename(), $versions_matches) !== 1) {
                continue;
            }

            $operator = '>';
            if ($this->force_upgrade) {
                $operator .= '=';
                $current_version = $this->force_upgrade_from_version;
            }
            if (version_compare($versions_matches['target_version'], $current_version, $operator)) {
                $function = preg_replace(
                    '/^update_(\d+)\.(\d+)\.(\d+|x)_to_(\d+)\.(\d+)\.(\d+|x)\.php$/',
                    'update$1$2$3to$4$5$6',
                    $file->getBasename()
                );
                $migrations[$file->getPathname()] = [
                    'function' => $function,
                    'target_version' => $versions_matches['target_version'],
                ];
            }
        }

        ksort($migrations, SORT_NATURAL);

        return $migrations;
    }
}
