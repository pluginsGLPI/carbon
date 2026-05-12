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

namespace GlpiPlugin\Carbon\Impact\Embodied\Tests;

use Computer as GlpiComputer;
use ComputerModel as GlpiComputerModel;
use Config as GlpiConfig;
use GlpiPlugin\Carbon\ComputerModel;
use GlpiPlugin\Carbon\DataTracking\AbstractTracked;
use GlpiPlugin\Carbon\Impact\Embodied\Boavizta\Computer as BoaviztaComputer;
use GlpiPlugin\Carbon\Impact\Embodied\Boavizta\Monitor as BoaviztaMonitor;
use GlpiPlugin\Carbon\Impact\Embodied\Engine;
use GlpiPlugin\Carbon\Impact\Embodied\Internal\Computer as InternalComputer;
use GlpiPlugin\Carbon\Impact\Embodied\Internal\Monitor as InternalMonitor;
use GlpiPlugin\Carbon\Impact\Embodied\Internal\NetworkEquipment as InternalNetworkEquipment;
use GlpiPlugin\Carbon\MonitorModel;
use GlpiPlugin\Carbon\NetworkEquipmentModel;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use Monitor as GlpiMonitor;
use MonitorModel as GlpiMonitorModel;
use NetworkEquipment as GlpiNetworkEquipment;
use NetworkEquipmentModel as GlpiNetworkEquipmentModel;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Engine::class)]
class EngineTest extends DbTestCase
{
    public function testGetEngineFromItemtypeForBoavizta()
    {
        GlpiConfig::setConfigurationValues('plugin:carbon', [
            'boaviztapi_base_url' => 'http://localhost:5000',
        ]);

        $item = $this->createItem(GlpiComputer::class);
        $result = Engine::getEngineFromItemtype($item);
        $this->assertTrue($result instanceof BoaviztaComputer);

        $item = $this->createItem(GlpiMonitor::class);
        $result = Engine::getEngineFromItemtype($item);
        $this->assertTrue($result instanceof BoaviztaMonitor);

        // This case returns internal embodied impact engine, as Boavizta does not provide data
        $item = $this->createItem(GlpiNetworkEquipment::class);
        $result = Engine::getEngineFromItemtype($item);
        $this->assertTrue($result instanceof InternalNetworkEquipment);
    }

    public function testGetEngineFromItemtypeForInternal()
    {
        $glpi_asset_model = $this->createItem(GlpiComputerModel::class);
        $asset_model = $this->createItem(ComputerModel::class, [
            'computermodels_id' => $glpi_asset_model->getID(),
        ]);
        $asset = $this->createItem(GlpiComputer::class, [
            'computermodels_id' => $glpi_asset_model->getID(),
            'gwp'               => 0,
            'gwp_quality'       => AbstractTracked::DATA_QUALITY_ESTIMATED,
        ]);
        $result = Engine::getEngineFromItemtype($asset);
        $this->assertTrue($result instanceof InternalComputer);

        $glpi_asset_model = $this->createItem(GlpiMonitorModel::class);
        $asset_model = $this->createItem(MonitorModel::class, [
            'monitormodels_id' => $glpi_asset_model->getID(),
        ]);
        $asset = $this->createItem(GlpiMonitor::class, [
            'monitormodels_id' => $glpi_asset_model->getID(),
            'gwp'               => 0,
            'gwp_quality'       => AbstractTracked::DATA_QUALITY_ESTIMATED,
        ]);
        $result = Engine::getEngineFromItemtype($asset);
        $this->assertTrue($result instanceof InternalMonitor);

        $glpi_asset_model = $this->createItem(GlpiNetworkEquipmentModel::class);
        $asset_model = $this->createItem(NetworkEquipmentModel::class, [
            'networkequipmentmodels_id' => $glpi_asset_model->getID(),
        ]);
        $asset = $this->createItem(GlpiNetworkEquipment::class, [
            'networkequipmentmodels_id' => $glpi_asset_model->getID(),
            'gwp'               => 0,
            'gwp_quality'       => AbstractTracked::DATA_QUALITY_ESTIMATED,
        ]);
        $result = Engine::getEngineFromItemtype($asset);
        $this->assertTrue($result instanceof InternalNetworkEquipment);
    }
}
