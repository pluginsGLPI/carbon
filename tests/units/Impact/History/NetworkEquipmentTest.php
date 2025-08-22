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

use DateTime;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\Tests\Impact\History\CommonAsset;
use GlpiPlugin\Carbon\Impact\History\NetworkEquipment;
use GlpiPlugin\Carbon\NetworkEquipmentType;
use Infocom;
use NetworkEquipmentType as GlpiNetworkEquipmentType;
use NetworkEquipment as GlpiNetworkEquipment;
use Location;
use NetworkEquipmentModel as GlpiNetworkEquipmentModel;

/**
 * #CoversMethod \GlpiPlugin\Carbon\Impact\History\NetworkEquipment
 */
class NetworkEquipmentTest extends CommonAsset
{
    protected string $history_type =  \GlpiPlugin\Carbon\Impact\History\NetworkEquipment::class;
    protected string $asset_type = GlpiNetworkEquipment::class;

    public function testGetEngine()
    {
        $asset = new GlpiNetworkEquipment();
        $engine = NetworkEquipment::getEngine($asset);
        $this->assertInstanceOf(\GlpiPlugin\Carbon\Engine\V1\NetworkEquipment::class, $engine);
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

        $model_power = 100;
        $location = $this->getItem(Location::class, [
            'state' => 'Quebec',
        ]);
        $model = $this->getItem(GlpiNetworkEquipmentModel::class, ['power_consumption' => $model_power]);
        $glpi_type = $this->getItem(GlpiNetworkEquipmentType::class);
        $type = $this->getItem(NetworkEquipmentType::class, [
            GlpiNetworkEquipmentType::getForeignKeyField() => $glpi_type->getID(),
        ]);
        $asset = $this->getItem(GlpiNetworkEquipment::class, [
            'networkequipmenttypes_id'  => $glpi_type->getID(),
            'networkequipmentmodels_id' => $model->getID(),
            'locations_id'              => $location->getID(),
            'date_creation'             => '2024-01-01',
            'date_mod'                  => null,
        ]);
        $history = new NetworkEquipment();
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
    }

    public function testGetStartDate()
    {
        $asset = $this->getItem(GlpiNetworkEquipment::class, ['date_creation' => null, 'date_mod' => null]);
        $instance = new $this->history_type();
        $output = $this->callPrivateMethod($instance, 'getStartDate', $asset->getID());
        $this->assertNull($output);

        $this->updateItem($asset, [
            'id' => $asset->getID(),
            'comment' => 'test date_mod',
        ]);
        $output = $this->callPrivateMethod($instance, 'getStartDate', $asset->getID());
        $this->assertEquals($_SESSION["glpi_currenttime"], $output->format('Y-m-d H:i:s'));

        $this->updateItem($asset, [
            'id' => $asset->getID(),
            'date_creation' => '2019-01-01 00:00:00',
        ]);
        $output = $this->callPrivateMethod($instance, 'getStartDate', $asset->getID());
        $this->assertEquals('2019-01-01 00:00:00', $output->format('Y-m-d H:i:s'));

        $infocom = $this->getItem(Infocom::class, [
            'itemtype' => $asset->getType(),
            'items_id' => $asset->getID(),
        ]);

        $output = $this->callPrivateMethod($instance, 'getStartDate', $asset->getID());
        $this->assertEquals('2019-01-01 00:00:00', $output->format('Y-m-d H:i:s'));

        $this->updateItem($infocom, [
            'id'       => $infocom->getID(),
            'buy_date' => '2018-01-01 00:00:00',
        ]);
        $output = $this->callPrivateMethod($instance, 'getStartDate', $asset->getID());
        $this->assertEquals('2018-01-01 00:00:00', $output->format('Y-m-d H:i:s'));

        $this->updateItem($infocom, [
            'id'            => $infocom->getID(),
            'delivery_date' => '2018-01-01 00:00:00',
        ]);
        $output = $this->callPrivateMethod($instance, 'getStartDate', $asset->getID());
        $this->assertEquals('2018-01-01 00:00:00', $output->format('Y-m-d H:i:s'));

        $this->updateItem($infocom, [
            'id'       => $infocom->getID(),
            'use_date' => '2017-01-01 00:00:00',
        ]);
        $output = $this->callPrivateMethod($instance, 'getStartDate', $asset->getID());
        $this->assertEquals('2017-01-01 00:00:00', $output->format('Y-m-d H:i:s'));
    }

