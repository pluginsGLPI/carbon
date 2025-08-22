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

use CommonDropdown;
use DateTime;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DBmysql;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\Zone;
use GlpiPlugin\Carbon\DataSource\CarbonIntensityInterface;
use Glpi\DBAL\QueryParam;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Some data sources
 *
 * Carbon intensity of electricity productiion for many countries, yearly
 *including a value for the whole world
 * https://ourworldindata.org/grapher/carbon-intensity-electricity?tab=chart
 */

/**
 * Undocumented class
 */
class CarbonIntensity extends CommonDropdown
{
    private const MIN_HISTORY_LENGTH = '13 months ago';

    public static $rightname = 'carbon:report';

    public static function getTypeName($nb = 0)
    {
        return __("Carbon intensity", 'carbon');
    }

    public static function getIcon(): string
    {
        return 'fa-solid fa-solar-panel';
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        // Name is useless; do not show it. The column is required to avoid SQL errors, all dropdown must have a name column
        // $tab[1]['datatype'] = 'text'; // To show 'name' column but make it not clickable
        unset($tab[1]);

        $table = self::getTable();

        $tab[] = [
            'id'                 => '3',
            'table'              => $table,
            'field'              => 'date',
            'name'               => __('Emission date', 'carbon'),
            'massiveaction'      => false, // implicit field is id
            'datatype'           => 'datetime',
        ];

        $tab[] = [
            'id'                 => SearchOptions::CARBON_INTENSITY_SOURCE,
            'table'              => CarbonIntensitySource::getTable(),
            'field'              => 'name',
            'name'               => CarbonIntensitySource::getTypeName(1),
            'massiveaction'      => false, // implicit field is id
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => SearchOptions::CARBON_INTENSITY_ZONE,
            'table'              => Zone::getTable(),
            'field'              => 'name',
            'name'               => Zone::getTypeName(1),
            'massiveaction'      => false, // implicit field is id
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => SearchOptions::CARBON_INTENSITY_INTENSITY,
            'table'              => $table,
            'field'              => 'intensity',
            'name'               => __('Intensity', 'carbon'),
            'massiveaction'      => false, // implicit field is id
            'datatype'           => 'decimal',
            'unit'               => 'gCO<sub>2</sub>eq/KWh',
        ];

        return $tab;
    }

    /**
     * get carbon intensity dates for a source and a zone
     *
     * @param string $zone_name   Zone to examinate
     * @param string $source_name Source to examinate
     * @return array
     */
    private function getKnownDatesQuery(string $zone_name, string $source_name)
    {
        $intensity_table = CarbonIntensity::getTable();
        $source_table = CarbonIntensitySource::getTable();
        $zone_table   = Zone::getTable();
        return [
            'SELECT' => [$intensity_table => ['id', 'date']],
            'FROM'   => $intensity_table,
            'INNER JOIN' => [
                $source_table => [
                    'FKEY' => [
                        $intensity_table => CarbonIntensitySource::getForeignKeyField(),
                        $source_table => 'id',
                    ]
                ],
                $zone_table => [
                    'FKEY' => [
                        $intensity_table => Zone::getForeignKeyField(),
                        $zone_table => 'id',
                    ]
                ]
            ],
            'WHERE' => [
                CarbonIntensitySource::getTableField('name') => $source_name,
                Zone::getTableField('name') => $zone_name
            ],
        ];
    }

