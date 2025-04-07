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

use CommonDropdown;
use DateTime;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DBmysql;
use DBmysqlIterator;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\Zone;
use GlpiPlugin\Carbon\DataSource\CarbonIntensityInterface;
use QueryParam;
use QuerySubQuery;
use QueryExpression;
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

    // public static function getMenuContent()
    // {
    //     $menu = [];

    //     if (self::canView()) {
    //         $menu = [
    //             'title' => self::getTypeName(0),
    //             'shortcut' => self::getMenuShorcut(),
    //             'page' => self::getSearchURL(false),
    //             'icon' => self::getIcon(),
    //             'lists_itemtype' => self::getType(),
    //             'links' => [
    //                 'search' => self::getSearchURL(),
    //                 'lists' => '',
    //             ]
    //         ];
    //     }

    //     return $menu;
    // }

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

        $intensity_table = CarbonIntensity::getTable();
        $source_table = CarbonIntensitySource::getTable();
        $zone_table   = Zone::getTable();

        $result = $DB->request([
            'SELECT' => [$intensity_table => ['id', 'date']],
            'FROM'   => $intensity_table,
            'INNER JOIN' => [
                $source_table => [
                    'FKEY' => [
                        $intensity_table => 'plugin_carbon_carbonintensitysources_id',
                        $source_table => 'id',
                    ]
                ],
                $zone_table => [
                    'FKEY' => [
                        $intensity_table => 'plugin_carbon_zones_id',
                        $zone_table => 'id',
                    ]
                ]
            ],
            'WHERE' => [
                CarbonIntensitySource::getTableField('name') => $source_name,
                Zone::getTableField('name') => $zone_name
            ],
            'ORDER' => CarbonIntensity::getTableField('date') . ' DESC',
            'LIMIT' => '1'
        ])->current();
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

        $intensity_table = CarbonIntensity::getTable();
        $source_table = CarbonIntensitySource::getTable();
        $zone_table   = Zone::getTable();

        $result = $DB->request([
            'SELECT' => CarbonIntensity::getTableField('date'),
            'FROM'   => $intensity_table,
            'INNER JOIN' => [
                $source_table => [
                    'FKEY' => [
                        $intensity_table => 'plugin_carbon_carbonintensitysources_id',
                        $source_table => 'id',
                    ]
                ],
                $zone_table => [
                    'FKEY' => [
                        $intensity_table => 'plugin_carbon_zones_id',
                        $zone_table => 'id',
                    ]
                ]
            ],
            'WHERE' => [
                CarbonIntensitySource::getTableField('name') => $source_name,
                Zone::getTableField('name') => $zone_name
            ],
            'ORDER' => CarbonIntensity::getTableField('date') . ' ASC',
            'LIMIT' => '1'
        ])->current();
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
            $limit -= $count;
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
            // Cannot download older data, then switch to incremetal mode
            $incremental = true;
        }
        if ($incremental) {
            $start_date = max($data_source->getMaxIncrementalAge(), $this->getLastKnownDate($zone_name, $data_source->getSourceName()));
            $start_date = $start_date->add(new DateInterval('PT1H'));
            $count = $data_source->incrementalDownload($zone_name, $start_date, $this, $limit);
            $total_count += $count;
            return $total_count;
        }

        $stop_date  = $this->getDownloadStopDate($zone_name, $data_source);
        $count = $data_source->fullDownload($zone_name, $start_date, $stop_date, $this, $limit);
        $total_count += $count;
        return $total_count;
    }

    public function getDownloadStartDate(string $zone_name, CarbonIntensityInterface $data_source): ?DateTimeImmutable
    {
        $start_date = new DateTime(self::MIN_HISTORY_LENGTH);
        $start_date->setTime(0, 0, 0);
        $start_date = DateTimeImmutable::createFromMutable($start_date);

        $toolbox = new Toolbox();
        $oldest_asset_date = $toolbox->getOldestAssetDate();
        if ($oldest_asset_date !== null) {
            $start_date = min($start_date, $oldest_asset_date);
        }

        return $start_date;
    }
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

        $in_transaction = $DB->inTransaction() || $DB->beginTransaction();

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
        if ($in_transaction) {
            $DB->commit();
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
