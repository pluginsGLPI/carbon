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
use ComputerModel;
use Monitor as GlpiMonitor;
use GlpiPlugin\Carbon\Impact\History\Monitor;
use GlpiPlugin\Carbon\Tests\Impact\History\CommonAsset;
use Infocom;
use Location as GlpiLocation;
use DateTime;
use MonitorModel as GlpiMonitorModel;
use MonitorType as GlpiMonitorType;
use ComputerType as GlpiComputerType;
use DBmysql;
use Glpi\Asset\Asset_PeripheralAsset;
use Computer_Item;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\Location;
use GlpiPlugin\Carbon\MonitorType;
use GlpiPlugin\Carbon\Source;
use GlpiPlugin\Carbon\Source_Zone;
use GlpiPlugin\Carbon\UsageInfo;
use GlpiPlugin\Carbon\Zone;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Monitor::class)]
class MonitorTest extends CommonAsset
{
    protected string $history_type = \GlpiPlugin\Carbon\Impact\History\Monitor::class;
    protected string $asset_type = GlpiMonitor::class;

    public function testGetEngine()
    {
        $asset = new GlpiMonitor();
        $engine = Monitor::getEngine($asset);
        $this->assertInstanceOf(\GlpiPlugin\Carbon\Engine\V1\Monitor::class, $engine);
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
            'name' => 'Hydro Quebec'
        ]);
        $zone = new Zone(); // This zone  exists after a fresh install
        $zone->getFromDBByCrit([
            'name' => 'Quebec'
        ]);
        $source_zone = new Source_Zone(); // the relation source / zone also exists after a fresh install
        $source_zone->getFromDBByCrit([
            $source::getForeignKeyField() => $source->getID(),
            $zone::getForeignKeyField() => $zone->getID()
        ]);
        $location = $this->createItem(Location::class, [
            'locations_id' => $glpi_location->getID(),
            'plugin_carbon_sources_zones_id' => $source_zone->getID()
        ]);

        $computer_model_power = 80;
        $computer_model = $this->createItem(ComputerModel::class, ['power_consumption' => $computer_model_power]);
        $glpi_computer_type = $this->createItem(GlpiComputerType::class);
        $computer_type = $this->createItem(ComputerType::class, [
            GlpiComputerType::getForeignKeyField() => $glpi_computer_type->getID(),
        ]);
        $computer = $this->createItem(GlpiComputer::class, [
            'computertypes_id'  => $glpi_computer_type->getID(),
            'computermodels_id' => $computer_model->getID(),
            'locations_id'      => $glpi_location->getID(),
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
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID(),
        ]);

        $model = $this->createItem(GlpiMonitorModel::class, ['power_consumption' => $model_power]);
        $glpi_type = $this->createItem(GlpiMonitorType::class);
        $type = $this->createItem(MonitorType::class, [
            GlpiMonitorType::getForeignKeyField() => $glpi_type->getID(),
        ]);
        $asset = $this->createItem(GlpiMonitor::class, [
            'monitortypes_id'   => $glpi_type->getID(),
            'monitormodels_id'  => $model->getID(),
            'locations_id'      => $glpi_location->getID(),
            'date_creation'     => '2024-01-01',
            'date_mod'          => null,
        ]);
        $computer_asset = $this->createItem(Computer_Item::class, [
            'computers_id' => $computer->getID(),
            'itemtype' => $asset->getType(),
            'items_id' => $asset->getID(),
        ]);

        $history = new Monitor();
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

        // Values from the fake carbon intensities added in global fixtures
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

    private static function getMonitorLinkedToComputer(?GlpiComputer $computer = null): CommonDBTM
    {
        $self = new self();
        if ($computer === null) {
            $computer = $self->createItem(GlpiComputer::class);
        }

        $item = $self->createItem(GlpiMonitor::class);

        $computer_item = $self->createItem(Computer_Item::class, [
            'computers_id' => $computer->getID(),
            'itemtype' => $item->getType(),
            'items_id' => $item->getID(),
        ]);

        return $item;
    }

    private static function addManagementToMonitor(CommonDBTM $item)
    {
        $self = new self();
        $management = $self->createItem(Infocom::class, [
            'itemtype' => $item->getType(),
            'items_id' => $item->getID(),
        ]);

        return $management;
    }

    private static function addDateToManagement(CommonDBTM $item)
    {
        $self = new self();

        $management = new Infocom();
        $management->getFromDBByCrit([
            'itemtype' => $item->getType(),
            'items_id' => $item->getID(),
        ]);
        $self->assertFalse($management->isNewItem(), "Infocom item not found");

        $self->updateItem($management, [
            'use_date' => '2020-01-01',
        ]);
    }

    public function testEmptyMonitorIsNotHistorizable()
    {
        $history = new Monitor();

        $monitor = $this->createItem(GlpiMonitor::class);
        $result = $history->getHistorizableDiagnosis($monitor);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_computer'                => false,
            'has_usage_profile'           => false,
            'has_location'                => false,
            'has_carbon_intensity_zone'   => false,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_inventory_entry_date'    => false,
            'ci_download_enabled'         => false,
            'ci_fallback_available'       => false,
            'not_is_ignore'               => true,
        ];
        $this->assertEquals($expected, $result);
        $expected = !in_array(false, $result, true);
        $result = $history->canHistorize($monitor->getID());
        $this->assertFalse($result);
    }

    public function testMonitorLinkedToComputerIsNotHistorizable()
    {
        $history = new Monitor();

        $monitor = $this->createItem(GlpiMonitor::class);
        $computer = $this->createItem(GlpiComputer::class);
        $computer_item = $this->createItem(Computer_Item::class, [
            'computers_id' => $computer->getID(),
            'itemtype'     => $monitor->getType(),
            'items_id'     => $monitor->getID(),
        ]);
        $result = $history->getHistorizableDiagnosis($monitor);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_computer'                => true,
            'has_usage_profile'           => false,
            'has_location'                => false,
            'has_carbon_intensity_zone'   => false,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_inventory_entry_date'    => false,
            'ci_download_enabled'         => false,
            'ci_fallback_available'       => false,
            'not_is_ignore'               => true,
        ];
        $this->assertEquals($expected, $result);
        $expected = !in_array(false, $result, true);
        $result = $history->canHistorize($monitor->getID());
        $this->assertFalse($result);
    }

    public function testMonitorWithEmptyInfocomIsNotHistorizable()
    {
        $history = new Monitor();

        $monitor = $this->createItem(GlpiMonitor::class);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype'     => $monitor->getType(),
            'items_id'     => $monitor->getID(),
        ]);
        $result = $history->getHistorizableDiagnosis($monitor);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_computer'                => false,
            'has_usage_profile'           => false,
            'has_location'                => false,
            'has_carbon_intensity_zone'   => false,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_inventory_entry_date'    => false,
            'ci_download_enabled'         => false,
            'ci_fallback_available'       => false,
            'not_is_ignore'               => true,
        ];
        $this->assertEquals($expected, $result);
        $expected = !in_array(false, $result, true);
        $result = $history->canHistorize($monitor->getID());
        $this->assertFalse($result);
    }

    public function testMonitorWithInfocomIsNotHistorizable()
    {
        $history = new Monitor();

        $monitor = $this->createItem(GlpiMonitor::class);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype'     => $monitor->getType(),
            'items_id'     => $monitor->getID(),
            'buy_date'     => '2024-01-01',
        ]);
        $result = $history->getHistorizableDiagnosis($monitor);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_computer'                => false,
            'has_usage_profile'           => false,
            'has_location'                => false,
            'has_carbon_intensity_zone'   => false,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_inventory_entry_date'    => true,
            'ci_download_enabled'         => false,
            'ci_fallback_available'       => false,
            'not_is_ignore'               => true,
        ];
        $this->assertEquals($expected, $result);
        $expected = !in_array(false, $result, true);
        $result = $history->canHistorize($monitor->getID());
        $this->assertFalse($result);
    }

    public function testMonitorWithEmptyLocationIsNotHistorizable()
    {
        $history = new Monitor();

        $monitor = $this->createItem(GlpiMonitor::class);
        $glpi_location = $this->createItem(GlpiLocation::class);
        $computer = $this->createItem(GlpiComputer::class, [
            'locations_id' => $glpi_location->getID(),
        ]);
        $computer_item = $this->createItem(Computer_Item::class, [
            'computers_id' => $computer->getID(),
            'itemtype'     => $monitor->getType(),
            'items_id'     => $monitor->getID(),
        ]);
        $result = $history->getHistorizableDiagnosis($monitor);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_computer'                => true,
            'has_usage_profile'           => false,
            'has_location'                => true,
            'has_carbon_intensity_zone'   => false,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_inventory_entry_date'    => false,
            'ci_download_enabled'         => false,
            'ci_fallback_available'       => false,
            'not_is_ignore'               => true,
        ];
        $this->assertEquals($expected, $result);
        $expected = !in_array(false, $result, true);
        $result = $history->canHistorize($monitor->getID());
        $this->assertFalse($result);
    }

    public function testMonitorWithLocationWithZoneIsNotHistorizable()
    {
        $history = new Monitor();

        $monitor = $this->createItem(GlpiMonitor::class);
        $glpi_location = $this->createItem(GlpiLocation::class);
        $source = new Source(); // This source exists after a fresh install
        $source->getFromDBByCrit([
            'name' => 'RTE'
        ]);
        $zone = new Zone(); // This zone  exists after a fresh install
        $zone->getFromDBByCrit([
            'name' => 'France'
        ]);
        $source_zone = new Source_Zone(); // the relation source / zone also exists after a fresh install
        $source_zone->getFromDBByCrit([
            $source::getForeignKeyField() => $source->getID(),
            $zone::getForeignKeyField() => $zone->getID()
        ]);
        $location = $this->createItem(Location::class, [
            'locations_id' => $glpi_location->getID(),
            'plugin_carbon_sources_zones_id' => $source_zone->getID()
        ]);
        $computer = $this->createItem(GlpiComputer::class, [
            'locations_id' => $glpi_location->getID(),
        ]);
        $computer_item = $this->createItem(Computer_Item::class, [
            'computers_id' => $computer->getID(),
            'itemtype'     => $monitor->getType(),
            'items_id'     => $monitor->getID(),
        ]);

        $result = $history->getHistorizableDiagnosis($monitor);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_computer'                => true,
            'has_usage_profile'           => false,
            'has_location'                => true,
            'has_carbon_intensity_zone'   => true,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_inventory_entry_date'    => false,
            'ci_download_enabled'         => false,
            'ci_fallback_available'       => false,
            'not_is_ignore'               => true,
        ];
        $this->assertEquals($expected, $result);
        $expected = !in_array(false, $result, true);
        $result = $history->canHistorize($monitor->getID());
        $this->assertFalse($result);
    }

    public function testMonitorWithUsageProfileIsNotHistorizable()
    {
        $history = new Monitor();

        $monitor = $this->createItem(GlpiMonitor::class);
        $computer = $this->createItem(GlpiComputer::class);
        $computer_item = $this->createItem(Computer_Item::class, [
            'computers_id' => $computer->getID(),
            'itemtype'     => $monitor->getType(),
            'items_id'     => $monitor->getID(),
        ]);
        $usage_profile = $this->createItem(ComputerUsageProfile::class);
        $impact = $this->createItem(UsageInfo::class, [
            $usage_profile->getForeignKeyField() => $usage_profile->getID(),
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID(),
        ]);
        $result = $history->getHistorizableDiagnosis($monitor);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_computer'                => true,
            'has_usage_profile'           => true,
            'has_location'                => false,
            'has_carbon_intensity_zone'   => false,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_inventory_entry_date'    => false,
            'ci_download_enabled'         => false,
            'ci_fallback_available'       => false,
            'not_is_ignore'               => true,
        ];
        $this->assertEquals($expected, $result);
        $expected = !in_array(false, $result, true);
        $result = $history->canHistorize($monitor->getID());
        $this->assertFalse($result);
    }

    public function testMonitorWithEmptyModelIsNotHistorizable()
    {
        $history = new Monitor();

        $glpi_monitor_model = $this->createItem(GlpiMonitorModel::class);
        $monitor = $this->createItem(GlpiMonitor::class, [
            'monitormodels_id' => $glpi_monitor_model->getID(),
        ]);
        $result = $history->getHistorizableDiagnosis($monitor);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_computer'                => false,
            'has_usage_profile'           => false,
            'has_location'                => false,
            'has_carbon_intensity_zone'   => false,
            'has_model'                   => true,
            'has_model_power_consumption' => false,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_inventory_entry_date'    => false,
            'ci_download_enabled'         => false,
            'ci_fallback_available'       => false,
            'not_is_ignore'               => true,
        ];
        $this->assertEquals($expected, $result);
        $expected = !in_array(false, $result, true);
        $result = $history->canHistorize($monitor->getID());
        $this->assertFalse($result);
    }

    public function testMonitorWithModelIsNotHistorizable()
    {
        $history = new Monitor();

        $glpi_monitor_model = $this->createItem(GlpiMonitorModel::class, [
            'power_consumption' => 35,
        ]);
        $monitor = $this->createItem(GlpiMonitor::class, [
            'monitormodels_id' => $glpi_monitor_model->getID(),
        ]);
        $result = $history->getHistorizableDiagnosis($monitor);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_computer'                => false,
            'has_usage_profile'           => false,
            'has_location'                => false,
            'has_carbon_intensity_zone'   => false,
            'has_model'                   => true,
            'has_model_power_consumption' => true,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_inventory_entry_date'    => false,
            'ci_download_enabled'         => false,
            'ci_fallback_available'       => false,
            'not_is_ignore'               => true,
        ];
        $this->assertEquals($expected, $result);
        $expected = !in_array(false, $result, true);
        $result = $history->canHistorize($monitor->getID());
        $this->assertFalse($result);
    }

    public function testMonitorWithEmptyTypeIsNotHistorizable()
    {
        $history = new Monitor();

        $glpi_monitor_type = $this->createItem(GlpiMonitorType::class);
        $monitor_type = $this->createItem(MonitorType::class, [
            'power_consumption' => 55,
            'monitortypes_id' => $glpi_monitor_type->getID(),
        ]);
        $monitor = $this->createItem(GlpiMonitor::class, [
            'monitortypes_id' => $glpi_monitor_type->getID(),
        ]);
        $result = $history->getHistorizableDiagnosis($monitor);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_computer'                => false,
            'has_usage_profile'           => false,
            'has_location'                => false,
            'has_carbon_intensity_zone'   => false,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => true,
            'has_type_power_consumption'  => true,
            'has_inventory_entry_date'    => false,
            'ci_download_enabled'         => false,
            'ci_fallback_available'       => false,
            'not_is_ignore'               => true,
        ];
        $this->assertEquals($expected, $result);
        $expected = !in_array(false, $result, true);
        $result = $history->canHistorize($monitor->getID());
        $this->assertFalse($result);
    }

    public function testMonitorWithTypeIsNotHistorizable()
    {
        $history = new Monitor();

        $glpi_monitor_type = $this->createItem(GlpiMonitorType::class);
        $monitor_type = $this->createItem(MonitorType::class, [
            'monitortypes_id'   => $glpi_monitor_type->getID(),
            'power_consumption' => 55,
        ]);
        $monitor = $this->createItem(GlpiMonitor::class, [
            'monitortypes_id' => $glpi_monitor_type->getID(),
        ]);
        $result = $history->getHistorizableDiagnosis($monitor);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_computer'                => false,
            'has_usage_profile'           => false,
            'has_location'                => false,
            'has_carbon_intensity_zone'   => false,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => true,
            'has_type_power_consumption'  => true,
            'has_inventory_entry_date'    => false,
            'ci_download_enabled'         => false,
            'ci_fallback_available'       => false,
            'not_is_ignore'               => true,
        ];
        $this->assertEquals($expected, $result);
        $expected = !in_array(false, $result, true);
        $result = $history->canHistorize($monitor->getID());
        $this->assertFalse($result);
    }


    public function testMonitorIsHistorizable()
    {
        $history = new Monitor();

        $glpi_location = $this->createItem(GlpiLocation::class);
        $source = new Source(); // This source exists after a fresh install
        $source->getFromDBByCrit([
            'name' => 'RTE'
        ]);
        $zone = new Zone(); // This zone  exists after a fresh install
        $zone->getFromDBByCrit([
            'name' => 'France'
        ]);
        $source_zone = new Source_Zone(); // the relation source / zone also exists after a fresh install
        $source_zone->getFromDBByCrit([
            $source::getForeignKeyField() => $source->getID(),
            $zone::getForeignKeyField() => $zone->getID()
        ]);
        $location = $this->createItem(Location::class, [
            'locations_id' => $glpi_location->getID(),
            'plugin_carbon_sources_zones_id' => $source_zone->getID()
        ]);
        $glpi_monitor_model = $this->createItem(GlpiMonitorModel::class, [
            'power_consumption' => 35,
        ]);
        $monitor = $this->createItem(GlpiMonitor::class, [
            'monitormodels_id' => $glpi_monitor_model->getID(),
        ]);
        $computer = $this->createItem(GlpiComputer::class, [
            'locations_id' => $glpi_location->getID(),
        ]);
        $computer_item = $this->createItem(Computer_Item::class, [
            'computers_id' => $computer->getID(),
            'itemtype'     => $monitor->getType(),
            'items_id'     => $monitor->getID(),
        ]);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype'     => $monitor->getType(),
            'items_id'     => $monitor->getID(),
            'buy_date'     => '2024-01-01',
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
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID(),
        ]);

        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_computer'                => true,
            'has_usage_profile'           => true,
            'has_location'                => true,
            'has_carbon_intensity_zone'   => true,
            'has_model'                   => true,
            'has_model_power_consumption' => true,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_inventory_entry_date'    => true,
            'ci_download_enabled'         => false,
            'ci_fallback_available'       => false,
            'not_is_ignore'               => true,
        ];
        $result = $history->getHistorizableDiagnosis($monitor);
        $this->assertEquals($expected, $result);
        $expected = !in_array(false, $result, true);
        $result = $history->canHistorize($monitor->getID());
        $this->assertTrue($result);
    }
}
