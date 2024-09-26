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

namespace GlpiPlugin\Carbon\History\Tests;

use DateTime;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\Tests\History\CommonAsset;
use GlpiPlugin\Carbon\History\NetworkEquipment;
use GlpiPlugin\Carbon\NetworkEquipmentType;
use Infocom;
use NetworkEquipmentType as GlpiNetworkEquipmentType;
use NetworkEquipment as GlpiNetworkEquipment;
use Location;
use NetworkEquipmentModel;

/**
 * @covers \GlpiPlugin\Carbon\History\NetworkEquipment
 */
class NetworkEquipmentTest extends CommonAsset
{
    protected string $history_type =  \GlpiPlugin\Carbon\History\NetworkEquipment::class;
    protected string $asset_type = GlpiNetworkEquipment::class;

    public function testGetEngine()
    {
        $asset = new GlpiNetworkEquipment();
        $engine = NetworkEquipment::getEngine($asset);
        $this->assertInstanceOf(\GlpiPlugin\Carbon\Engine\V1\NetworkEquipment::class, $engine);
    }

    public function testHistorizeItem()
    {
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
        $count = $history->historizeItem(
            $asset->getID(),
            new DateTime($start_date),
            new DateTime($end_date)
        );

        // Days interval is [$start_date, $end_date[
        $this->assertEquals(8, $count);

        $carbon_emission = new CarbonEmission();
        $emissions = $carbon_emission->find([
            ['date' => ['>=', $start_date]],
            ['date' =>  ['<', $end_date]],
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
}
