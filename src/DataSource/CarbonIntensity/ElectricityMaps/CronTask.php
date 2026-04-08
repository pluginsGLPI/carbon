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

namespace GlpiPlugin\Carbon\DataSource\CarbonIntensity\ElectricityMaps;

use CommonGLPI;
use CronTask as GlpiCronTask;
use GlpiPlugin\Carbon\DataSource\AbstractCronTask;
use GlpiPlugin\Carbon\DataSource\CronTaskInterface;

class CronTask extends AbstractCronTask implements CronTaskInterface
{
    protected static string $client_name = 'ElectricityMaps';

    protected static string $downloadMethod = 'DownloadElectricityMaps';

    public static function getIcon()
    {
        return 'fa-solid fa-gears';
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        return self::createTabEntry(__('Resource diagnosis', 'carbon'), 0);
    }

    public static function enumerateTasks(): array
    {
        // TODO: This data shoud replace the occurrence in CronTask::cronInfo()
        return [
            [
                'itemtype'    => self::class,
                'name'        => self::$downloadMethod,
                'frequency'   => DAY_TIMESTAMP / 2,
                'options'     => [
                    'mode'          => GlpiCronTask::MODE_EXTERNAL,
                    'allowmode'     => GlpiCronTask::MODE_INTERNAL + GlpiCronTask::MODE_EXTERNAL,
                    'logs_lifetime' => 30,
                    'comment'       => __('Collect carbon intensities from Electricity Maps', 'carbon'),
                    'param'         => 10000, // Maximum rows to generate per execution
                ],
            ],
        ];
    }

    /**
     * Get description of an automatic action
     *
     * @param string $name
     * @return array
     */
    public static function cronInfo(string $name): array
    {
        switch ($name) {
            case self::$downloadMethod:
                return [
                    'description' => __('Download carbon emissions from Electricity Maps', 'carbon'),
                    'parameter' => __('Maximum number of entries to download', 'carbon'),
                ];
        }
        return [];
    }
}
