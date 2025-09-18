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

use CronTask as GlpiCronTask;
use Config as GlpiConfig;
use Geocoder\Geocoder;
use GlpiPlugin\Carbon\DataSource\RestApiClient;
use GlpiPlugin\Carbon\DataSource\CarbonIntensityRTE;
use GlpiPlugin\Carbon\DataSource\CarbonIntensityElectricityMap;
use GlpiPlugin\Carbon\DataSource\CarbonIntensityInterface;
use GlpiPlugin\Carbon\Impact\Embodied\Engine as EmbodiedEngine;
use GlpiPlugin\Carbon\Impact\Usage\UsageImpactInterface as UsageImpactInterface;
use GlpiPlugin\Carbon\Impact\Usage\Engine as UsageEngine;
use GlpiPlugin\Carbon\Toolbox;
use Location as GlpiLocation;

class CronTask
{
    private $getGeocoder = [Location::class, 'getGeocoder'];

    /**
     * Get description of an automatic action
     *
     * @param string $name
     * @return array
     */
    public static function cronInfo(string $name)
    {
        switch ($name) {
            case 'LocationCountryCode':
                // Use geocoding to find the country code of the address of a location
                return [
                    'description' => __('Find the Alpha3 country code (ISO3166)', 'carbon'),
                    'parameter' => __('Maximum number of locations to solve', 'carbon'),
                ];

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
        $task->setVolume(0); // start with zero

        $usage_impacts = Toolbox::getGwpUsageImpactClasses();
        $remaining = $task->fields['param'];
        $limit_per_type = (int) floor(($remaining) / count($usage_impacts));
        // Half of job for GWP, the other half for other impacts
        $limit_per_type = max(1, floor($limit_per_type / 2));

        // Calculate GWP
        $count = 0;
        foreach ($usage_impacts as $usage_impact_type) {
            /** @var UsageImpactInterface $usage_impact */
            $usage_impact = new $usage_impact_type();
            $usage_impact->setLimit($limit_per_type);
            $count = $usage_impact->evaluateItems();
            $task->addVolume($count);
        }

        // Calculate other impacts
        $usage_impacts = Toolbox::getUsageImpactClasses();
        foreach ($usage_impacts as $usage_impact_type) {
            /** @ar UsageImpactInterface $usage_impact */
            $usage_impact = UsageEngine::getEngine($usage_impact_type);
            if ($usage_impact === null) {
                continue;
            }
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
        $limit_per_type = max(1, floor(($remaining) / count($embodied_impacts)));
        foreach ($embodied_impacts as $embodied_impact_type) {
            $embodied_impact = EmbodiedEngine::getEngine($embodied_impact_type);
            if ($embodied_impact === null) {
                // An error occured while configuring the engine
                continue;
            }
            $embodied_impact->setLimit($limit_per_type);
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

        $limit_per_zone = max(1, floor(($remaining) / count($zones)));
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

    public static function cronLocationCountryCode(GlpiCronTask $task): int
    {
        $task->setVolume(0); // start with zero
        $plugin_task = new self();
        $solved = $plugin_task->fillIncompleteLocations($task);

        $task->addVolume($solved);
        return 1;
    }

    /**
     * Fill incomplete locations with country code
     *
     * @param GlpiCronTask $task
     * @return int
     */
    public function fillIncompleteLocations(GlpiCronTask $task): int
    {
        // Check if geocoding is enabled
        $enabled = GlpiConfig::getConfigurationValue('plugin:carbon', 'geocoding_enabled');
        if (!$enabled) {
            // If geocoding is not enabled, disable the task
            return 0;
        }

        $result = Location::getIncompleteLocations([
            'LIMIT' => $task->fields['param'],
        ]);

        $geocoder = call_user_func($this->getGeocoder);

        $solved = 0;
        $failure = false;
        foreach ($result as $row) {
            $glpi_location = GlpiLocation::getById($row['id']);
            // Get the country code from the location
            try {
                $location = new Location();
                $country_code = $location->getCountryCode($glpi_location, $geocoder);
            } catch (\Geocoder\Exception\QuotaExceeded $e) {
                // If the quota is exceeded, stop the task
                break;
            } catch (\RuntimeException $e) {
                // If there is a runtime exception, log it and continue
                $failure = true;
                trigger_error($e->getMessage(), E_USER_WARNING);
                continue;
            }
            if (empty($country_code)) {
                $failure = true;
                continue;
            }

            // Set the country code in the location
            $success = $glpi_location->update([
                'id'             => $glpi_location->getID(),
                '_boavizta_zone' => $country_code
            ]);
            if (!$success) {
                $failure = true;
                continue;
            }
            $solved++;

            sleep(1); // Sleep to avoid too many requests in a short time (Nominatim fait use)
        }

        return ($failure ? -$solved : $solved);
    }

    /**
     * Set the geocoder callable. Required for unit tests to mock the geocoder.
     *
     * @param callable $geocoder
     */
    public function setGeocoder(callable $geocoder): void
    {
        $this->getGeocoder = $geocoder;
    }
}
