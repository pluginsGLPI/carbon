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

use CommonDBTM;
use DateTime;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DBmysqlIterator;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\CarbonIntensityZone;
use GlpiPlugin\Carbon\DataSource\CarbonIntensityInterface;
use QueryParam;
use QuerySubQuery;
use QueryExpression;

class CarbonIntensity extends CommonDBTM
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

    public static function getMenuContent()
    {
        $menu = [];

        if (self::canView()) {
            $menu = [
                'title' => self::getTypeName(0),
                'shortcut' => self::getMenuShorcut(),
                'page' => self::getSearchURL(false),
                'icon' => self::getIcon(),
                'lists_itemtype' => self::getType(),
                'links' => [
                    'search' => self::getSearchURL(),
                    'lists' => '',
                ]
            ];
        }

        return $menu;
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $table = self::getTable();

        $tab[] = [
            'id'                 => '2',
            'table'              => $table,
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false, // implicit field is id
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $table,
            'field'              => 'date',
            'name'               => __('Emission date', 'carbon'),
            'massiveaction'      => false, // implicit field is id
            'datatype'           => 'datetime',
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => CarbonIntensitySource::getTable(),
            'field'              => 'name',
            'name'               => CarbonIntensitySource::getTypeName(1),
            'massiveaction'      => false, // implicit field is id
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => CarbonIntensityZone::getTable(),
            'field'              => 'name',
            'name'               => CarbonIntensityZone::getTypeName(1),
            'massiveaction'      => false, // implicit field is id
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $table,
            'field'              => 'intensity',
            'name'               => __('Intensity', 'carbon'),
            'massiveaction'      => false, // implicit field is id
            'datatype'           => 'integer',
            'unit'               => 'gCO<sub>2</sub>eq/KWh',
        ];

        return $tab;
    }


    /**
     * Get the last known date of carbon emissiosn
     *
     * @param string $zone Zone to examinate
     * @return DateTimeImmutable
     */
    public function getLastKnownDate(string $zone_name, string $source_name): ?DateTimeImmutable
    {
        global $DB;

        $intensity_table = CarbonIntensity::getTable();
        $source_table = CarbonIntensitySource::getTable();
        $zone_table   = CarbonIntensityZone::getTable();

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
                        $intensity_table => 'plugin_carbon_carbonintensityzones_id',
                        $zone_table => 'id',
                    ]
                ]
            ],
            'WHERE' => [
                CarbonIntensitySource::getTableField('name') => $source_name,
                CarbonIntensityZone::getTableField('name') => $zone_name
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
     * @param string $zone Zone to examinate
     * @return DateTimeImmutable
     */
    public function getFirstKnownDate(string $zone_name, string $source_name): ?DateTimeImmutable
    {
        global $DB;

        $intensity_table = CarbonIntensity::getTable();
        $source_table = CarbonIntensitySource::getTable();
        $zone_table   = CarbonIntensityZone::getTable();

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
                        $intensity_table => 'plugin_carbon_carbonintensityzones_id',
                        $zone_table => 'id',
                    ]
                ]
            ],
            'WHERE' => [
                CarbonIntensitySource::getTableField('name') => $source_name,
                CarbonIntensityZone::getTableField('name') => $zone_name
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
     * @param string $zone zone name
     * @param integer $limit maximum count of items to process
     * @return integer count of item downloaded
     */
    public function downloadOneZone(CarbonIntensityInterface $data_source, string $zone_name, int $limit = 0): int
    {
        $start_date = $this->getDownloadStartDate($zone_name, $data_source);

        $total_count = 0;

        // Check if there are gaps to fill
        $source = new CarbonIntensitySource();
        $source->getFromDBByCrit(['name' => $data_source->getSourceName()]);
        $zone = new CarbonIntensityZone();
        $zone->getFromDBByCrit(['name' => $zone_name]);
        $gaps = $this->findGaps($source->getID(), $zone->getID(), $start_date);
        foreach ($gaps as $gap) {
            $gap_start = DateTimeImmutable::createFromFormat('U', $gap['start']);
            $gap_end = DateTimeImmutable::createFromFormat('U', $gap['end']);
            $count = $data_source->fullDownload($zone_name, $gap_start, $gap_end, $this, $limit);
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
        $start_date = min($start_date, $oldest_asset_date);

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
        $zone = new CarbonIntensityZone();
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
                CarbonIntensityZone::getForeignKeyField() => new QueryParam(),
                'intensity' => new QueryParam(),
                'data_quality' => new QueryParam(),
            ],
        );
        $stmt = $DB->prepare($query);

        $in_transaction = $DB->inTransaction();
        if (!$in_transaction) {
            $DB->beginTransaction();
        }

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
        if (!$in_transaction) {
            $DB->commit();
        }
        $stmt->close();

        return $count;
    }

    /**
     * Gets date intervals where data are missing
     *
     * @param integer $id
     * @param DateTimeInterface $start
     * @param DateTimeInterface|null $stop
     * @return DBmysqlIterator
     */
    public function findGaps(int $source_id, int $zone_id, DateTimeInterface $start, ?DateTimeInterface $stop = null): DBmysqlIterator
    {
        global $DB;

        $table = self::getTable();

        // Build WHERE clause for boundaries
        $boundaries = [];
        if ($start !== null) {
            $unix_start = $start->format('U');
            $unix_start = $unix_start - ($unix_start % 3600);
            $boundaries[] = new QueryExpression('UNIX_TIMESTAMP(`date`) >= ' . $unix_start);
        }
        if ($stop !== null) {
            $unix_stop = $stop->format('U');
            $unix_stop = $unix_stop - ($unix_stop % 3600);
            $boundaries[] = new QueryExpression('UNIX_TIMESTAMP(`date`) <= ' . $unix_stop);
        }

        $date_1 = 'UNIX_TIMESTAMP(`date`)';
        $date_2 = 'LEAD(UNIX_TIMESTAMP(`date`), 1) OVER (ORDER BY UNIX_TIMESTAMP(`date`))';
        $request = [
            'SELECT' => [
                'date as start',
                'next_available_date as end'
            ],
            'FROM'  => new QuerySubQuery([
                'SELECT' => [
                    new QueryExpression("$date_1 as `date`"),
                    new QueryExpression("$date_2  AS `next_available_date`"),
                    new QueryExpression("$date_2 - $date_1 as `diff`"),
                ],
                'FROM' => $table,
                'WHERE' => [
                    CarbonIntensitySource::getForeignKeyField() => $source_id,
                    CarbonIntensityZone::getForeignKeyField() => $zone_id,
                ] + $boundaries
            ], 'rows'),
            'WHERE' => ['diff' => ['>', 3600]]
        ];
        $iterator = $DB->request($request);

        return $iterator;
    }
}
