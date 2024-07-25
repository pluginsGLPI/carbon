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
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\CarbonIntensityZone;
use GlpiPlugin\Carbon\DataSource\CarbonIntensityInterface;
use QueryParam;

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
            'field'              => 'emission_date',
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
    public function getLatestKnownDate(string $zone_name, string $source_name): ?DateTimeImmutable
    {
        global $DB;

        $intensity_table = CarbonIntensity::getTable();
        $source_table = CarbonIntensitySource::getTable();
        $zone_table   = CarbonIntensityZone::getTable();

        $result = $DB->request([
            'SELECT' => CarbonIntensity::getTableField('emission_date'),
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
            'LIMIT' => '1'
        ])->current();
        if ($result === null) {
            return null;
        }
        return DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $result['emission_date']);
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
        $incremental = $data_source->isZoneDownloadComplete($zone_name);

        $start_date = $this->getDownloadStartDate($zone_name, $data_source);

        $recent_limit = $data_source->getMaxIncrementalAge();
        if ($incremental && $start_date >= $recent_limit) {
            return $data_source->incrementalDownload($zone_name, $start_date, $this, $limit);
        }

        return $data_source->fullDownload($zone_name, $start_date, $this, $limit);
    }

    public function getDownloadStartDate(string $zone_name, CarbonIntensityInterface $data_source): ?DateTimeImmutable
    {
        $start_date = new DateTime(self::MIN_HISTORY_LENGTH);
        $start_date->setTime(0, 0, 0);
        $start_date = DateTimeImmutable::createFromMutable($start_date);

        $toolbox = new Toolbox();
        $last_known_intensity_date = $this->getLatestKnownDate($zone_name, $data_source->getSourceName());
        $first_unknown_intensity_date = null;
        if ($last_known_intensity_date !== null) {
            $first_unknown_intensity_date = $last_known_intensity_date->add(new DateInterval('PT1H'));
            $oldest_asset_date = $toolbox->getOldestAssetDate();
            $start_date = max($oldest_asset_date, $first_unknown_intensity_date);
        }

        return $start_date;
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
            trigger_error('Attempt to save carbon intensity with a source which is not in the database', E_USER_ERROR);
            return 0;
        }
        $zone = new CarbonIntensityZone();
        $zone->getFromDBByCrit([
            'name' => $zone_name,
        ]);
        if ($zone->isNewItem()) {
            trigger_error('Attempt to save carbon intensity with a zone which is not in the database', E_USER_ERROR);
            return 0;
        }

        $query = $DB->buildInsert(
            CarbonIntensity::getTable(),
            [
                'emission_date' => new QueryParam(),
                CarbonIntensitySource::getForeignKeyField() => new QueryParam(),
                CarbonIntensityZone::getForeignKeyField() => new QueryParam(),
                'intensity' => new QueryParam(),
            ],
        );
        $stmt = $DB->prepare($query);

        $in_transaction = $DB->inTransaction();
        if (!$in_transaction) {
            $DB->beginTransaction();
        }

        try {
            foreach ($data as $intensity) {
                $stmt->bind_param(
                    'siid',
                    $intensity['datetime'],
                    $source->fields['id'],
                    $zone->fields['id'],
                    $intensity['intensity']
                );
                $DB->executeStatement($stmt);
                $count++;
            }
        } catch (\RuntimeException $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
            $count = -$count;
        } finally {
            if (!$in_transaction) {
                $DB->commit();
            }
        }
        $stmt->close();

        return $count;
    }
}
