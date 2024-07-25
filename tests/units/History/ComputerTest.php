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

use Computer as GlpiComputer;
use GlpiPlugin\Carbon\History\Computer;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use Location;
use DateInterval;
use DateTime;
use ComputerModel;
use ComputerType;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\EnvironnementalImpact;

class ComputerTest extends DbTestCase
{
    public function testGetEngine()
    {
        $computer = new GlpiComputer();
        $engine = Computer::getEngine($computer);
        $this->assertInstanceOf(\GlpiPlugin\Carbon\Engine\V1\Computer::class, $engine);
    }

    public function testHistorizeItem()
    {
        $this->login('glpi', 'glpi');
        $entities_id = $this->isolateInEntity('glpi', 'glpi');

        $model_power = 55;
        $location = $this->getItem(Location::class, [
            'country' => PLUGIN_CARBON_TEST_FAKE_ZONE_NAME,
        ]);
        $model = $this->getItem(ComputerModel::class, ['power_consumption' => $model_power]);
        $type = $this->getItem(ComputerType::class, []);
        $computer = $this->getItem(GlpiComputer::class, [
            'computertypes_id'  => $type->getID(),
            'computermodels_id' => $model->getID(),
            'locations_id'      => $location->getID(),
        ]);
        $usage_profile = $this->getItem(ComputerUsageProfile::class, [
            'average_load' => 0.3,
            'time_start'   => '09:00:00',
            'time_stop'    => '18:00:00',
            'day_1'        => '1',
            'day_2'        => '1',
            'day_3'        => '1',
            'day_4'        => '1',
            'day_5'        => '1',
            'day_6'        => '0',
            'day_7'        => '0',
        ]);
        $impact = $this->getItem(EnvironnementalImpact::class, [
            $usage_profile->getForeignKeyField() => $usage_profile->getID(),
            'computers_id' => $computer->getID(),
            $usage_profile->getForeignKeyField() => $usage_profile->getID(),
        ]);

        $history = new Computer();
        $count = $history->historizeItem(
            $computer->getID(),
            new DateTime('2024-02-01 00:00:00'),
            new DateTime('2024-02-07 00:00:00')
        );

        // Days interval is [$start_date, $end_date[
        $this->assertEquals(7, $count);
    }
}
