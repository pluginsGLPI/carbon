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

namespace GlpiPlugin\Carbon\Impact\Embodied\Boavizta;

use CommonDBTM;
use Computer as GlpiComputer;
use ComputerModel as GlpiComputerModel;
use ComputerType as GlpiComputerType;
use GlpiPlugin\Carbon\CloudInventoryConnector;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\DataSource\Lca\Boaviztapi\ComputerModelizationAdapterTrait;
use GlpiPlugin\Cloudinventory\Amazon;
use GlpiPlugin\Cloudinventory\Azure;
use GlpiPlugin\Cloudinventory\CloudInstance;
use GlpiPlugin\Cloudinventory\Google;
use GlpiPlugin\Cloudinventory\Ovh;
use GlpiPlugin\Cloudinventory\Scaleway;
use Override;
use UnhandledMatchError;

class Computer extends AbstractAsset
{
    use ComputerModelizationAdapterTrait;

    protected static string $itemtype = GlpiComputer::class;

    protected string $endpoint        = 'server';

    /**
     * If the plugin CloudInventory is available, this is an oblect from that
     * plugin representing the cloud related data of the computer
     */
    protected ?CloudInstance $cloud_instance = null;

    /**
     * @var array Description of the asset for querying Boaviztapi
     */
    protected array $description = [];

    #[Override]
    protected function doEvaluation(): ?array
    {
        $type = $this->getType($this->item);

        $response = null;
        $this->chooseEvaluationMode($type);

        // select all impact types
        $this->endpoint .= '?' . $this->getCriteriasQueryString();

        // Query Boaviztapi
        $response = $this->query($this->description);

        $impacts = $this->client->parseResponse($response, 'embedded');
        return $impacts;
    }

    private function chooseEvaluationMode(int $type): string
    {
        if ($type === ComputerType::CATEGORY_CLOUD) {
            $cloud_provider = '';
            switch ($this->cloud_instance->fields['itemtype']) {
                case Amazon::class:
                    $cloud_provider = 'aws';
                    break;
                case Azure::class:
                    $cloud_provider = 'azure';
                    break;
                case Google::class:
                    $cloud_provider = 'gcp';
                    break;
                case Ovh::class:
                    $cloud_provider = 'ovhcloud';
                    break;
                case Scaleway::class:
                    $cloud_provider = 'scaleway';
                    break;
            }
            $glpi_computer_model = GlpiComputerModel::getById($this->cloud_instance->fields['computermodels_id']);
            if ($glpi_computer_model !== false) {
                $instance_types = $this->client->getCloudInstances($cloud_provider);
                $model = $this->normalizeModel($cloud_provider, $glpi_computer_model->fields['name']);
                if (in_array($model, $instance_types)) {
                    $this->prepareCloudDescription($cloud_provider, $model);
                    return 'cloud';
                }
            }
        }

        $this->prepareHardwareDescription($type);
        return 'hardware';
    }

    /**
     * Prepare description of the asset for the Boaviztapi query
     */
    private function prepareHardwareDescription(int $type): void
    {
        try {
            $this->endpoint = match ($type) {
                ComputerType::CATEGORY_SERVER     => 'server',
                ComputerType::CATEGORY_LAPTOP     => 'terminal/laptop',
                ComputerType::CATEGORY_TABLET     => 'terminal/tablet',
                ComputerType::CATEGORY_SMARTPHONE => 'terminal/smartphone',
            };
        } catch (UnhandledMatchError $e) {
            $this->endpoint = 'terminal/desktop';
        }

        $this->description = [
            'configuration' => $this->analyzeHardware(),
            'usage' => self::USAGE_NULL,
        ];
    }

    /**
     * Prepare description of the asset for the Boaviztapi query
     *
     * @param string $provider
     * @param string $model
     * @return void
     */
    private function prepareCloudDescription(string $provider, string $model)
    {
        $this->endpoint = 'cloud/instance';

        $this->description = [
            'usage'    => self::USAGE_NULL,
        ];
        $this->description['provider'] = $provider;
        $this->description['instance_type'] = $model;
    }

    /**
     * Get the type of the computer
     * @param CommonDBTM $item
     * @return int The type of the computer
     */
    protected function getType(CommonDBTM $item): int
    {
        $cloudInventory_connector = new CloudInventoryConnector();
        if ($cloudInventory_connector->pluginAvailable()) {
            $cloud_instance = new CloudInstance();
            $cloud_instance->getFromDBByCrit([
                'computers_id' => $item->getID(),
            ]);
            if (!$cloud_instance->isNewItem()) {
                $this->cloud_instance = $cloud_instance;
                return ComputerType::CATEGORY_CLOUD;
            }
        }

        $computer_table = GlpiComputer::getTable();
        $computer_type_table = ComputerType::getTable();
        $glpi_computer_type_table = GlpiComputerType::getTable();
        $computer_type = new ComputerType();
        $found = $computer_type->getFromDBByRequest([
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
        if ($found === false) {
            return ComputerType::CATEGORY_UNDEFINED;
        }

        return $computer_type->fields['category'];
    }

    protected function normalizeModel(string $provider, string $model): string
    {
        switch ($provider) {
            case 'scaleway':
                // CloudInventory sets scaleway models with the prefix "SCW-"
                return strtolower(substr($model, 4));
        }

        return $provider;
    }
}