    /**
     * Get the last known date of carbon emissiosn
     *
     * @param string $zone_name   Zone to examinate
     * @param string $source_name Source to examinate
     * @return DateTimeImmutable
     */
    public function getLastKnownDate(string $zone_name, string $source_name): ?DateTimeImmutable
    {
        /** @var DBmysql $DB */
        global $DB;

        $request = $this->getKnownDatesQuery($zone_name, $source_name);
        $request['ORDER'] = CarbonIntensity::getTableField('date') . ' DESC';
        $request['LIMIT'] = '1';
        $result = $DB->request($request)->current();
        if ($result === null) {
            return null;
        }
        return DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $result['date']);
    }

    /**
     * Get the first known date of carbon emissiosn
     *
     * @param string $zone_name Zone to examinate
     * @param string $source_name Source to examinate
     * @return DateTimeImmutable
     */
    public function getFirstKnownDate(string $zone_name, string $source_name): ?DateTimeImmutable
    {
        /** @var DBmysql $DB */
        global $DB;

        $request = $this->getKnownDatesQuery($zone_name, $source_name);
        $request['ORDER'] = CarbonIntensity::getTableField('date') . ' ASC';
        $request['LIMIT'] = '1';
        $result = $DB->request($request)->current();

        if ($result === null) {
            return null;
        }
        return DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $result['date']);
    }

    /**
     * Download data for a single zone
     *
     * @param CarbonIntensityInterface $data_source
     * @param string $zone_name zone name
     * @param integer $limit maximum count of items to process
     * @param ProgressBar $progress_bar progress bar to update (CLI mode only)
     * @return integer count of item downloaded
     */
    public function downloadOneZone(CarbonIntensityInterface $data_source, string $zone_name, int $limit = 0, ?ProgressBar $progress_bar = null): int
    {
        $start_date = $this->getDownloadStartDate($zone_name, $data_source);

        $total_count = 0;

        // Check if there are gaps to fill
        $source = new CarbonIntensitySource();
        $source->getFromDBByCrit(['name' => $data_source->getSourceName()]);
        $zone = new Zone();
        $zone->getFromDBByCrit(['name' => $zone_name]);
        $gaps = $this->findGaps($source->getID(), $zone->getID(), $start_date);
        if (count($gaps) === 0) {
            // Log a notice specifying the source and the zone
            trigger_error(sprintf(
                "No gap to fill for source %s and zone %s between %s and %s",
                $data_source->getSourceName(),
                $zone_name,
                $start_date->format('Y-m-d'),
                'now'
            ), E_USER_WARNING);
        }

        if ($progress_bar) {
            $hours_to_download = 0;
            // Count the total days to download
            foreach ($gaps as $gap) {
                $gap_start = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $gap['start']);
                $gap_end = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $gap['end']);
                $diff = $gap_start->diff($gap_end);
                $hours_to_download += $diff->days * 24 + $diff->h;
            }
            $progress_bar->setMaxSteps($hours_to_download);
        }

        foreach ($gaps as $gap) {
            $gap_start = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $gap['start']);
            $gap_end = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $gap['end']);
            $count = $data_source->fullDownload($zone_name, $gap_start, $gap_end, $this, $limit, $progress_bar);
            $total_count += $count;
            if ($total_count >= $limit) {
                return $total_count;
            }
        }

        $first_known_intensity_date = $this->getFirstKnownDate($zone_name, $data_source->getSourceName());
        $incremental = false;
        if ($first_known_intensity_date !== null) {
            $incremental = ($start_date >= $first_known_intensity_date);
        }
        if ($first_known_intensity_date !== null && $first_known_intensity_date <= $data_source->getHardStartDate()) {
            // Cannot download older data than absolute start date of the source, then switch to incremetal mode
            $incremental = true;
        }
        if ($incremental) {
            $start_date = max($data_source->getMaxIncrementalAge(), $this->getLastKnownDate($zone_name, $data_source->getSourceName()));
            $start_date = $start_date->add(new DateInterval('PT1H'));
            $count = $data_source->incrementalDownload($zone_name, $start_date, $this, $limit);
            $total_count += $count;
            return $total_count;
        }

        return $total_count;
    }

    /**
     * Get the oldest date where data are required
     *
     * @param string                   $zone_name   ignored for now; zone to examine
     * @param CarbonIntensityInterface $data_source ignored for now; data source
     * @return DateTimeImmutable|null
     */
    public function getDownloadStartDate(string $zone_name, CarbonIntensityInterface $data_source): ?DateTimeImmutable
    {
        // Get the default oldest date od data to download
        $start_date = new DateTime(self::MIN_HISTORY_LENGTH);
        $start_date->setTime(0, 0, 0);
        $start_date = DateTimeImmutable::createFromMutable($start_date);

        // Get the oldest date between the oldest asset in the inventory and the previous one
        $toolbox = new Toolbox();
        $oldest_asset_date = $toolbox->getOldestAssetDate();
        if ($oldest_asset_date !== null) {
            $start_date = min($start_date, $oldest_asset_date);
        }

        return $start_date;
    }

    /**
     * Get date where data download shall end, excluding the incremental download mode for the specified data source
     *
     * @param string                   $zone_name   zone to examine
     * @param CarbonIntensityInterface $data_source data source
     * @return DateTimeImmutable
     */
    public function getDownloadStopDate(string $zone_name, CarbonIntensityInterface $data_source): DateTimeImmutable
    {
        $stop_date = $data_source->getMaxIncrementalAge();
        $first_known_intensity_date = $this->getFirstKnownDate($zone_name, $data_source->getSourceName());
        if ($first_known_intensity_date !== null) {
            $first_known_intensity_date = $first_known_intensity_date->sub(new DateInterval('PT1H'));
            $stop_date = min($stop_date, $first_known_intensity_date);
        }

        return $stop_date;
    }

    /**
     * Save in database the carbon intensities
     * Give up on failures
     *
     * @param string $zone_name name of the zone to store intensities
     * @param string $source_name name of the source to store intensities
     * @param array $data as an array of arrays ['datetime' => string, 'intensity' => float]
     * @return integer count of actually saved items,
     */
    public function save(string $zone_name, string $source_name, array $data): int
    {
        /** @var DBmysql $DB */
        global $DB;

        $count = 0;
        $source = new CarbonIntensitySource();
        $source->getFromDBByCrit([
            'name' => $source_name,
        ]);
        if ($source->isNewItem()) {
            throw new \RuntimeException('Attempt to save carbon intensity with a source which is not in the database');
            // trigger_error('Attempt to save carbon intensity with a source which is not in the database', E_USER_ERROR);
            // return 0;
        }
        $zone = new Zone();
        $zone->getFromDBByCrit([
            'name' => $zone_name,
        ]);
        if ($zone->isNewItem()) {
            throw new \RuntimeException('Attempt to save carbon intensity with a zone which is not in the database');
            // trigger_error('Attempt to save carbon intensity with a zone which is not in the database', E_USER_ERROR);
            // return 0;
        }

        $query = $DB->buildInsert(
            CarbonIntensity::getTable(),
            [
                'date' => new QueryParam(),
                CarbonIntensitySource::getForeignKeyField() => new QueryParam(),
                Zone::getForeignKeyField() => new QueryParam(),
                'intensity' => new QueryParam(),
                'data_quality' => new QueryParam(),
            ],
        );
        $stmt = $DB->prepare($query);

        foreach ($data as $intensity) {
            try {
                $stmt->bind_param(
                    'siidi',
                    $intensity['datetime'],
                    $source->fields['id'],
                    $zone->fields['id'],
                    $intensity['intensity'],
                    $intensity['data_quality']
                );
                $DB->executeStatement($stmt);
                $count++;
            } catch (\RuntimeException $e) {
                $count++;
                continue;
            }
        }
        $stmt->close();

        return $count;
    }

    /**
     * Gets date intervals where data are missing
     *
     * @param integer $source_id
     * @param integer $zone_id
     * @param DateTimeInterface $start
     * @param DateTimeInterface|null $stop
     * @return array
     */
    public function findGaps(int $source_id, int $zone_id, DateTimeInterface $start, ?DateTimeInterface $stop = null): array
    {
        $criteria = [
            CarbonIntensitySource::getForeignKeyField() => $source_id,
            Zone::getForeignKeyField() => $zone_id,
        ];
        $interval = new DateInterval('PT1H');
        return Toolbox::findTemporalGapsInTable(self::getTable(), $start, $interval, $stop, $criteria);
    }
}
