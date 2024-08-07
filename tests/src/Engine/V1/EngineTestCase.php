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

 namespace GlpiPlugin\Carbon\Tests\Engine\V1;

use Computer_Item;
use DateTime;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\Engine\V1\EngineInterface;

class EngineTestCase extends DbTestCase
{
    protected static string $engine_class = '';
    protected static string $itemtype_class = '';
    protected static string $glpi_type_class = '';
    protected static string $type_class = '';
    protected static string $model_class = '';

    /**
     * The delta for comparison of computed emission with expected value,
     * as == for float must not be used because of float representation.
     */
    const EPSILON = 0.001;

    public function getPowerProvider(): \Generator
    {
        $item = $this->getItem(static::$itemtype_class);
        $engine = new static::$engine_class($item->getID());
        yield 'item without model nor type' => [
            $engine,
            0
        ];

        $model = $this->getItem(static::$model_class);
        $glpi_type = $this->getItem(static::$glpi_type_class);
        $type = $this->getItem(static::$type_class, [
            static::$glpi_type_class::getForeignKeyField() => $glpi_type->getID(),
        ]);
        $item = $this->getItem(static::$itemtype_class, [
            static::$glpi_type_class::getForeignKeyField() => $glpi_type->getID(),
            static::$model_class::getForeignKeyField() => $model->getID(),
        ]);
        $engine = new static::$engine_class($item->getID());
        yield 'item with empty power data' => [
            $engine,
            0
        ];

        $model = $this->getItem(static::$model_class, ['power_consumption' => 20]);
        $item = $this->getItem(static::$itemtype_class, [
            static::$glpi_type_class::getForeignKeyField() => $glpi_type->getID(),
            static::$model_class::getForeignKeyField() => $model->getID(),
        ]);
        $engine = new static::$engine_class($item->getID());
        yield 'item with power data in model' => [
            $engine,
            20
        ];

        $model = $this->getItem(static::$model_class);
        $glpi_type = $this->getItem(static::$glpi_type_class);
        $type = $this->getItem(static::$type_class, [
            static::$glpi_type_class::getForeignKeyField() => $glpi_type->getID(),
            'power_consumption' => 40
        ]);
        $item = $this->getItem(static::$itemtype_class, [
            static::$glpi_type_class::getForeignKeyField() => $glpi_type->getID(),
            static::$model_class::getForeignKeyField() => $model->getID(),
        ]);
        $engine = new static::$engine_class($item->getID());
        yield 'item with power data in type' => [
            $engine,
            40
        ];

        $model = $this->getItem(static::$model_class, ['power_consumption' => 20]);
        $item = $this->getItem(static::$itemtype_class, [
            static::$glpi_type_class::getForeignKeyField() => $glpi_type->getID(),
            static::$model_class::getForeignKeyField() => $model->getID(),
        ]);
        $engine = new static::$engine_class($item->getID());
        yield 'item with power data in model and type' => [
            $engine,
            20
        ];
    }

    /**
     * @dataProvider getPowerProvider
     */
    public function testGetPower(EngineInterface $engine, int $expected_power)
    {
        $actual_power = $engine->getPower();
        $this->assertEquals($expected_power, $actual_power);
    }

    /**
     * @dataProvider getEnergyPerDayProvider
     */
    public function testGetEnergyPerDay(EngineInterface $engine, DateTime $date, float $expected_energy)
    {
        $output = $engine->getEnergyPerDay($date);
        $this->assertEquals($expected_energy, $output);
    }

    /**
     *
     * @dataProvider getCarbonEmissionPerDateProvider
     */
    public function testGetCarbonEmissionPerDay(EngineInterface $engine, DateTime $day, float $expected_emission)
    {
        $emission = $engine->getCarbonEmissionPerDay($day);
        $this->assertEqualsWithDelta($expected_emission, $emission, self::EPSILON);
    }
}
