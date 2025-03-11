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
use NetworkEquipmentModel;

/**
 * @covers \GlpiPlugin\Carbon\Impact\History\NetworkEquipment
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
            'country' => PLUGIN_CARBON_TEST_FAKE_ZONE_NAME,
        ]);
        $model = $this->getItem(NetworkEquipmentModel::class, ['power_consumption' => $model_power]);
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

        $asset->update([
            'id' => $asset->getID(),
            'comment' => 'test date_mod',
        ]);
        $output = $this->callPrivateMethod($instance, 'getStartDate', $asset->getID());
        $this->assertEquals($_SESSION["glpi_currenttime"], $output->format('Y-m-d H:i:s'));

        $asset->update([
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

        $infocom->update([
            'id'       => $infocom->getID(),
            'buy_date' => '2018-01-01 00:00:00',
        ]);
        $output = $this->callPrivateMethod($instance, 'getStartDate', $asset->getID());
        $this->assertEquals('2018-01-01 00:00:00', $output->format('Y-m-d H:i:s'));

        $infocom->update([
            'id'            => $infocom->getID(),
            'delivery_date' => '2018-01-01 00:00:00',
        ]);
        $output = $this->callPrivateMethod($instance, 'getStartDate', $asset->getID());
        $this->assertEquals('2018-01-01 00:00:00', $output->format('Y-m-d H:i:s'));

        $infocom->update([
            'id'       => $infocom->getID(),
            'use_date' => '2017-01-01 00:00:00',
        ]);
        $output = $this->callPrivateMethod($instance, 'getStartDate', $asset->getID());
        $this->assertEquals('2017-01-01 00:00:00', $output->format('Y-m-d H:i:s'));
    }

    public function testCanHistorize()
    {
        $network_equipment = $this->getItem(GlpiNetworkEquipment::class);
        $id = $network_equipment->getID();

        // Check we cannot historize an empty item
        $history = new NetworkEquipment();
        $this->assertFalse($history->canHistorize($id));

        // Add empty info on the asset
        $management = $this->getItem(Infocom::class, [
            'itemtype' => $network_equipment->getType(),
            'items_id' => $id,
        ]);
        $this->assertFalse($history->canHistorize($id));

        // Add a date of inventory entry
        $management->update([
            'id' => $management->getID(),
            'use_date' => '2020-01-01',
        ]);
        $this->assertFalse($history->canHistorize($id));

        // Add an empty location
        $location = $this->getItem(Location::class);
        $network_equipment->update([
            'id' => $id,
            'locations_id' => $location->getID(),
        ]);
        $this->assertFalse($history->canHistorize($id));

        // Add a country to the location
        $location->update([
            'id' => $location->getID(),
            'country' => 'France',
        ]);
        $this->assertFalse($history->canHistorize($id));

        // Add a model
        $model = $this->getItem(NetworkEquipmentModel::class);
        $this->assertFalse($history->canHistorize($id));
        $network_equipment->update([
            'id' => $id,
            'networkequipmentmodels_id' => $model->getID(),
        ]);
        $this->assertFalse($history->canHistorize($id));

        // Add a power consumption to the model
        $model->update([
            'id' => $model->getID(),
            'power_consumption' => 55,
        ]);
        $this->assertFalse($history->canHistorize($id));

        // add a type
        $type = $this->getItem(GlpiNetworkEquipmentType::class);
        $network_equipment->update([
            'id' => $id,
            'networkequipmenttypes_id' => $type->getID(),
        ]);
        $this->assertTrue($history->canHistorize($id));

        // Remove power consumption on model
        $model->update([
            'id' => $model->getID(),
            'power_consumption' => 0,
        ]);
        $this->assertFalse($history->canHistorize($id));

        // add a type power consumption
        $power_consumption = $this->getItem(NetworkEquipmentType::class, [
            GlpiNetworkEquipmentType::getForeignKeyField() => $type->getID(),
        ]);
        $this->assertFalse($history->canHistorize($id));

        // Set a type power consumption
        $power_consumption->update([
            'id' => $type->getID(),
            'power_consumption' => 55,
        ]);
        $this->assertTrue($history->canHistorize($id));

        // *** test blocking conditions ***

        // Put the asset in the trash bin
        $network_equipment->update([
            'id' => $id,
            'is_deleted' => 1,
        ]);
        $this->assertFalse($history->canHistorize($id));

        // Restore the asset
        $network_equipment->update([
            'id' => $id,
            'is_deleted' => 0,
        ]);

        // Transform the asset into a template
        $network_equipment->update([
            'id' => $id,
            'is_template' => 1,
        ]);
        $this->assertFalse($history->canHistorize($id));

        // Restore the asset
        $network_equipment->update([
            'id' => $id,
            'is_template' => 0,
        ]);
        $this->assertTrue($history->canHistorize($id));
    }
}
