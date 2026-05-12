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

namespace GlpiPlugin\Carbon\DataSource\CarbonIntensity;

use GlpiPlugin\Carbon\DataSource\CarbonIntensity\ElectricityMaps\Client as ElectricityMapsClient;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ClientFactory::class)]
class ClientFactoryTest extends DbTestCase
{
    public function testGetClientTypes()
    {
        $result = ClientFactory::getClientTypes();
        $expected = [
            'ElectricityMaps' => 'GlpiPlugin\\Carbon\\DataSource\\CarbonIntensity\\ElectricityMaps\\Client',
            'Rte' => 'GlpiPlugin\\Carbon\\DataSource\\CarbonIntensity\\Rte\\Client',
        ];
        $this->assertSame($expected, $result);
    }

    public function testGetConfigTypes()
    {
        $result = ClientFactory::getConfigTypes();
        $expected = [
            'ElectricityMaps' => 'GlpiPlugin\\Carbon\\DataSource\\CarbonIntensity\\ElectricityMaps\\Config',
            'Rte' => 'GlpiPlugin\\Carbon\\DataSource\\CarbonIntensity\\Rte\\Config',
        ];
        $this->assertSame($expected, $result);
    }

    public function testCreateElectricityMap()
    {
        $client = ClientFactory::create('ElectricityMaps');
        $this->assertInstanceOf(ElectricityMapsClient::class, $client);
    }

    public function testGetSecuredConfigs()
    {
        // This tests takes into account the available data sources
        $result = ClientFactory::getSecuredConfigs();
        $expected = [
            'electricitymap_api_key',
        ];
        $this->assertSame($expected, $result);
    }
}
