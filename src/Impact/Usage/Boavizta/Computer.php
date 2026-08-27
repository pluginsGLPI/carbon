<?php

/**
 * -------------------------------------------------------------------------
 * Carbon plugin for GLPI
 *
 * @copyright Copyright (C) 2024-2025 Teclib' and contributors.
 * @copyright Copyright (C) 2024 by the carbon plugin team.
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

namespace GlpiPlugin\Carbon\Impact\Usage\Boavizta;

use CommonDBTM;
use Computer as GlpiComputer;
use ComputerModel as GlpiComputerModel;
use ComputerType as GlpiComputerType;
use DBmysql;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\DataSource\Lca\Boaviztapi\ComputerModelizationAdapterTrait;
use GlpiPlugin\Carbon\Location;
use GlpiPlugin\Carbon\UsageInfo;
use Override;

class Computer extends AbstractAsset
{
    use ComputerModelizationAdapterTrait;

    protected static string $itemtype = GlpiComputer::class;
    protected static string $type_itemtype  = GlpiComputerType::class;
    protected static string $model_itemtype = GlpiComputerModel::class;

    protected string $endpoint        = 'server';

    #[Override]
    public function getEvaluableQuery(string $itemtype, array $crit = [], bool $entity_restrict = true): array
    {
        $request = parent::getEvaluableQuery($itemtype);

        $item_table = getTableForItemType($itemtype);
        $computerUsageProfile_table = ComputerUsageProfile::getTable();
        $usage_info_table = UsageInfo::getTable();
        $request['INNER JOIN'][$usage_info_table] =  [
            'FKEY'   => [
                $item_table  => 'id',
                $usage_info_table => 'items_id',
                [
                    'AND' => [UsageInfo::getTableField('itemtype') => self::$itemtype],
                ],
            ],
        ];
        $request['INNER JOIN'][$computerUsageProfile_table] =  [
            'FKEY'   => [
                $usage_info_table  => ComputerUsageProfile::getForeignKeyField(),
                $computerUsageProfile_table => 'id',
            ],
        ];
        $request['WHERE'][] = [
            ['NOT' => [ComputerType::getTableField('category') => null]],
            [ComputerType::getTableField('category') => ['>', 0]],
        ];
        $request['WHERE'][] = $crit;

        return $request;
    }

    #[Override]
    protected function doEvaluation(CommonDBTM $item): ?array
    {
        $type = $this->getType($item);
        $this->endpoint = $this->getEndpoint($type);
        $this->endpoint .= '?' . $this->getCriteriasQueryString();

        // Find boavizta zone code
        $zone_code = Location::getZoneCode($item);
        if ($zone_code === null) {
            return null;
        }
        $average_power = $this->getAveragePower($item->getID());
        // Ask for usage impact only
        $configuration = $this->analyzeHardware();
        if (count($configuration) === 0) {
            return null;
        }
        $lifespan = (new UsageInfo())->getLifespanInHours($item);
        if ($lifespan === null) {
            return null;
        }
        $use_ratio = $this->getUseRatio();
        $time_workload = $this->getWorkloadRepartition();

        $description = [
            'configuration' => $configuration,
            'usage' => [
                'usage_location' => $zone_code,
                'hours_lifetime' => $lifespan,
                'avg_power'      => $average_power,
                'use_time_ratio' => $use_ratio,
                'time_workload'  => $time_workload,
            ],
        ];
        $response = $this->query($description);
        $impacts = $this->client->parseResponse($response, 'use');

        return $impacts;
    }

    /**
     * Get the type of the computer
     * @param CommonDBTM $item
     * @return int The type of the computer
     */
    protected function getType(CommonDBTM $item): int
    {
        /** @var DBmysql $DB */
        global $DB;

        $computer_table = GlpiComputer::getTable();
        $computer_type_table = ComputerType::getTable();
        $glpi_computer_type_table = GlpiComputerType::getTable();
        $result = $DB->request([
            'SELECT'     => ComputerType::getTableField('category'),
            'FROM'       => $computer_type_table,
            'INNER JOIN' => [
                $glpi_computer_type_table => [
                    'FKEY' => [
                        $computer_type_table => 'computertypes_id',
                        $glpi_computer_type_table => 'id',
                    ],
                ],
                $computer_table => [
                    'FKEY' => [
                        $glpi_computer_type_table => 'id',
                        $computer_table           => 'computertypes_id',
                    ],
                ],
            ],
            'WHERE' => [
                GlpiComputer::getTableField('id') => $item->getID(),
            ],
        ]);
        $row_count = $result->count();
        if ($row_count === 0) {
            return ComputerType::CATEGORY_UNDEFINED;
        } elseif ($result->count() > 1) {
            trigger_error(sprintf('SQL query shall return 1 row, got %d', $row_count), WARNING);
        }

        return $result->current()['category'];
    }

    /**
     * Get the endpoint to use for the given type
     */
    protected function getEndpoint(int $type)
    {
        switch ($type) {
            case ComputerType::CATEGORY_SERVER:
                return 'server';
            case ComputerType::CATEGORY_LAPTOP:
                return 'terminal/laptop';
            case ComputerType::CATEGORY_TABLET:
                return 'terminal/tablet';
            case ComputerType::CATEGORY_SMARTPHONE:
                return 'terminal/smartphone';
        }

        // ComputerType::CATEGORY_UNDEFINED
        // ComputerType::CATEGORY_DESKTOP
        return 'terminal/desktop';
    }

    /**
     * Calculate the use time ratio from the usage profile
     *
     * @return float Ratio between 0 and 1
     */
    protected function getUseRatio(): float
    {
        $usage_profile = new ComputerUsageProfile();
        $usage_profile_table = ComputerUsageProfile::getTable();
        $usage_info_table = getTableForItemType(UsageInfo::class);
        $usage_profile->getFromDBByRequest([
            'INNER JOIN' => [
                $usage_info_table => [
                    'FKEY' => [
                        $usage_info_table => 'plugin_carbon_computerusageprofiles_id',
                        $usage_profile_table => 'id',
                    ],
                ],
            ],
            'WHERE' => [
                UsageInfo::getTableField('itemtype') => static::$itemtype,
                UsageInfo::getTableField('items_id') => $this->item->getID(),
            ],
        ]);

        return $usage_profile->getPoweredOnRatio();
    }
}
