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

namespace GlpiPlugin\Carbon\Tests\Engine\V1;

use GlpiPlugin\Carbon\Tests\DbTestCase;

abstract class EngineTestCase extends DbTestCase
{
    protected static string $engine_class = '';
    protected static string $itemtype_class = '';
    protected static string $glpi_type_class = '';
    protected static string $type_class = '';
    protected static string $model_class = '';

    abstract public function getEnergyPerDayProvider(): \Generator;

    abstract public function getCarbonEmissionPerDateProvider(): \Generator;

    /**
     * The delta for comparison of computed emission with expected value,
     * as == for float must not be used because of float representation.
     */
    const EPSILON = 0.001;

    public function getPowerProvider(): \Generator
    {
        $item = $this->getItem(static::$itemtype_class);
        $engine = new static::$engine_class($item);
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
        $engine = new static::$engine_class($item);
        yield 'item with empty power data' => [
            $engine,
            0
        ];

        $model = $this->getItem(static::$model_class, ['power_consumption' => 20]);
        $item = $this->getItem(static::$itemtype_class, [
            static::$glpi_type_class::getForeignKeyField() => $glpi_type->getID(),
            static::$model_class::getForeignKeyField() => $model->getID(),
        ]);
        $engine = new static::$engine_class($item);
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
        $engine = new static::$engine_class($item);
        yield 'item with power data in type' => [
            $engine,
            40
        ];

        $model = $this->getItem(static::$model_class, ['power_consumption' => 20]);
        $item = $this->getItem(static::$itemtype_class, [
            static::$glpi_type_class::getForeignKeyField() => $glpi_type->getID(),
            static::$model_class::getForeignKeyField() => $model->getID(),
        ]);
        $engine = new static::$engine_class($item);
        yield 'item with power data in model and type' => [
            $engine,
            20
        ];
    }

    public function testGetPower()
    {
        foreach ($this->getPowerProvider() as $data) {
            list ($engine, $expected_power) = $data;
            $actual_power = $engine->getPower();
            $this->assertEquals($expected_power, $actual_power->getValue());
        }
    }

    public function testGetEnergyPerDay()
    {
        foreach ($this->getEnergyPerDayProvider() as $data) {
            list($engine, $date, $expected_energy) = $data;
            $output = $engine->getEnergyPerDay($date);
            $this->assertEquals($expected_energy, $output->getValue());
        }
    }

    public function testGetCarbonEmissionPerDay()
    {
        foreach ($this->getCarbonEmissionPerDateProvider() as $data) {
            list($engine, $day, $zone, $expected_emission) = $data;
            $emission = $engine->getCarbonEmissionPerDay($day, $zone);
            $this->assertEqualsWithDelta($expected_emission, $emission->getValue(), self::EPSILON);
        }
    }
}
