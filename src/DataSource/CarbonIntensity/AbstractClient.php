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

namespace GlpiPlugin\Carbon\DataSource\CarbonIntensity;

use Config as GlpiConfig;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use DBmysql;
use Generator;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\Source;
use GlpiPlugin\Carbon\Source_Zone;
use GlpiPlugin\Carbon\Toolbox;
use GlpiPlugin\Carbon\Zone;
use Symfony\Component\Console\Helper\ProgressBar;

abstract class AbstractClient implements ClientInterface
{
    protected int $step;

    protected bool $use_cache = true;

    abstract public function getSourceName(): string;

    abstract public function getDataInterval(): string;

    abstract protected function formatOutput(array $response, int $step): array;

    /**
     * Download all data for a single day from the datasource
     *
     * @param DateTimeImmutable $day
     * @param Source_Zone $source_zone
     * @return array
     *
     * @throws AbortException if an error requires to stop all subsequent fetches
     */
    abstract public function fetchDay(DateTimeImmutable $day, Source_Zone $source_zone): array;

    /**
     * Download a range if data from the data source
     *
     * @param DateTimeImmutable $start
     * @param DateTimeImmutable $stop
     * @param Source_Zone $source_zone
     * @return array
     *
     * @throws AbortException if an error requires to stop all subsequent fetches
     */
    abstract public function fetchRange(DateTimeImmutable $start, DateTimeImmutable $stop, Source_Zone $source_zone): array;

    public function disableCache()
    {
        $this->use_cache = false;
    }

    /**
     * Key of the configuration value that indicates if the full download is complete
     *
     * @return string
     */
    public function getConfigFetchCompleteName(string $zone_name): string
    {
        return $this->getSourceName() . '_download_' . $zone_name . '_complete';
    }

    public function getConfigZoneSetupCompleteName(): string
    {
        return $this->getSourceName() . '_zone_setup_complete';
    }

    public function isZoneSetupComplete(): bool
    {
        $config = $this->getConfigZoneSetupCompleteName();
        $value = GlpiConfig::getConfigurationValue('plugin:carbon', $config);
        if ($value === null || $value === '0' || $value === '') {
            return false;
        }

        return true;
    }

    protected function setZoneSetupComplete()
    {
        $config = $this->getConfigZoneSetupCompleteName();
        GlpiConfig::setConfigurationValues('plugin:carbon', [$config => 1]);
    }

    public function isZoneDownloadComplete(string $zone_name): bool
    {
        $config = $this->getConfigFetchCompleteName($zone_name);
        $value = GlpiConfig::getConfigurationValue('plugin:carbon', $config);
        if ($value === null || $value === '0' || $value === '') {
            return false;
        }

        return true;
    }

    public function getZones(array $crit = []): array
    {
        /** @var DBmysql $DB */
        global $DB;

        $source_table = Source::getTable();
        $source_fk = Source::getForeignKeyField();
        $zone_table = Zone::getTable();
        $zone_fk = Zone::getForeignKeyField();
        $source_zone_table = Source_Zone::getTable();
        $iterator = $DB->request([
            'SELECT' => [
                Zone::getTableField('name'),
            ],
            'FROM' => $zone_table,
            'INNER JOIN' => [
                $source_zone_table => [
                    'ON' => [
                        $zone_table => 'id',
                        $source_zone_table => $zone_fk,
                    ],
                ],
                $source_table => [
                    'ON' => [
                        $source_table => 'id',
                        $source_zone_table => $source_fk,
                    ],
                ],
            ],
            'WHERE' => [
                Source::getTableField('name') => $this->getSourceName(),
            ] + $crit,
        ]);

        return iterator_to_array($iterator);
    }

    public function getSourceZones(array $crit = []): array
    {
        /** @var DBmysql $DB */
        global $DB;

        $source_table = Source::getTable();
        $source_fk = Source::getForeignKeyField();
        $zone_table = Zone::getTable();
        $zone_fk = Zone::getForeignKeyField();
        $source_zone_table = Source_Zone::getTable();
        $iterator = $DB->request([
            'SELECT' => [
                getTableForItemType(Source_Zone::class) . '.*',
            ],
            'FROM' => $zone_table,
            'INNER JOIN' => [
                $source_zone_table => [
                    'ON' => [
                        $zone_table => 'id',
                        $source_zone_table => $zone_fk,
                    ],
                ],
                $source_table => [
                    'ON' => [
                        $source_table => 'id',
                        $source_zone_table => $source_fk,
                    ],
                ],
            ],
            'WHERE' => [
                Source::getTableField('name') => $this->getSourceName(),
            ] + $crit,
        ]);

        return iterator_to_array($iterator);
    }

