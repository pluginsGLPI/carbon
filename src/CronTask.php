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

use CommonDBTM;
use CommonGLPI;
use Config as GlpiConfig;
use CronTask as GlpiCronTask;
use Geocoder\Exception\QuotaExceeded;
use Geocoder\Geocoder;
use GlpiPlugin\Carbon\DataSource\CarbonIntensity\ClientFactory;
use GlpiPlugin\Carbon\DataSource\CarbonIntensity\ClientInterface;
use GlpiPlugin\Carbon\DataSource\CronTaskProvider;
use GlpiPlugin\Carbon\Impact\Embodied\Engine as EmbodiedEngine;
use GlpiPlugin\Carbon\Impact\Usage\Engine as UsageEngine;
use GlpiPlugin\Carbon\Impact\Usage\UsageImpactInterface as UsageImpactInterface;
use Location as GlpiLocation;

class CronTask extends CommonGLPI
{
    private $getGeocoder = [Location::class, 'getGeocoder'];

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        // Delegate to the client's crontask class the tab name to return
        // But keep here the logic to decide if a tab name shall be returned
        // to reduce class loading
        if (!is_a($item, GlpiCronTask::class)) {
            return '';
        }
        if (!in_array($item->fields['itemtype'], CronTaskProvider::getCronTaskTypes())) {
            return '';
        }
        $client_cron_task = new $item->fields['itemtype']();
        return $client_cron_task->getTabNameForItem($item);
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (is_a($item, GlpiCronTask::class)) {
            /** @var GlpiCronTask $item */
            $cron_task = new self();
            $cron_task->showForCronTask($item);
        }
        return true;
    }

    public function showForCronTask(CommonDBTM $item)
    {
        $itemtype = $item->fields['itemtype'];
        if (!in_array($itemtype, CronTaskProvider::getCronTaskTypes())) {
            return;
        }
        $crontask = new $itemtype();
        $crontask->showForCronTask($item);
    }

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

            case 'DownloadWatttime':
                return [
                    'description' => __('Download carbon emissions from Watttime', 'carbon'),
                    'parameter' => __('Maximum number of entries to download', 'carbon'),
                ];

            case 'UsageImpact':
                return [
                    'description' => __('Compute usage environmental impact for all assets', 'carbon'),
                    'parameter' => __('Maximum number of entries to calculate', 'carbon'),
                ];
            case 'EmbodiedImpact':
                return [
                    'description' => __('Compute embodied environmental impact for all assets', 'carbon'),
                    'parameter' => __('Maximum number of entries to calculate', 'carbon'),
                ];
        }
        return [];
    }

    /**
     * Calculate usage impact for all assets
     *
     * @param GlpiCronTask $task
     * @return int
     */
    public static function cronUsageImpact(GlpiCronTask $task): int
    {
        $task->setVolume(0); // start with zero

        $usage_impacts = Toolbox::getGwpUsageImpactClasses();
        $task->setVolume(0); // start with zero
        $remaining = $task->fields['param'];
        $limit_per_type = (int) floor(($remaining) / count($usage_impacts));
        // Half of job for GWP, the other half for other impacts
        $limit_per_type = max(1, floor($limit_per_type / 2));

        /**
         * Huge quantity of SQL queries will be executed
         * We NEED to check memory usage to avoid running out of memory
         * @see DbMysql::doQuery()
         */
        $memory_limit = Toolbox::getMemoryLimit();

        // Calculate GWP
        $count = 0;
        foreach ($usage_impacts as $usage_impact_type) {
            /** @var UsageImpactInterface $usage_impact */
            $usage_impact = new $usage_impact_type();
            $usage_impact->setLimit($limit_per_type);
            $count = $usage_impact->evaluateItems($usage_impact->getItemsToEvaluate());
            $task->addVolume($count);
        }

        // Calculate other impacts
        $limit = ['LIMIT' => $limit_per_type];
        foreach (PLUGIN_CARBON_TYPES as $itemtype) {
            foreach (UsageImpact::getItemsToEvaluate($itemtype, $limit) as $row) {
                $item = new $itemtype();
                if (!$item->getFromDB($row['id'])) {
                    continue;
                }
                $usage_impact = UsageEngine::getEngineFromItemtype($item);
                if ($usage_impact === null) {
                    // An error occured while configuring the engine
                    continue;
                }
                if ($usage_impact->evaluateItem()) {
                    $count++;
                }

                // Check free memory
                if ($memory_limit && $memory_limit < memory_get_usage()) {
                    // 8 MB memory left, emergency exit
                    // Terminate the task
                    break 2;
                }
            }
        }

        return ($count > 0 ? 1 : 0);
    }

    /**
     * Calculate embodied impact for all assets
     *
     * @param GlpiCronTask $task
     * @return int
     */
    public static function cronEmbodiedImpact(GlpiCronTask $task): int
    {
        $count = 0;
        $embodied_impacts = Toolbox::getEmbodiedImpactClasses();
        $task->setVolume(0); // start with zero
        $remaining = $task->fields['param'];
        $limit_per_type = max(1, floor(($remaining) / count($embodied_impacts)));

        /**
         * Huge quantity of SQL queries will be executed
         * We NEED to check memory usage to avoid running out of memory
         * @see DbMysql::doQuery()
         */
        $memory_limit = Toolbox::getMemoryLimit();

        /** @var int $count count of successfully evaluated assets */
        $count = 0;
        $limit = ['LIMIT' => $limit_per_type];
        foreach (PLUGIN_CARBON_TYPES as $itemtype) {
            foreach (EmbodiedImpact::getItemsToEvaluate($itemtype, $limit) as $row) {
                $item = new $itemtype();
                if (!$item->getFromDB($row['id'])) {
                    continue;
                }
                $embodied_impact = EmbodiedEngine::getEngineFromItemtype($item);
                if ($embodied_impact === null) {
                    // An error occured while configuring the engine
                    continue;
                }
                if ($embodied_impact->evaluateItem()) {
                    $count++;
                }

                // Check free memory
                if ($memory_limit && $memory_limit < memory_get_usage()) {
                    // 8 MB memory left, emergency exit
                    // Terminate the task
                    break 2;
                }
            }
        }

        $task->addVolume($count);
        return ($count > 0 ? 1 : 0);
    }

    /**
     * Automatic action for Watttime datasource
     *
     * @return int
     */
    public static function cronDownloadWatttime(GlpiCronTask $task): int
    {
        $client = ClientFactory::create('Watttime');
        return self::downloadCarbonIntensityFromSource($task, $client, new CarbonIntensity());
    }

    /**
     * Download carbon intensity data from a 3rd party source
     *
     * @param GlpiCronTask $task
     * @param ClientInterface $data_source
     * @param CarbonIntensity $intensity
     * @return int
     */
    public static function downloadCarbonIntensityFromSource(GlpiCronTask $task, ClientInterface $data_source, CarbonIntensity $intensity): int
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
            } catch (QuotaExceeded $e) {
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
                '_boavizta_zone' => $country_code,
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
