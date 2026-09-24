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

namespace GlpiPlugin\Carbon\DataSource;

use DirectoryIterator;

class CronTaskProvider
{
    /**
     * Get all the cron task types available in the plugin.
     *
     * @param array<DirectoryIterator> $subdirs The subdirectories to search for cron tasks.
     * @return array<string, class-string<CronTaskInterface>> An associative array where the keys are the cron task names and the values are the fully qualified class names of the cron tasks.
     */
    public static function getCronTaskTypes(array $subdirs): array
    {
        static $types = [];
        if (!empty($types)) {
            return $types;
        }
        foreach ($subdirs as $subdir) {
            foreach ($subdir as $connector_dir) {
                if ($connector_dir->isDot() || !$connector_dir->isDir()) {
                    continue;
                }
                $type_dir = basename(dirname($connector_dir->getPathname()));
                $dir = $connector_dir->getBasename();
                $class_name = 'GlpiPlugin\\Carbon\\DataSource\\' . $type_dir . '\\' . $dir . '\\CronTask';
                if (!class_exists($class_name)) {
                    continue;
                }
                if (!is_subclass_of($class_name, CronTaskInterface::class)) {
                    continue;
                }
                $types[$dir] = $class_name;
            }
        }

        return $types;
    }

    /**
     * Get the directories containing cron tasks.
     *
     * @return array<DirectoryIterator> The directories containing cron tasks.
     */
    public static function getCronTaskDirectories(): array
    {
        return [
            new DirectoryIterator(__DIR__ . '/CarbonIntensity'),
            new DirectoryIterator(__DIR__ . '/Lca'),
        ];
    }
}
