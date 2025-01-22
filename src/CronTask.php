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
use GlpiPlugin\Carbon\DataSource\RestApiClient;
use GlpiPlugin\Carbon\DataSource\CarbonIntensityRTE;
use GlpiPlugin\Carbon\DataSource\CarbonIntensityElectricityMap;
use GlpiPlugin\Carbon\DataSource\CarbonIntensityInterface;
use GlpiPlugin\Carbon\DataSource\Boaviztapi;
use GlpiPlugin\Carbon\Toolbox;

class CronTask
{
    /**
     * Get description of an automatic action
     *
     * @param string $name
     * @return void
     */
    public static function cronInfo(string $name)
    {
        switch ($name) {
            case 'DownloadRte':
                return [
                    'description' => __('Download carbon emissions from RTE', 'carbon'),
                    'parameter' => __('Maximum number of entries to download', 'carbon'),
                ];

            case 'DownloadElectricityMap':
                return [
                    'description' => __('Download carbon emissions from ElectricityMap', 'carbon'),
                    'parameter' => __('Maximum number of entries to download', 'carbon'),
                ];

            case 'UsageImpact':
                return [
                    'description' => __('Compute usage environnemental impact for all assets', 'carbon'),
                    'parameter' => __('Maximum number of entries to calculate', 'carbon'),
                ];
            case 'EmbodiedImpact':
                return [
                    'description' => __('Compute embodied environnemental impact for all assets', 'carbon'),
                    'parameter' => __('Maximum number of entries to calculate', 'carbon'),
                ];
        }
        return [];
    }

    /**
     * Calculate usage impact for all assets
     *
     * @param GlpiCronTask $task
     * @return integer
     */
    public static function cronUsageImpact(GlpiCronTask $task): int
    {
        $count = 0;

        $usage_impacts = Toolbox::getUsageImpactClasses();
        $task->setVolume(0); // start with zero
        $remaining = $task->fields['param'];
        $limit_per_type = floor(((int) $remaining) / count($usage_impacts));
        foreach ($usage_impacts as $usage_impact_type) {
            /** @var AbstractAsset $usage_impact */
            $usage_impact = new $usage_impact_type();
            $usage_impact->setLimit($limit_per_type);
            $count = $usage_impact->evaluateItems();
            $task->addVolume($count);
        }

        return ($count > 0 ? 1 : 0);
    }

    /**
     * Calculate embodied impact for all assets
     *
     * @param GlpiCronTask $task
     * @return integer
     */
    public static function cronEmbodiedImpact(GlpiCronTask $task): int
    {
        $count = 0;

        $embodied_impacts = Toolbox::getEmbodiedImpactClasses();
        $task->setVolume(0); // start with zero
        $remaining = $task->fields['param'];
        $limit_per_type = floor(((int) $remaining) / count($embodied_impacts));
        foreach ($embodied_impacts as $embodied_impact_type) {
            /** @var AbstractAsset $embeddedImpact */
            $embodied_impact = new $embodied_impact_type();
            $embodied_impact->setLimit($limit_per_type);
            $embodied_impact->setClient(new Boaviztapi(new RestApiClient()));
            $count = $embodied_impact->evaluateItems();
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
        $failure = false;

        // Check the zones are configured
        // If not, set them up
        $zones = $data_source->getZones();
        if (!$data_source->isZoneSetupComplete() || count($zones) === 0) {
            $done_count = $data_source->createZones();
            if ($done_count < 0) {
                $failure = true;
            }
            $remaining -= abs($done_count);
            $task->addVolume($done_count);
        }

        $zones = $data_source->getZones(['is_download_enabled' => 1]);
        if (count($zones) === 0) {
            trigger_error(__('No zone to download', 'carbon'), E_USER_WARNING);
            return 0;
        }

        $limit_per_zone = floor(((int) $remaining) / count($zones));
        $count = 0;
        foreach ($zones as $zone) {
            $zone_name = $zone['name'];
            try {
                $added = $intensity->downloadOneZone($data_source, $zone_name, $limit_per_zone);
            } catch (\RuntimeException $e) {
                trigger_error($e->getMessage(), E_USER_WARNING);
                continue;
            }
            $count += abs($added);
            $failure |= $added < 0;
        }

        if ($count === 0) {
            return 0;
        }

        $task->addVolume($count);
        return ($failure ? -1 : 1);
    }
}