    public function fullDownload(Source_Zone $source_zone, DateTimeImmutable $start_date, DateTimeImmutable $stop_date, CarbonIntensity $intensity, int $limit = 0, ?ProgressBar $progress_bar = null): int
    {
        if ($start_date >= $stop_date) {
            return 0;
        }
        // Round start date to beginning of month
        $start_date = $start_date->setTime(0, 0, 0, 0)->setDate((int) $start_date->format('Y'), (int) $start_date->format('m'), 1);
        $stop_date = $stop_date->setTime(0, 0, 0, 0)->setDate((int) $stop_date->format('Y'), (int) $stop_date->format('m'), 1);
        $count = 0;
        $saved = 0;
        if ($start_date == $stop_date) {
            $stop_date = $stop_date->add(new DateInterval('P1M'));
        }

        /**
         * Huge quantity of SQL queries will be executed
         * We NEED to check memory usage to avoid running out of memory
         * @see DBmysql::doQuery()
         */
        $memory_limit = Toolbox::getMemoryLimit();

        // Traverse each month from start_date to end_date
        $current_date = DateTime::createFromImmutable($start_date);
        while ($current_date < $stop_date) {
            $next_month = clone $current_date;
            $next_month->add(new DateInterval('P1M'));
            try {
                $data = $this->fetchRange(
                    DateTimeImmutable::createFromMutable($current_date),
                    DateTimeImmutable::createFromMutable($next_month),
                    $source_zone
                );
            } catch (AbortException $e) {
                break;
            }
            if (count($data) > 0) {
                $data = $this->formatOutput($data, $this->step);
                $saved = $intensity->save($source_zone, $data);
                if ($progress_bar) {
                    $progress_bar->advance($saved);
                }

                $count += abs($saved);
            }
            if ($limit > 0 && $count >= $limit) {
                return $saved > 0 ? $count : -$count;
            }
            if ($memory_limit && $memory_limit < memory_get_usage()) {
                // 8 MB memory left, emergency exit
                return $saved > 0 ? $count : -$count;
            }
            $current_date = $next_month;
        }
        return $saved > 0 ? $count : -$count;
    }

    public function incrementalDownload(Source_Zone $source_zone, DateTimeImmutable $start_date, CarbonIntensity $intensity, int $limit = 0): int
    {
        $end_date = new DateTimeImmutable('now');

        $count = 0;
        $saved = 0;
        foreach ($this->sliceDateRangeByDay($start_date, $end_date) as $slice) {
            try {
                $data = $this->fetchDay($slice, $source_zone);
            } catch (AbortException $e) {
                throw $e;
            }
            $data = $this->formatOutput($data, $this->step);
            $saved = $intensity->save($source_zone, $data);
            $count += abs($saved);
            if ($limit > 0 && $count >= $limit) {
                return $saved > 0 ? $count : -$count;
            }
        }

        return $saved > 0 ? $count : -$count;
    }

    /**
     * Divide a time range into a group of 1 month time ranges (1st day of month to 1st day of next month)
     * Range must be processed as [start; stop[
     * Handles input ranges not matching a month boundary
     *
     * @param DateTimeImmutable $start
     * @param DateTimeImmutable $stop
     * @return Generator
     */
    protected function sliceDateRangeByMonth(DateTimeImmutable $start, DateTimeImmutable $stop): Generator
    {
        $real_start = $start->setTime((int) $start->format('H'), 0, 0, 0);
        $real_stop = $stop->setTime((int) $stop->format('H'), 0, 0, 0);
        $slice = [
            'start' => null,
            'stop'  => null,
        ];

        if ($real_start > $real_stop) {
            return;
        }

        $current_date = clone $real_stop;

        // If stop date day is > 1 then return a slice to the begining of the same month
        if ((int) $real_stop->format('d') > 1 || (int) $real_stop->format('H') > 0) {
            $slice['start'] = $real_stop->setDate((int) $stop->format('Y'), (int) $real_stop->format('m'), 1);
            $slice['start'] = $slice['start']->setTime(0, 0, 0, 0);
            if ($slice['start'] < $real_start) {
                $slice['start'] = $real_start;
            }
            $slice['stop'] = $real_stop;
            yield $slice;
            $current_date = clone $slice['start'];
        }

        // Yield slices for each month ordered backwards
        while ($current_date > $real_start) {
            $slice['stop']  = $current_date;
            $slice['start'] = $current_date->setDate((int) $slice['stop']->format('Y'), (int) $slice['stop']->format('m') - 1, 1);
            if ($slice['start'] < $real_start) {
                $slice['start'] = $real_start;
            }
            yield $slice;
            $current_date = clone $slice['start'];
        }
    }

    /**
     * Divide a time range into a group of 1 day time ranges
     *
     * @param DateTimeImmutable $start
     * @param DateTimeImmutable $stop
     * @return Generator
     */
    protected function sliceDateRangeByDay(DateTimeImmutable $start, DateTimeImmutable $stop)
    {
        $real_stop = $stop->setTime((int) $stop->format('H'), 0, 0);

        $current_date = DateTime::createFromImmutable($start);
        while ($current_date <= $real_stop) {
            yield DateTimeImmutable::createFromMutable($current_date);
            $current_date->add(new DateInterval('P1D'));
            $current_date->setTime(0, 0, 0);
        }
    }

    protected function toggleZoneDownload(Zone $zone, Source $source, ?bool $state): bool
    {
        $source_zone = new Source_Zone();
        $source_zone->getFromDBByCrit([
            $zone->getForeignKeyField() => $zone->getID(),
            $source->getForeignKeyField() => $source->getID(),
        ]);
        return $source_zone->toggleZone($state);
    }

    protected function getCacheFilename(string $base_dir, DateTimeImmutable $start, DateTimeImmutable $end, DateTimeZone $timezone): string
    {
        $timezone_name = $timezone->getName();
        $timezone_name = str_replace('/', '-', $timezone_name);
        return sprintf(
            '%s/%s_%s_%s.json',
            $base_dir,
            $timezone_name,
            $start->format('Y-m-d'),
            $end->format('Y-m-d')
        );
    }
}
