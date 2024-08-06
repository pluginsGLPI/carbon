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

use CronTask as GlpiCronTask;
use GlpiPlugin\Carbon\History\Computer as ComputerHistory;
use GlpiPlugin\Carbon\History\Monitor as MonitorHistory;
use GlpiPlugin\Carbon\History\NetworkEquipment as NetworkEquipmentHistory;
use GlpiPlugin\Carbon\DataSource\RestApiClient;
use GlpiPlugin\Carbon\DataSource\CarbonIntensityRTE;
use GlpiPlugin\Carbon\DataSource\CarbonIntensityElectricityMap;
use GlpiPlugin\Carbon\DataSource\CarbonIntensityInterface;

class CronTask
{
    public static function cronHistorize(GlpiCronTask $task): int
    {
        $histories = [
            ComputerHistory::class,
            MonitorHistory::class,
            NetworkEquipmentHistory::class,
        ];
        $task->setVolume(0); // start with zero
        foreach ($histories as $history_type) {
            /** @var AbstractAsset $history */
            $history = new $history_type();
            $history->setLimit(0);
            $count = $history->historizeItems();
            $task->addVolume($count);
        }

        return ($count > 0 ? 1 : 0);
    }

    /**
     * Automatic action for RTE datasource
     *
     * @return int
     */
    public static function cronDownloadRte(GlpiCronTask $task): int
    {
        return self::downloadCarbonIntensityFromSource($task, new CarbonIntensityRTE(new RestApiClient([])), new CarbonIntensity());
    }

    /**
     * Automatic action for ElectricityMap datasource
     *
     * @return int
     */
    public static function cronDownloadElectricityMap(GlpiCronTask $task): int
    {
        return self::downloadCarbonIntensityFromSource($task, new CarbonIntensityElectricityMap(new RestApiClient([])), new CarbonIntensity());
    }

    /**
     * Download carbon intensity data from a 3rd party source
     *
     * @param GlpiCronTask $task
     * @param CarbonIntensityInterface $data_source
     * @param CarbonIntensity $intensity
     * @return integer
     */
    protected static function downloadCarbonIntensityFromSource(GlpiCronTask $task, CarbonIntensityInterface $data_source, CarbonIntensity $intensity): int
    {
        $task->setVolume(0); // start with zero
        $remaining = $task->fields['param'];

        // Check the zones are configured
        // If not, set them up
        if (!$data_source->isZoneSetupComplete()) {
            $done_count = $data_source->createZones();
            $remaining -= $done_count;
            $task->addVolume($done_count);
        }

        $zones = $data_source->getZones();
        if (count($zones) === 0) {
            return 0;
        }

        $limit_per_zone = floor(((int) $remaining) / count($zones));
        $count = 0;
        $failure = false;
        foreach ($zones as $zone_name) {
            $added = $intensity->downloadOneZone($data_source, $zone_name, $limit_per_zone);
            $count += abs($added);
            $failure |= $added < 0;
        }

        if ($count === 0) {
            return 0;
        }
        return ($failure ? -1 : 1);
    }
}
