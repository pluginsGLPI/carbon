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
use Monitor as GlpiMonitor;
use NetworkEquipment as GlpiNetworkEquipment;
use Config as GlpiConfig;
use GlpiPlugin\Carbon\DataSource\RestApiClient;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use GlpiPlugin\Carbon\Impact\Embodied\Boavizta\Computer;
use GlpiPlugin\Carbon\Impact\Embodied\Boavizta\Monitor;
use GlpiPlugin\Carbon\Impact\Embodied\Internal\NetworkEquipment;
use GlpiPlugin\Carbon\Impact\Embodied\Engine;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Engine::class)]
class EngineTest extends DbTestCase
{
    public function testGetEngineFromItemtypeForBoavizta()
    {
        GlpiConfig::setConfigurationValues('plugin:carbon', [
            'boaviztapi_base_url' => 'http://localhost:5000'
        ]);
        $version_response = [
            '1.3.11',
        ];
        $client_stub = $this->getMockBuilder(RestApiClient::class)
            ->getMock();
        $client_stub->method('request')->willReturn($version_response);

        $itemtype = GlpiComputer::class;
        $result = Engine::getEngineFromItemtype($itemtype, $client_stub);
        $this->assertTrue($result instanceof Computer);

        $itemtype = GlpiMonitor::class;
        $result = Engine::getEngineFromItemtype($itemtype, $client_stub);
        $this->assertTrue($result instanceof Monitor);

        // This case returns internal embodied impact engine, as Boavizta does not provide data
        $itemtype = GlpiNetworkEquipment::class;
        $result = Engine::getEngineFromItemtype($itemtype, $client_stub);
        $this->assertTrue($result instanceof NetworkEquipment);
    }
}