    public function testEmptyItemIsNotHistorizable()
    {
        $history = new NetworkEquipment();

        $network_equipment = $this->getItem(GlpiNetworkEquipment::class);
        $result = $history->getHistorizableDiagnosis($network_equipment);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => false,
            'has_state_or_country'        => false,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_inventory_entry_date'    => false,
        ];
        $this->assertEquals($expected, $result);
        $expected = !in_array(false, $result, true);
        $result = $history->canHistorize($network_equipment->getID());
        $this->assertFalse($result);
    }

    public function testNetDeviceWithEmptyInfocomIsNotHistorizable()
    {
        $history = new NetworkEquipment();

        $network_equipment = $this->getItem(GlpiNetworkEquipment::class);
        $infocom = $this->getItem(Infocom::class, [
            'itemtype'     => $network_equipment->getType(),
            'items_id'     => $network_equipment->getID(),
        ]);
        $result = $history->getHistorizableDiagnosis($network_equipment);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => false,
            'has_state_or_country'        => false,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_inventory_entry_date'    => false,
        ];
        $this->assertEquals($expected, $result);
        $expected = !in_array(false, $result, true);
        $result = $history->canHistorize($network_equipment->getID());
        $this->assertFalse($result);
    }

    public function testNetDeviceWithInfocomIsNotHistorizable()
    {
        $history = new NetworkEquipment();

        $network_equipment = $this->getItem(GlpiNetworkEquipment::class);
        $infocom = $this->getItem(Infocom::class, [
            'itemtype'     => $network_equipment->getType(),
            'items_id'     => $network_equipment->getID(),
            'buy_date'     => '2024-01-01',
        ]);
        $result = $history->getHistorizableDiagnosis($network_equipment);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => false,
            'has_state_or_country'        => false,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_inventory_entry_date'    => true,
        ];
        $this->assertEquals($expected, $result);
        $expected = !in_array(false, $result, true);
        $result = $history->canHistorize($network_equipment->getID());
        $this->assertFalse($result);
    }

    public function testNetDeviceWithEmptyLocationIsNotHistorizable()
    {
        $history = new NetworkEquipment();

        $location = $this->getItem(Location::class);
        $network_equipment = $this->getItem(GlpiNetworkEquipment::class, [
            'locations_id' => $location->getID(),
        ]);
        $result = $history->getHistorizableDiagnosis($network_equipment);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => true,
            'has_state_or_country'        => false,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_inventory_entry_date'    => false,
        ];
        $this->assertEquals($expected, $result);
        $expected = !in_array(false, $result, true);
        $result = $history->canHistorize($network_equipment->getID());
        $this->assertFalse($result);
    }

    public function testNetDeviceWithLocationWithCountryIsNotHistorizable()
    {
        $history = new NetworkEquipment();

        $location = $this->getItem(Location::class, [
            'country' => 'France',
        ]);
        $network_equipment = $this->getItem(GlpiNetworkEquipment::class, [
            'locations_id' => $location->getID(),
        ]);
        $result = $history->getHistorizableDiagnosis($network_equipment);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => true,
            'has_state_or_country'        => true,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_inventory_entry_date'    => false,
        ];
        $this->assertEquals($expected, $result);
        $expected = !in_array(false, $result, true);
        $result = $history->canHistorize($network_equipment->getID());
        $this->assertFalse($result);
    }

    public function testNetDeviceWithLocationWithStateIsNotHistorizable()
    {
        $history = new NetworkEquipment();

        $location = $this->getItem(Location::class, [
            'state' => 'Quebec',
        ]);
        $network_equipment = $this->getItem(GlpiNetworkEquipment::class, [
            'locations_id' => $location->getID(),
        ]);
        $result = $history->getHistorizableDiagnosis($network_equipment);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => true,
            'has_state_or_country'        => true,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_inventory_entry_date'    => false,
        ];
        $this->assertEquals($expected, $result);
        $expected = !in_array(false, $result, true);
        $result = $history->canHistorize($network_equipment->getID());
        $this->assertFalse($result);
    }

    public function testNetDeviceWithEmptyModelIsNotHistorizable()
    {
        $history = new NetworkEquipment();

        $glpi_model = $this->getItem(GlpiNetworkEquipmentModel::class);
        $network_equipment = $this->getItem(GlpiNetworkEquipment::class, [
            'networkequipmentmodels_id' => $glpi_model->getID(),
        ]);
        $result = $history->getHistorizableDiagnosis($network_equipment);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => false,
            'has_state_or_country'        => false,
            'has_model'                   => true,
            'has_model_power_consumption' => false,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_inventory_entry_date'    => false,
        ];
        $this->assertEquals($expected, $result);
        $expected = !in_array(false, $result, true);
        $result = $history->canHistorize($network_equipment->getID());
        $this->assertFalse($result);
    }

    public function testNetDeviceWithModelIsNotHistorizable()
    {
        $history = new NetworkEquipment();

        $glpi_model = $this->getItem(GlpiNetworkEquipmentModel::class, [
            'power_consumption' => 60,
        ]);
        $network_equipment = $this->getItem(GlpiNetworkEquipment::class, [
            'networkequipmentmodels_id' => $glpi_model->getID(),
        ]);
        $result = $history->getHistorizableDiagnosis($network_equipment);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => false,
            'has_state_or_country'        => false,
            'has_model'                   => true,
            'has_model_power_consumption' => true,
            'has_type'                    => false,
            'has_type_power_consumption'  => false,
            'has_inventory_entry_date'    => false,
        ];
        $this->assertEquals($expected, $result);
        $expected = !in_array(false, $result, true);
        $result = $history->canHistorize($network_equipment->getID());
        $this->assertFalse($result);
    }

    public function testNetDeviceWithEmptyTypeIsNotHistorizable()
    {
        $history = new NetworkEquipment();

        $glpi_type = $this->getItem(GlpiNetworkEquipmentType::class);
        $network_equipment = $this->getItem(GlpiNetworkEquipment::class, [
            'networkequipmenttypes_id' => $glpi_type->getID(),
        ]);
        $result = $history->getHistorizableDiagnosis($network_equipment);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => false,
            'has_state_or_country'        => false,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => true,
            'has_type_power_consumption'  => false,
            'has_inventory_entry_date'    => false,
        ];
        $this->assertEquals($expected, $result);
        $expected = !in_array(false, $result, true);
        $result = $history->canHistorize($network_equipment->getID());
        $this->assertFalse($result);
    }

    public function testNetDeviceWithTypeIsNotHistorizable()
    {
        $history = new NetworkEquipment();

        $glpi_type = $this->getItem(GlpiNetworkEquipmentType::class);
        $network_equipment_type = $this->getItem(NetworkEquipmentType::class, [
            'power_consumption' => 55,
            'networkequipmenttypes_id' => $glpi_type->getID(),
        ]);
        $network_equipment = $this->getItem(GlpiNetworkEquipment::class, [
            'networkequipmenttypes_id' => $glpi_type->getID(),
        ]);
        $result = $history->getHistorizableDiagnosis($network_equipment);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => false,
            'has_state_or_country'        => false,
            'has_model'                   => false,
            'has_model_power_consumption' => false,
            'has_type'                    => true,
            'has_type_power_consumption'  => true,
            'has_inventory_entry_date'    => false,
        ];
        $this->assertEquals($expected, $result);
        $expected = !in_array(false, $result, true);
        $result = $history->canHistorize($network_equipment->getID());
        $this->assertFalse($result);
    }
}
