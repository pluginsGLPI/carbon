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

namespace GlpiPlugin\Carbon\Engine\V1\Tests;

use DateTime;
use GlpiPlugin\Carbon\Tests\Engine\V1\EngineTestCase;
use GlpiPlugin\Carbon\Engine\V1\NetworkEquipment;
use GlpiPlugin\Carbon\NetworkEquipmentType;
use GlpiPlugin\Carbon\CarbonIntensityZone;
use NetworkEquipment as GlpiNetworkEquipment;
use NetworkEquipmentModel;
use NetworkEquipmentType as GlpiNetworkEquipmentType;

class NetworkEquipmentTest extends EngineTestCase
{
    protected static string $engine_class = NetworkEquipment::class;
    protected static string $itemtype_class = GlpiNetworkEquipment::class;
    protected static string $glpi_type_class = GlpiNetworkEquipmentType::class;
    protected static string $type_class = NetworkEquipmentType::class;
    protected static string $model_class = NetworkEquipmentModel::class;

    /**
     * The delta for comparison of computed emission with expected value,
     * as == for float must not be used because of float representation.
     */
    const EPSILON = 0.001;

    public function getEnergyPerDayProvider(): \Generator
    {
        $model = $this->getItem(static::$model_class, ['power_consumption' => 20]);
        $item = $this->getItem(static::$itemtype_class, [
            static::$model_class::getForeignKeyField() => $model->getID(),
        ]);
        $engine = new static::$engine_class($item->getID());
        yield 'item' => [
            $engine,
            new DateTime('2024-01-01 00:00:00'),
            20 * 24 / 1000,
        ];
    }

    public function testGetEnergyPerDay()
    {
        foreach ($this->getEnergyPerDayProvider() as $data) {
            list ($engine, $date, $expected_energy) = $data;
            $output = $engine->getEnergyPerDay($date);
            $this->assertEquals($expected_energy, $output->getValue());
        }
    }

    public function getCarbonEmissionPerDateProvider(): \Generator
    {
        $country = $this->getUniqueString();
        $thursday = DateTime::createFromFormat('Y-m-d H:i:s', '1999-12-02 12:00:00');
        $this->createCarbonIntensityData($country, 'Test source', $thursday, 1);
        $zone = new CarbonIntensityZone();
        $zone->getFromDBByCrit(['name' => $country]);

        $model = $this->getItem(static::$model_class, ['power_consumption' => 80]);
        $item = $this->getItem(static::$itemtype_class, [
            $model::getForeignKeyField() => $model->getID(),
        ]);
        $engine = new static::$engine_class($item->getID());
        yield 'Item' => [
            $engine,
            $thursday,
            $zone,
            80 * 24 * 1 / 1000,
        ];
    }
}
