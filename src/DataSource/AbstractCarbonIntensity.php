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

namespace GlpiPlugin\Carbon\DataSource;

use Config as GlpiConfig;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\CarbonIntensitySource_CarbonIntensityZone;
use GlpiPlugin\Carbon\CarbonIntensityZone;

abstract class AbstractCarbonIntensity implements CarbonIntensityInterface
{
    abstract public function getSourceName(): string;

    abstract public function getDataInterval(): string;

    /**
     * Create the source in the database
     * Should not be called as it shall be created at plugin installation
     *
     * @return CarbonIntensitySource
     */
    protected function getOrCreateSource(): ?CarbonIntensitySource
    {
        $source = new CarbonIntensitySource();
        if (!$source->getFromDBByCrit(['name' => $this->getSourceName()])) {
            $source->add([
                'name' => $this->getSourceName(),
            ]);
            if ($source->isNewItem()) {
                return null;
            }
        }

        return $source;
    }

    /**
     * Download all data for a single day from the datasource
     *
     * @param DateTimeImmutable $day
     * @param string $zone
     * @return array
     *
     * @throws AbortException if an error requires to stop all subsequent fetches
     */
    abstract public function fetchDay(DateTimeImmutable $day, string $zone): array;

    /**
     * Download a range if data from the data source
     *
     * @param DateTimeImmutable $start
     * @param DateTimeImmutable $stop
     * @param string $zone
     * @return array
     *
     * @throws AbortException if an error requires to stop all subsequent fetches
     */
    abstract public function fetchRange(DateTimeImmutable $start, DateTimeImmutable $stop, string $zone): array;

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
        global $DB;

        $source_table = CarbonIntensitySource::getTable();
        $source_fk = CarbonIntensitySource::getForeignKeyField();
        $zone_table = CarbonIntensityZone::getTable();
        $zone_fk = CarbonIntensityZone::getForeignKeyField();
        $source_zone_table = CarbonIntensitySource_CarbonIntensityZone::getTable();
        $iterator = $DB->request([
            'SELECT' => CarbonIntensityZone::getTableField('name'),
            'FROM' => $zone_table,
            'INNER JOIN' => [
                $source_zone_table => [
                    'ON' => [
                        $zone_table => 'id',
                        $source_zone_table => $zone_fk,
                    ]
                ],
                $source_table => [
                    'ON' => [
                        $source_table => 'id',
                        $source_zone_table => $source_fk,
                    ]
                ],
            ],
            'WHERE' => [
                CarbonIntensitySource::getTableField('name') => $this->getSourceName(),
            ] + $crit,
        ]);

        return iterator_to_array($iterator);
    }


    public function fullDownload(string $zone, DateTimeImmutable $start_date, DateTimeImmutable $stop_date, CarbonIntensity $intensity, int $limit = 0): int
    {
        $count = 0;
        $saved = 0;
        // $max_date = $this->getMaxIncrementalAge();
        foreach ($this->sliceDateRangeByMonth($start_date, $stop_date) as $slice) {
            try {
                $data = $this->fetchRange($slice['start'], $slice['stop'], $zone);
            } catch (AbortException $e) {
                break;
            }
            if (!isset($data[$zone])) {
                break;
            }
            $saved = $intensity->save($zone, $this->getSourceName(), $data[$zone]);
            $count += abs($saved);
            if ($limit > 0 && $count >= $limit) {
                return $saved > 0 ? $count : -$count;
            }
        }

        return $saved > 0 ? $count : -$count;
    }

    public function incrementalDownload(string $zone, DateTimeImmutable $start_date, CarbonIntensity $intensity, int $limit = 0): int
    {
        $end_date = new DateTimeImmutable('now');

        $count = 0;
        $saved = 0;
        foreach ($this->sliceDateRangeByDay($start_date, $end_date) as $slice) {
            try {
                $data = $this->fetchDay($slice, $zone);
            } catch (AbortException $e) {
                throw $e;
            }
            $saved = $intensity->save($zone, $this->getSourceName(), $data[$zone]);
            $count += abs($saved);
            if ($limit > 0 && $count >= $limit) {
                return $saved > 0 ? $count : -$count;
            }
        }

        return $saved > 0 ? $count : -$count;
    }

    /**
     * Divide a time range into a group of 1 month time ranges (1 to last day of month)
     * Handles input ranges not matching a month boundary
     *
     * @param DateTimeImmutable $start
     * @param DateTimeImmutable $stop
     * @return \Generator
     */
    protected function sliceDateRangeByMonth(DateTimeImmutable $start, DateTimeImmutable $stop): \Generator
    {
        $real_start = $start->setTime($start->format('H'), 0, 0, 0);
        $real_stop = $stop->setTime($stop->format('H'), 0, 0, 0);
        $slice = [
            'start' => null,
            'stop'  => null,
        ];

        if ($real_start > $real_stop) {
            return;
        }

        $current_date = clone $real_stop;

        // If stop date day is > 1 then return a slice to the begining of the same month
        if ($real_stop->format('d') > 1 || $real_stop->format('H') > 0) {
            $slice['start'] = $real_stop->setDate($stop->format('Y'), $real_stop->format('m'), 1);
            if ($slice['start'] < $real_start) {
                $slice['start'] = $real_start;
            }
            $slice['stop']  = $real_stop;
            yield $slice;
            $current_date = clone $slice['start'];
        }

        // Yield slices for each month ordered backwards
        while ($current_date > $real_start) {
            $slice['stop']  = $current_date;
            $slice['start'] = $current_date->setDate($slice['stop']->format('Y'), $slice['stop']->format('m') - 1, 1);
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
     * @return \Generator
     */
    protected function sliceDateRangeByDay(DateTimeImmutable $start, DateTimeImmutable $stop)
    {
        $real_start = $start;
        $real_stop = $stop->setTime($stop->format('H'), 0, 0);

        $current_date = DateTime::createFromImmutable($real_start);
        while ($current_date <= $real_stop) {
            yield DateTimeImmutable::createFromMutable($current_date);
            $current_date->add(new DateInterval('P1D'));
            $current_date->setTime(0, 0, 0);
        }
    }
}
