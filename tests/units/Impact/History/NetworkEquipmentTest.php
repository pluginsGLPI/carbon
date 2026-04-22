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
use DateTime;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\Impact\History\NetworkEquipment;
use GlpiPlugin\Carbon\Location;
use GlpiPlugin\Carbon\NetworkEquipmentModel;
use GlpiPlugin\Carbon\NetworkEquipmentType;
use GlpiPlugin\Carbon\Source;
use GlpiPlugin\Carbon\Source_Zone;
use GlpiPlugin\Carbon\Tests\Impact\History\CommonAsset;
use GlpiPlugin\Carbon\UsageInfo;
use GlpiPlugin\Carbon\Zone;
use Infocom;
use Location as GlpiLocation;
use NetworkEquipment as GlpiNetworkEquipment;
use NetworkEquipmentModel as GlpiNetworkEquipmentModel;
use NetworkEquipmentType as GlpiNetworkEquipmentType;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NetworkEquipment::class)]
class NetworkEquipmentTest extends CommonAsset
{
    protected string $history_type =  NetworkEquipment::class;
    protected string $asset_type = GlpiNetworkEquipment::class;

    /**
     * Create an asset with all required data to make it evaluable
     *
     * @return array<CommonDBTM> An asset and related objects
     */
    protected function getHistorizableNetworkEquipment(): array
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
        $glpi_networkequipment_type = $this->createItem(GlpiNetworkEquipmentType::class);
        $networkequipment_type = $this->createItem(NetworkEquipmentType::class, [
            'power_consumption' => 55,
            'networkequipmenttypes_id' => $glpi_networkequipment_type->getID(),
        ]);
        $glpi_networkequipment_model = $this->createItem(GlpiNetworkEquipmentModel::class, [
            'power_consumption' => 35,
        ]);
        $networkequipment_model = $this->createItem(NetworkEquipmentModel::class, [
            'networkequipmentmodels_id' => $glpi_networkequipment_model->getID(),
        ]);
        $glpi_networkequipment = $this->createItem(GlpiNetworkEquipment::class, [
            'locations_id' => $glpi_location->getID(),
            'networkequipmenttypes_id' => $glpi_networkequipment_type->getID(),
            'networkequipmentmodels_id' => $glpi_networkequipment_model->getID(),
        ]);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype' => $glpi_networkequipment->getType(),
            'items_id' => $glpi_networkequipment->getID(),
            'use_date' => '2020-01-01',
            'decommission_date' => '2028-05-01',
        ]);
        $impact = $this->createItem(UsageInfo::class, [
            'itemtype' => $glpi_networkequipment->getType(),
            'items_id' => $glpi_networkequipment->getID(),
        ]);

        return [
            $glpi_networkequipment,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_networkequipment_model,
            $glpi_networkequipment_type,
            $networkequipment_type,
            $infocom,
            $zone,
        ];
    }

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
        $glpi_location = $this->createItem(GlpiLocation::class, [
            'state' => 'Quebec',
        ]);
        $source = new Source(); // This source exists after a fresh install
        $source->getFromDBByCrit([
            'name' => 'Hydro Quebec',
        ]);
        $zone = new Zone(); // This zone  exists after a fresh install
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
        $model = $this->createItem(GlpiNetworkEquipmentModel::class, ['power_consumption' => $model_power]);
        $glpi_type = $this->createItem(GlpiNetworkEquipmentType::class);
        $type = $this->createItem(NetworkEquipmentType::class, [
            GlpiNetworkEquipmentType::getForeignKeyField() => $glpi_type->getID(),
        ]);
        $asset = $this->createItem(GlpiNetworkEquipment::class, [
            'networkequipmenttypes_id'  => $glpi_type->getID(),
            'networkequipmentmodels_id' => $model->getID(),
            'locations_id'              => $glpi_location->getID(),
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
        $asset = $this->createItem(GlpiNetworkEquipment::class, ['date_creation' => null, 'date_mod' => null]);
        $instance = new $this->history_type();
        $result = $this->callPrivateMethod($instance, 'getStartDate', $asset->getID());
        $this->assertNull($result);

        $this->updateItem($asset, [
            'id' => $asset->getID(),
            'comment' => 'test date_mod',
        ]);
        $result = $this->callPrivateMethod($instance, 'getStartDate', $asset->getID());
        $this->assertNull($result);

        $this->updateItem($asset, [
            'id' => $asset->getID(),
            'date_creation' => '2019-01-01 00:00:00',
        ]);
        $result = $this->callPrivateMethod($instance, 'getStartDate', $asset->getID());
        $this->assertEquals('2019-01-01 00:00:00', $result->format('Y-m-d H:i:s'));

        $infocom = $this->createItem(Infocom::class, [
            'itemtype' => $asset->getType(),
            'items_id' => $asset->getID(),
        ]);

        $result = $this->callPrivateMethod($instance, 'getStartDate', $asset->getID());
        $this->assertEquals('2019-01-01 00:00:00', $result->format('Y-m-d H:i:s'));

        $this->updateItem($infocom, [
            'id'       => $infocom->getID(),
            'buy_date' => '2018-01-01 00:00:00',
        ]);
        $result = $this->callPrivateMethod($instance, 'getStartDate', $asset->getID());
        $this->assertEquals('2018-01-01 00:00:00', $result->format('Y-m-d H:i:s'));

        $this->updateItem($infocom, [
            'id'            => $infocom->getID(),
            'delivery_date' => '2018-01-01 00:00:00',
        ]);
        $result = $this->callPrivateMethod($instance, 'getStartDate', $asset->getID());
        $this->assertEquals('2018-01-01 00:00:00', $result->format('Y-m-d H:i:s'));

        $this->updateItem($infocom, [
            'id'       => $infocom->getID(),
            'use_date' => '2017-01-01 00:00:00',
        ]);
        $result = $this->callPrivateMethod($instance, 'getStartDate', $asset->getID());
        $this->assertEquals('2017-01-01 00:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_getHistorizableDiagnosis_when_networkequipment_is_historizable()
    {
        $history = new NetworkEquipment();

        [
            $glpi_networkequipment,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_networkequipment_model,
            $glpi_networkequipment_type,
            $networkequipment_type,
            $infocom,
            $zone,
        ] = $this->getHistorizableNetworkEquipment();
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => true,
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

        $result = $history->getHistorizableDiagnosis($glpi_networkequipment);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_networkequipment_is_deleted()
    {
        $history = new NetworkEquipment();

        [
            $glpi_networkequipment,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_networkequipment_model,
            $glpi_networkequipment_type,
            $networkequipment_type,
            $infocom,
            $zone,
        ] = $this->getHistorizableNetworkEquipment();
        $this->updateItem($glpi_networkequipment, ['is_deleted' => 1]);
        $expected = [
            'is_deleted'                  => false,
            'is_template'                 => true,
            'has_location'                => true,
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

        $result = $history->getHistorizableDiagnosis($glpi_networkequipment);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_networkequipment_is_template()
    {
        $history = new NetworkEquipment();

        [
            $glpi_networkequipment,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_networkequipment_model,
            $glpi_networkequipment_type,
            $networkequipment_type,
            $infocom,
            $zone,
        ] = $this->getHistorizableNetworkEquipment();
        $this->updateItem($glpi_networkequipment, ['is_template' => 1]);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => false,
            'has_location'                => true,
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

        $result = $history->getHistorizableDiagnosis($glpi_networkequipment);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_networkequipment_has_no_location()
    {
        $history = new NetworkEquipment();

        [
            $glpi_networkequipment,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_networkequipment_model,
            $glpi_networkequipment_type,
            $networkequipment_type,
            $infocom,
            $zone,
        ] = $this->getHistorizableNetworkEquipment();
        $this->deleteItem($glpi_location, true);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => false,
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

        $result = $history->getHistorizableDiagnosis($glpi_networkequipment);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_networkequipment_has_no_model()
    {
        $history = new NetworkEquipment();

        [
            $glpi_networkequipment,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_networkequipment_model,
            $glpi_networkequipment_type,
            $networkequipment_type,
            $infocom,
            $zone,
        ] = $this->getHistorizableNetworkEquipment();
        $this->deleteItem($glpi_networkequipment_model, true);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => true,
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

        $result = $history->getHistorizableDiagnosis($glpi_networkequipment);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_networkequipment_has_no_model_power_consumption()
    {
        $history = new NetworkEquipment();

        [
            $glpi_networkequipment,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_networkequipment_model,
            $glpi_networkequipment_type,
            $networkequipment_type,
            $infocom,
            $zone,
        ] = $this->getHistorizableNetworkEquipment();
        $this->updateItem($glpi_networkequipment_model, ['power_consumption' => 0]);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => true,
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

        $result = $history->getHistorizableDiagnosis($glpi_networkequipment);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_networkequipment_has_no_type()
    {
        $history = new NetworkEquipment();

        [
            $glpi_networkequipment,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_networkequipment_model,
            $glpi_networkequipment_type,
            $networkequipment_type,
            $infocom,
            $zone,
        ] = $this->getHistorizableNetworkEquipment();
        $this->deleteItem($glpi_networkequipment_type, true);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => true,
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

        $result = $history->getHistorizableDiagnosis($glpi_networkequipment);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_networkequipment_has_no_type_extra_data()
    {
        $history = new NetworkEquipment();

        [
            $glpi_networkequipment,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_networkequipment_model,
            $glpi_networkequipment_type,
            $networkequipment_type,
            $infocom,
            $zone,
        ] = $this->getHistorizableNetworkEquipment();
        $this->deleteItem($networkequipment_type, true);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => true,
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

        $result = $history->getHistorizableDiagnosis($glpi_networkequipment);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_networkequipment_has_no_type_power_consumption()
    {
        $history = new NetworkEquipment();

        [
            $glpi_networkequipment,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_networkequipment_model,
            $glpi_networkequipment_type,
            $networkequipment_type,
            $infocom,
            $zone,
        ] = $this->getHistorizableNetworkEquipment();
        $this->updateItem($networkequipment_type, ['power_consumption' => 0]);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => true,
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

        $result = $history->getHistorizableDiagnosis($glpi_networkequipment);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_networkequipment_has_no_inventory_entry_date()
    {
        $history = new NetworkEquipment();

        [
            $glpi_networkequipment,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_networkequipment_model,
            $glpi_networkequipment_type,
            $networkequipment_type,
            $infocom,
            $zone,
        ] = $this->getHistorizableNetworkEquipment();
        $this->updateItem($infocom, ['use_date' => null]);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => true,
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

        $result = $history->getHistorizableDiagnosis($glpi_networkequipment);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_networkequipment_has_no_carbon_intensity_download_enabled()
    {
        $history = new NetworkEquipment();

        [
            $glpi_networkequipment,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_networkequipment_model,
            $glpi_networkequipment_type,
            $networkequipment_type,
            $infocom,
            $zone,
        ] = $this->getHistorizableNetworkEquipment();
        $this->updateItem($source_zone, ['is_download_enabled' => 0]);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => true,
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

        $result = $history->getHistorizableDiagnosis($glpi_networkequipment);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_networkequipment_has__no_carbon_intensity_fallback_data()
    {
        $history = new NetworkEquipment();

        [
            $glpi_networkequipment,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_networkequipment_model,
            $glpi_networkequipment_type,
            $networkequipment_type,
            $infocom,
            $zone,
        ] = $this->getHistorizableNetworkEquipment();
        $source_zone->deleteByCriteria([
            ['NOT' => ['id' => $source_zone->getID()]],
        ]);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => true,
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

        $result = $history->getHistorizableDiagnosis($glpi_networkequipment);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_networkequipment_is_ignored()
    {
        $history = new NetworkEquipment();

        [
            $glpi_networkequipment,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_networkequipment_model,
            $glpi_networkequipment_type,
            $networkequipment_type,
            $infocom,
            $zone,
        ] = $this->getHistorizableNetworkEquipment();
        $this->updateItem($networkequipment_type, ['is_ignore' => 1]);
        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => true,
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

        $result = $history->getHistorizableDiagnosis($glpi_networkequipment);
        $this->assertEquals($expected, $result);
    }

    public function test_getHistorizableDiagnosis_when_networkequipment_has_no_decommission_date()
    {
        $history = new NetworkEquipment();

        [
            $glpi_networkequipment,
            $glpi_location,
            $location,
            $source_zone,
            $glpi_networkequipment_model,
            $glpi_networkequipment_type,
            $networkequipment_type,
            $infocom,
            $zone,
        ] = $this->getHistorizableNetworkEquipment();
        $this->updateItem($infocom, ['decommission_date' => null]);

        $expected = [
            'is_deleted'                  => true,
            'is_template'                 => true,
            'has_location'                => true,
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

        $result = $history->getHistorizableDiagnosis($glpi_networkequipment);
        $this->assertEquals($expected, $result);
    }
}
