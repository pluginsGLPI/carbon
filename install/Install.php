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
use DirectoryIterator;
use Glpi\Message\MessageType;
use Glpi\Toolbox\Sanitizer;
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
            $this->migration->addWarningMessage("Error creating tables : " . $DB->error());
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

        $this->force_upgrade = array_key_exists('force-upgrade', $args);
        if ($this->force_upgrade) {
            $this->force_upgrade_from_version = PLUGIN_CARBON_VERSION;
            if (array_key_exists('version', $args)) {
                $this->force_upgrade_from_version = $args['version'];
                // Check the version os SEMVER compliant
                $regex = '!^' . self::SEMVER_REGEX . '$!';
                if (preg_match($regex, $this->force_upgrade_from_version) !== 1) {
                    $e = new \RuntimeException('Invalid start version for upgrade.');
                    trigger_error($e->getMessage(), E_USER_WARNING);
                    throw $e;
                }
                if (version_compare($this->force_upgrade_from_version, $oldest_upgradable_version) < 0) {
                    $e = new \RuntimeException('Upgrade is not supported before ' . $this->force_upgrade_from_version . '.');
                    trigger_error($e->getMessage(), E_USER_WARNING);
                    throw $e;
                }
            }
        } else {
            if (version_compare($from_version, $oldest_upgradable_version, 'lt')) {
                $e = new \RuntimeException("Upgrade is not supported before $oldest_upgradable_version!");
                trigger_error($e->getMessage(), E_USER_WARNING);
                throw $e;
            }
        }

        ini_set("max_execution_time", "0");
        $migrations = $this->getMigrationsToDo($from_version);
        foreach ($migrations as $file => $data) {
            $function = $data['function'];
            $target_version = $data['target_version'];
            include_once($file);
            if ($function($this->migration, $args)) {
                // Set the version to the target one s it is complete
                // May be useful if subsequent steps fail and need to run upgrade again, by not running already done steps
                Config::setConfigurationValues('plugin:carbon', ['dbversion' => $target_version]);
            } else {
                return false;
            }
        }

        // If no migration was ran, we still set the version to the current one
        Config::setConfigurationValues('plugin:carbon', ['dbversion' => PLUGIN_CARBON_SCHEMA_VERSION]);

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

    /**
     * Get or create a carbon intensity zone by name
     *
     * @param string $name Name of the zone
     * @param int $is_fallback Is the zone a fallback zone (1) or not (0)
     * @return int ID of the zone
     */
    public static function getOrCreateSource(string $name, int $is_fallback = 1): int
    {
        $source = new CarbonIntensitySource();
        $source->getFromDBByCrit(['name' => $name]);
        if ($source->isNewItem()) {
            $source->add([
                'name' => $name,
                'is_fallback' => $is_fallback,
            ]);
            /** @phpstan-ignore if.alwaysTrue */
            if ($source->isNewItem()) {
                throw new \RuntimeException("Failed to create carbon intensity source '$name' in DB");
            }
        }
        return $source->getID();
    }

    /**
     * Get or create a carbon intensity zone by name
     *
     * @param string $name Name of the zone
     * @param int $source_id ID of the source to associate with the zone
     * @return int ID of the zone
     */
    public static function getOrCreateZone(string $name, int $source_id): int
    {
        $zone = new Zone();
        $zone->getFromDBByCrit(['name' => $name]);
        if ($zone->isNewItem()) {
            $zone->add([
                'name' => $name,
                'plugin_carbon_carbonintensitysources_id_historical' => $source_id,
            ]);
            /** @phpstan-ignore if.alwaysTrue */
            if ($zone->isNewItem()) {
                throw new \RuntimeException("Failed to create zone '$name' in DB");
            }
        }

        return $zone->getID();
    }

    /**
     * Link a carbon intensity source to a zone
     *
     * @param int $source_id ID of the carbon intensity source
     * @param int $zone_id ID of the zone
     * @return int ID of the link
     */
    public static function linkSourceZone(int $source_id, int $zone_id): int
    {
        $source_zone = new CarbonIntensitySource_Zone();
        $source_zone->getFromDBByCrit([
            'plugin_carbon_carbonintensitysources_id' => $source_id,
            'plugin_carbon_zones_id'                  => $zone_id,
        ]);
        if ($source_zone->isNewItem()) {
            $source_zone->add([
                'plugin_carbon_carbonintensitysources_id' => $source_id,
                'plugin_carbon_zones_id'                  => $zone_id,
            ]);
        }

        return $source_zone->getID();
    }
}
