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

namespace GlpiPlugin\Carbon\Impact\History\Tests;

use CommonDBTM;
use Computer as GlpiComputer;
use ComputerModel as GlpiComputerModel;
use ComputerType as GlpiComputerType;
use DateTime;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\ComputerModel;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\Impact\History\Computer;
use GlpiPlugin\Carbon\Location;
use GlpiPlugin\Carbon\Source;
use GlpiPlugin\Carbon\Source_Zone;
use GlpiPlugin\Carbon\Tests\Impact\History\CommonAsset;
use GlpiPlugin\Carbon\UsageInfo;
use GlpiPlugin\Carbon\Zone;
use Infocom;
use Location as GlpiLocation;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Computer::class)]
class ComputerTest extends CommonAsset
{
    protected string $history_type = Computer::class;
    protected string $asset_type = GlpiComputer::class;

    /**
     * Create an asset with all required data to make it evaluable
     *
     * @return array<CommonDBTM> An asset and related objects
     */
    protected function getHistorizableComputer(): array
    {
        $glpi_location = $this->createItem(GlpiLocation::class);
        $source = new Source(); // This source exists after a fresh install
        $source->getFromDBByCrit([
            'name' => 'RTE',
        ]);
        $zone = new Zone(); // This zone exists after a fresh install
        $zone->getFromDBByCrit([
            'name' => 'France',
        ]);
        $source_zone = new Source_Zone(); // the relation source / zone also exists after a fresh install
        $source_zone->getFromDBByCrit([
            $source::getForeignKeyField() => $source->getID(),
            $zone::getForeignKeyField() => $zone->getID(),
        ]);
        $location = $this->createItem(Location::class, [
            'locations_id' => $glpi_location->getID(),
            'plugin_carbon_sources_zones_id' => $source_zone->getID(),
        ]);
        $glpi_computer_type = $this->createItem(GlpiComputerType::class);
        $computer_type = $this->createItem(ComputerType::class, [
            'power_consumption' => 55,
            'computertypes_id' => $glpi_computer_type->getID(),
            'category'         => ComputerType::CATEGORY_DESKTOP,
        ]);
        $glpi_computer_model = $this->createItem(GlpiComputerModel::class, [
            'power_consumption' => 35,
        ]);
        $computer_model = $this->createItem(ComputerModel::class, [
            'computermodels_id' => $glpi_computer_model->getID(),
        ]);
        $computer = $this->createItem(GlpiComputer::class, [
            'locations_id' => $glpi_location->getID(),
            'computertypes_id' => $glpi_computer_type->getID(),
            'computermodels_id' => $glpi_computer_model->getID(),
        ]);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID(),
            'use_date' => '2020-01-01',
            'decommission_date' => '2028-05-01',
        ]);
        $usage_profile = $this->createItem(ComputerUsageProfile::class);
        $impact = $this->createItem(UsageInfo::class, [
            $usage_profile->getForeignKeyField() => $usage_profile->getID(),
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID(),
        ]);

        return [
            $computer,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_computer_model,
            $glpi_computer_type,
            $computer_type,
            $infocom,
            $usage_profile,
            $zone,
        ];
    }

    public function testGetEngine()
    {
        $asset = new GlpiComputer();
        $engine = Computer::getEngine($asset);
        $this->assertInstanceOf(\GlpiPlugin\Carbon\Engine\V1\Computer::class, $engine);
    }

    public function testEvaluateItem()
    {
        /** @var DBmysql $DB */
        global $DB;

        // Check DBMS version as this tests does not works on minimal DBMS requirement of GLPI 10.0
        $db_version_full = $DB->getVersion();
        // Isolate version number
        $db_version = preg_replace('/[^0-9.]/', '', $db_version_full);
        // Check if is MariaDB
        $min_version = '8.0';
        if (strpos($db_version_full, 'MariaDB') !== false) {
            $min_version = '10.2';
        }
        if (version_compare($db_version, $min_version, '<') || version_compare($db_version, $min_version, '<')) {
            $this->markTestSkipped('Test requires MySQL 8.0 or MariaDB 10.2');
        }

        $this->login('glpi', 'glpi');
        $entities_id = $this->isolateInEntity('glpi', 'glpi');

        $model_power = 55;
        $glpi_location = $this->createItem(GlpiLocation::class, [
            'state' => 'Quebec',
        ]);
        $source = new Source(); // This source exists after a fresh install
        $source->getFromDBByCrit([
            'name' => 'Hydro Quebec',
        ]);
        $zone = new Zone(); // This zone exists after a fresh install
        $zone->getFromDBByCrit([
            'name' => 'Quebec',
        ]);
        $source_zone = new Source_Zone(); // the relation source / zone also exists after a fresh install
        $source_zone->getFromDBByCrit([
            $source::getForeignKeyField() => $source->getID(),
            $zone::getForeignKeyField() => $zone->getID(),
        ]);
        $location = $this->createItem(Location::class, [
            'locations_id' => $glpi_location->getID(),
            'plugin_carbon_sources_zones_id' => $source_zone->getID(),
        ]);
        $glpi_model = $this->createItem(GlpiComputerModel::class, ['power_consumption' => $model_power]);
        $glpi_type = $this->createItem(GlpiComputerType::class);
        $type = $this->createItem(ComputerType::class, [
            GlpiComputerType::getForeignKeyField() => $glpi_type->getID(),
        ]);
        $asset = $this->createItem(GlpiComputer::class, [
            'computertypes_id'  => $glpi_type->getID(),
            'computermodels_id' => $glpi_model->getID(),
            'locations_id'      => $glpi_location->getID(),
            'date_creation'     => '2024-01-01',
            'date_mod'          => null,
        ]);
        $usage_profile = $this->createItem(ComputerUsageProfile::class, [
            'time_start'   => '09:00',
            'time_stop'    => '18:00',
            'day_1'        => '1',
            'day_2'        => '1',
            'day_3'        => '1',
            'day_4'        => '1',
            'day_5'        => '1',
            'day_6'        => '0',
            'day_7'        => '0',
        ]);
        $impact = $this->createItem(UsageInfo::class, [
            $usage_profile->getForeignKeyField() => $usage_profile->getID(),
            'itemtype' => $asset->getType(),
            'items_id' => $asset->getID(),
        ]);

        $history = new Computer();
        $start_date = '2024-02-01 00:00:00';
        $end_date =   '2024-02-08 00:00:00';

        $count = $history->evaluateItem(
            $asset->getID(),
            new DateTime($start_date),
            new DateTime($end_date)
        );

        // Days interval is [$start_date, $end_date[
        $this->assertEquals(7, $count);

        $carbon_emission = new CarbonEmission();
        $emissions = $carbon_emission->find([
            ['date' => ['>=', $start_date]],
            ['date' =>  ['<', $end_date]],
            'itemtype' => $asset->getType(),
            'items_id' => $asset->getID(),
        ], [
            'date ASC',
        ]);
        $this->assertEquals(7, count($emissions));

        $expected = [
            [
                'date'             => '2024-02-01 00:00:00',
                'energy_per_day'   => 0.495,
                'emission_per_day' => 17.0775,
            ],[
                'date' => '2024-02-02 00:00:00',
                'energy_per_day'   => 0.495,
                'emission_per_day' => 17.0775,
            ], [
                'date' => '2024-02-03 00:00:00',
                'energy_per_day'   => 0,
                'emission_per_day' => 0,
            ], [
                'date' => '2024-02-04 00:00:00',
                'energy_per_day'   => 0,
                'emission_per_day' => 0,
            ], [
                'date' => '2024-02-05 00:00:00',
                'energy_per_day'   => 0.495,
                'emission_per_day' => 17.0775,
            ], [
                'date' => '2024-02-06 00:00:00',
                'energy_per_day'   => 0.495,
                'emission_per_day' => 17.0775,
            ], [
                'date' => '2024-02-07 00:00:00',
                'energy_per_day'   => 0.495,
                'emission_per_day' => 17.0775,
            ],
        ];
        foreach ($emissions as $emission) {
            $expected_row = array_shift($expected);
            $emission = array_intersect_key($emission, $expected_row);
            $this->assertEquals($expected_row, $emission);
        }
    }

    public function test_getHistorizableDiagnosis_when_computer_is_historizable()
    {
        $history = new Computer();

        [
            $glpi_computer,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_computer_model,
            $glpi_computer_type,
            $computer_type,
            $infocom,
            $usage_profile,
            $zone,
        ] = $this->getHistorizableComputer();

        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_usage_profile'           => true,
            'has_location'                => true,
            'has_category'                => true,
            'has_carbon_intensity_zone'   => true,
            'has_model'                   => true,
            'has_model_power_consumption' => true,
            'has_type'                    => true,
            'has_type_power_consumption'  => true,
            'has_inventory_entry_date'    => true,
            'ci_download_enabled'         => true,
            'ci_fallback_available'       => true,
            'not_is_ignore'               => true,
            'has_decommission_date'       => true,
        ];
        $result = $history->getHistorizableDiagnosis($glpi_computer);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_computer_is_deleted()
    {
        $history = new Computer();

        [
            $glpi_computer,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_computer_model,
            $glpi_computer_type,
            $computer_type,
            $infocom,
            $usage_profile,
            $zone,
        ] = $this->getHistorizableComputer();
        $this->updateItem($glpi_computer, ['is_deleted' => 1]);
        $expected = [
            'is_deleted'                  => false,
            'is_template'                 => true,
            'has_usage_profile'           => true,
            'has_location'                => true,
            'has_category'                => true,
            'has_carbon_intensity_zone'   => true,
            'has_model'                   => true,
            'has_model_power_consumption' => true,
            'has_type'                    => true,
            'has_type_power_consumption'  => true,
            'has_inventory_entry_date'    => true,
            'ci_download_enabled'         => true,
            'ci_fallback_available'       => true,
            'not_is_ignore'               => true,
            'has_decommission_date'       => true,
        ];
        $result = $history->getHistorizableDiagnosis($glpi_computer);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_computer_is_template()
    {
        $history = new Computer();

        [
            $glpi_computer,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_computer_model,
            $glpi_computer_type,
            $computer_type,
            $infocom,
            $usage_profile,
            $zone,
        ] = $this->getHistorizableComputer();
        $this->updateItem($glpi_computer, ['is_template' => 1]);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => false,
            'has_usage_profile'           => true,
            'has_location'                => true,
            'has_category'                => true,
            'has_carbon_intensity_zone'   => true,
            'has_model'                   => true,
            'has_model_power_consumption' => true,
            'has_type'                    => true,
            'has_type_power_consumption'  => true,
            'has_inventory_entry_date'    => true,
            'ci_download_enabled'         => true,
            'ci_fallback_available'       => true,
            'not_is_ignore'               => true,
            'has_decommission_date'       => true,
        ];
        $result = $history->getHistorizableDiagnosis($glpi_computer);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_computer_has_no_usage_profile()
    {
        $history = new Computer();

        [
            $glpi_computer,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_computer_model,
            $glpi_computer_type,
            $computer_type,
            $infocom,
            $usage_profile,
            $zone,
        ] = $this->getHistorizableComputer();
        $this->deleteItem($usage_profile, true);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_usage_profile'           => false,
            'has_location'                => true,
            'has_category'                => true,
            'has_carbon_intensity_zone'   => true,
            'has_model'                   => true,
            'has_model_power_consumption' => true,
            'has_type'                    => true,
            'has_type_power_consumption'  => true,
            'has_inventory_entry_date'    => true,
            'ci_download_enabled'         => true,
            'ci_fallback_available'       => true,
            'not_is_ignore'               => true,
            'has_decommission_date'       => true,
        ];
        $result = $history->getHistorizableDiagnosis($glpi_computer);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_computer_has_no_location()
    {
        $history = new Computer();

        [
            $glpi_computer,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_computer_model,
            $glpi_computer_type,
            $computer_type,
            $infocom,
            $usage_profile,
            $zone,
        ] = $this->getHistorizableComputer();
        $this->deleteItem($glpi_location, true);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_usage_profile'           => true,
            'has_location'                => false,
            'has_category'                => true,
            'has_carbon_intensity_zone'   => false, // No location cascades this requirement to be not met
            'has_model'                   => true,
            'has_model_power_consumption' => true,
            'has_type'                    => true,
            'has_type_power_consumption'  => true,
            'has_inventory_entry_date'    => true,
            'ci_download_enabled'         => false, // No location cascades this requirement to be not met
            'ci_fallback_available'       => false, // No location cascades this requirement to be not met
            'not_is_ignore'               => true,
            'has_decommission_date'       => true,
        ];
        $result = $history->getHistorizableDiagnosis($glpi_computer);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_computer_has_no_category()
    {
        $history = new Computer();

        [
            $glpi_computer,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_computer_model,
            $glpi_computer_type,
            $computer_type,
            $infocom,
            $usage_profile,
            $zone,
        ] = $this->getHistorizableComputer();
        $this->updateItem($computer_type, ['category' => ComputerType::CATEGORY_UNDEFINED]);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_usage_profile'           => true,
            'has_location'                => true,
            'has_category'                => false,
            'has_carbon_intensity_zone'   => true,
            'has_model'                   => true,
            'has_model_power_consumption' => true,
            'has_type'                    => true,
            'has_type_power_consumption'  => true,
            'has_inventory_entry_date'    => true,
            'ci_download_enabled'         => true,
            'ci_fallback_available'       => true,
            'not_is_ignore'               => true,
            'has_decommission_date'       => true,
        ];
        $result = $history->getHistorizableDiagnosis($glpi_computer);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_computer_has_no_carbon_intensity_zone()
    {
        $history = new Computer();

        [
            $glpi_computer,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_computer_model,
            $glpi_computer_type,
            $computer_type,
            $infocom,
            $usage_profile,
            $zone,
        ] = $this->getHistorizableComputer();
        $this->deleteItem($zone);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_usage_profile'           => true,
            'has_location'                => true,
            'has_category'                => true,
            'has_carbon_intensity_zone'   => false,
            'has_model'                   => true,
            'has_model_power_consumption' => true,
            'has_type'                    => true,
            'has_type_power_consumption'  => true,
            'has_inventory_entry_date'    => true,
            'ci_download_enabled'         => true,
            'ci_fallback_available'       => true,
            'not_is_ignore'               => true,
            'has_decommission_date'       => true,
        ];
        $result = $history->getHistorizableDiagnosis($glpi_computer);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_computer_has_no_model()
    {
        $history = new Computer();

        [
            $glpi_computer,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_computer_model,
            $glpi_computer_type,
            $computer_type,
            $infocom,
            $usage_profile,
            $zone,
        ] = $this->getHistorizableComputer();
        $this->deleteItem($glpi_computer_model);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_usage_profile'           => true,
            'has_location'                => true,
            'has_category'                => true,
            'has_carbon_intensity_zone'   => true,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => true,
            'has_type_power_consumption'  => true,
            'has_inventory_entry_date'    => true,
            'ci_download_enabled'         => true,
            'ci_fallback_available'       => true,
            'not_is_ignore'               => true,
            'has_decommission_date'       => true,
        ];
        $result = $history->getHistorizableDiagnosis($glpi_computer);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_computer_has_no_model_power_consumption()
    {
        $history = new Computer();

        [
            $glpi_computer,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_computer_model,
            $glpi_computer_type,
            $computer_type,
            $infocom,
            $usage_profile,
            $zone,
        ] = $this->getHistorizableComputer();
        $this->updateItem($glpi_computer_model, ['power_consumption' => 0]);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_usage_profile'           => true,
            'has_location'                => true,
            'has_category'                => true,
            'has_carbon_intensity_zone'   => true,
            'has_model'                   => true,
            'has_model_power_consumption' => false,
            'has_type'                    => true,
            'has_type_power_consumption'  => true,
            'has_inventory_entry_date'    => true,
            'ci_download_enabled'         => true,
            'ci_fallback_available'       => true,
            'not_is_ignore'               => true,
            'has_decommission_date'       => true,
        ];
        $result = $history->getHistorizableDiagnosis($glpi_computer);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_computer_has_no_type()
    {
        $history = new Computer();

        [
            $glpi_computer,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_computer_model,
            $glpi_computer_type,
            $computer_type,
            $infocom,
            $usage_profile,
            $zone,
        ] = $this->getHistorizableComputer();
        $this->deleteItem($glpi_computer_type, true);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_usage_profile'           => true,
            'has_location'                => true,
            'has_category'                => false,
            'has_carbon_intensity_zone'   => true,
            'has_model'                   => true,
            'has_model_power_consumption' => true,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_inventory_entry_date'    => true,
            'ci_download_enabled'         => true,
            'ci_fallback_available'       => true,
            'not_is_ignore'               => true,
            'has_decommission_date'       => true,
        ];
        $result = $history->getHistorizableDiagnosis($glpi_computer);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_computer_has_no_type_power_consumption()
    {
        $history = new Computer();

        [
            $glpi_computer,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_computer_model,
            $glpi_computer_type,
            $computer_type,
            $infocom,
            $usage_profile,
            $zone,
        ] = $this->getHistorizableComputer();
        $this->updateItem($computer_type, ['power_consumption' => 0]);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_usage_profile'           => true,
            'has_location'                => true,
            'has_category'                => true,
            'has_carbon_intensity_zone'   => true,
            'has_model'                   => true,
            'has_model_power_consumption' => true,
            'has_type'                    => true,
            'has_type_power_consumption'  => false,
            'has_inventory_entry_date'    => true,
            'ci_download_enabled'         => true,
            'ci_fallback_available'       => true,
            'not_is_ignore'               => true,
            'has_decommission_date'       => true,
        ];
        $result = $history->getHistorizableDiagnosis($glpi_computer);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_computer_has_no_inventory_entry_date()
    {
        $history = new Computer();

        [
            $glpi_computer,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_computer_model,
            $glpi_computer_type,
            $computer_type,
            $infocom,
            $usage_profile,
            $zone,
        ] = $this->getHistorizableComputer();
        $this->updateItem($infocom, ['use_date' => null]);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_usage_profile'           => true,
            'has_location'                => true,
            'has_category'                => true,
            'has_carbon_intensity_zone'   => true,
            'has_model'                   => true,
            'has_model_power_consumption' => true,
            'has_type'                    => true,
            'has_type_power_consumption'  => true,
            'has_inventory_entry_date'    => false,
            'ci_download_enabled'         => true,
            'ci_fallback_available'       => true,
            'not_is_ignore'               => true,
            'has_decommission_date'       => true,
        ];
        $result = $history->getHistorizableDiagnosis($glpi_computer);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_computer_has_no_carbon_intensity_download_enabled()
    {
        $history = new Computer();

        [
            $glpi_computer,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_computer_model,
            $glpi_computer_type,
            $computer_type,
            $infocom,
            $usage_profile,
            $zone,
        ] = $this->getHistorizableComputer();
        $this->updateItem($source_zone, ['is_download_enabled' => 0]);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_usage_profile'           => true,
            'has_location'                => true,
            'has_category'                => true,
            'has_carbon_intensity_zone'   => true,
            'has_model'                   => true,
            'has_model_power_consumption' => true,
            'has_type'                    => true,
            'has_type_power_consumption'  => true,
            'has_inventory_entry_date'    => true,
            'ci_download_enabled'         => false,
            'ci_fallback_available'       => true,
            'not_is_ignore'               => true,
            'has_decommission_date'       => true,
        ];
        $result = $history->getHistorizableDiagnosis($glpi_computer);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_computer_has_no_carbon_intensity_fallback_data()
    {
        $history = new Computer();

        [
            $glpi_computer,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_computer_model,
            $glpi_computer_type,
            $computer_type,
            $infocom,
            $usage_profile,
            $zone,
        ] = $this->getHistorizableComputer();
        $source_zone->deleteByCriteria([
            ['NOT' => ['id' => $source_zone->getID()]],
        ]);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_usage_profile'           => true,
            'has_location'                => true,
            'has_category'                => true,
            'has_carbon_intensity_zone'   => true,
            'has_model'                   => true,
            'has_model_power_consumption' => true,
            'has_type'                    => true,
            'has_type_power_consumption'  => true,
            'has_inventory_entry_date'    => true,
            'ci_download_enabled'         => true,
            'ci_fallback_available'       => false,
            'not_is_ignore'               => true,
            'has_decommission_date'       => true,
        ];
        $result = $history->getHistorizableDiagnosis($glpi_computer);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_computer_is_ignored()
    {
        $history = new Computer();

        [
            $glpi_computer,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_computer_model,
            $glpi_computer_type,
            $computer_type,
            $infocom,
            $usage_profile,
            $zone,
        ] = $this->getHistorizableComputer();
        $this->updateItem($computer_type, ['is_ignore' => 1]);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_usage_profile'           => true,
            'has_location'                => true,
            'has_category'                => true,
            'has_carbon_intensity_zone'   => true,
            'has_model'                   => true,
            'has_model_power_consumption' => true,
            'has_type'                    => true,
            'has_type_power_consumption'  => true,
            'has_inventory_entry_date'    => true,
            'ci_download_enabled'         => true,
            'ci_fallback_available'       => true,
            'not_is_ignore'               => false,
            'has_decommission_date'       => true,
        ];
        $result = $history->getHistorizableDiagnosis($glpi_computer);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_computer_has_no_decommission_date()
    {
        $history = new Computer();

        [
            $glpi_computer,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_computer_model,
            $glpi_computer_type,
            $computer_type,
            $infocom,
            $usage_profile,
            $zone,
        ] = $this->getHistorizableComputer();
        $this->updateItem($infocom, ['decommission_date' => null]);

        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_usage_profile'           => true,
            'has_location'                => true,
            'has_category'                => true,
            'has_carbon_intensity_zone'   => true,
            'has_model'                   => true,
            'has_model_power_consumption' => true,
            'has_type'                    => true,
            'has_type_power_consumption'  => true,
            'has_inventory_entry_date'    => true,
            'ci_download_enabled'         => true,
            'ci_fallback_available'       => true,
            'not_is_ignore'               => true,
            'has_decommission_date'       => false,
        ];
        $result = $history->getHistorizableDiagnosis($glpi_computer);
        $this->assertEquals($expected, $result);
    }

    public function testComputerWithEverythingIsHistorizable()
    {
        $history = new Computer();

        $glpi_location = $this->createItem(GlpiLocation::class);
        $source = new Source(); // This source exists after a fresh install
        $source->getFromDBByCrit([
            'name' => 'RTE',
        ]);
        $zone = new Zone(); // This zone  exists after a fresh install
        $zone->getFromDBByCrit([
            'name' => 'France',
        ]);
        $source_zone = new Source_Zone(); // the relation source / zone also exists after a fresh install
        $source_zone->getFromDBByCrit([
            $source::getForeignKeyField() => $source->getID(),
            $zone::getForeignKeyField() => $zone->getID(),
        ]);
        $location = $this->createItem(Location::class, [
            'locations_id' => $glpi_location->getID(),
            'plugin_carbon_sources_zones_id' => $source_zone->getID(),
        ]);
        $glpi_computer_type = $this->createItem(GlpiComputerType::class);
        $computer_type = $this->createItem(ComputerType::class, [
            'power_consumption' => 55,
            'computertypes_id' => $glpi_computer_type->getID(),
        ]);
        $computer = $this->createItem(GlpiComputer::class, [
            'locations_id' => $glpi_location->getID(),
            'computertypes_id' => $glpi_computer_type->getID(),
        ]);
        $management = $this->createItem(Infocom::class, [
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID(),
            'use_date' => '2020-01-01',
            'decommission_date' => '2028-05-01',
        ]);
        $usage_profile = $this->createItem(ComputerUsageProfile::class);
        $impact = $this->createItem(UsageInfo::class, [
            $usage_profile->getForeignKeyField() => $usage_profile->getID(),
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID(),
        ]);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => true,
            'has_carbon_intensity_zone'   => true,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => true,
            'has_type_power_consumption'  => true,
            'has_usage_profile'           => true,
            'has_category'                => false,
            'has_inventory_entry_date'    => true,
            'ci_download_enabled'         => true,
            'ci_fallback_available'       => true,
            'not_is_ignore'               => true,
            'has_decommission_date'       => true,
        ];
        $result = $history->getHistorizableDiagnosis($computer);
        $this->assertEquals($expected, $result);
    }
}
