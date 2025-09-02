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

namespace GlpiPlugin\Carbon\Engine\V1\Tests;

use DateTime;
use GlpiPlugin\Carbon\Tests\Engine\V1\EngineTestCase;
use GlpiPlugin\Carbon\Engine\V1\NetworkEquipment;
use GlpiPlugin\Carbon\NetworkEquipmentType;
use GlpiPlugin\Carbon\Zone;
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
        $model = $this->createItem(static::$model_class, ['power_consumption' => 20]);
        $item = $this->createItem(static::$itemtype_class, [
            static::$model_class::getForeignKeyField() => $model->getID(),
        ]);
        $engine = new static::$engine_class($item);
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
        $zone = new Zone();
        $zone->getFromDBByCrit(['name' => $country]);

        $model = $this->createItem(static::$model_class, ['power_consumption' => 80]);
        $item = $this->createItem(static::$itemtype_class, [
            $model::getForeignKeyField() => $model->getID(),
        ]);
        $engine = new static::$engine_class($item);
        yield 'Item' => [
            $engine,
            $thursday,
            $zone,
            80 * 24 * 1 / 1000,
        ];
    }

    public function testGetCarbonEmissionPerDay()
    {
        $zone = new Zone();
        $zone->getEmpty();

        $model = $this->createItem(static::$model_class, ['power_consumption' => 400]);
        $item = $this->createItem(static::$itemtype_class, [
            static::$model_class::getForeignKeyField() => $model->getID(),
        ]);

        $engine = new static::$engine_class($item);
        $output = $engine->getCarbonEmissionPerDay(
            new DateTime('2024-02-01 00:00:00'),
            $zone
        );

        // Expects to use World carbon intensity as fallback
        $this->assertEqualsWithDelta(
            4540.867,
            $output->getValue(),
            static::EPSILON
        );
    }
}
