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

namespace GlpiPlugin\Carbon\Tests;

use GlpiPlugin\Carbon\Config;
use Config as GlpiConfig;
use Geocoder\Geocoder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass('GlpiPlugin\Carbon\Config')]
class ConfigTest extends DbTestCase
{
    /**
     * #CoversMethod GlpiPlugin\Carbon\Config::getEmbodiedImpactEngine
     */
    public function testGetEmbodiedImpactEngine()
    {
        $configuration_key = 'impact_engines';

        // test when engine is not set
        $output = Config::getEmbodiedImpactEngine();
        $this->assertEquals('GlpiPlugin\\Carbon\\Impact\\Embodied\\Boavizta', $output);

        // test a engine is set
        GlpiConfig::setConfigurationValues('plugin:carbon', [$configuration_key => 'foo']);
        $output = Config::getEmbodiedImpactEngine();
        $this->assertEquals('GlpiPlugin\\Carbon\\Impact\\Embodied\\foo', $output);

        // test change of the engine
        GlpiConfig::setConfigurationValues('plugin:carbon', [$configuration_key => 'Boavizta']);
        $output = Config::getEmbodiedImpactEngine();
        $this->assertEquals('GlpiPlugin\\Carbon\\Impact\\Embodied\\Boavizta', $output);
    }

    /**
     * #CoversMethod GlpiPlugin\Carbon\Config::getUsageImpactEngine
     */
    public function testGetUsageImpactEngine()
    {
        $configuration_key = 'impact_engines';

        // test when engine is not set
        $output = Config::getUsageImpactEngine();
        $this->assertEquals('GlpiPlugin\\Carbon\\Impact\\Usage\\Boavizta', $output);

        // test a engine is set
        GlpiConfig::setConfigurationValues('plugin:carbon', [$configuration_key => 'foo']);
        $output = Config::getUsageImpactEngine();
        $this->assertEquals('GlpiPlugin\\Carbon\\Impact\\Usage\\foo', $output);

        // test change of the engine
        GlpiConfig::setConfigurationValues('plugin:carbon', [$configuration_key => 'Boavizta']);
        $output = Config::getUsageImpactEngine();
        $this->assertEquals('GlpiPlugin\\Carbon\\Impact\\Usage\\Boavizta', $output);
    }

    public static function configUpdateProvider()
    {
        yield [
            [
                'electricitymap_api_key' => '',
            ], [
            ]
        ];

        yield [
            [
                'electricitymap_api_key' => 'foo',
            ], [
                'electricitymap_api_key' => 'foo',
            ]
        ];

        yield [
            [
                'boaviztapi_base_url' => '',
            ], [
                'boaviztapi_base_url' => '',
            ]
        ];

        // TODO: requires code change to test boaviztapi_base_url with a not-empty value
        // this triggers creation of an object then HTTP request, should be avoided in tests context
    }

    /**
     * #dataProvider configUpdateProvider
     * #CoversMethod GlpiPlugin\Carbon\Config::configUpdate
     *
     * @param array $input
     * @param array $expected
     * @return void
     */
    #[dataProvider('configUpdateProvider')]
    public function testConfigUpdate(array $input, array $expected)
    {
        $result = Config::configUpdate($input);
        $this->assertEquals($expected, $result);
    }

    /**
     * #CoversMethod GlpiPlugin\Carbon\Config::isDemoMode
     *
     * @return void
     */
    public function testIsDemoMode()
    {
        Config::setConfigurationValues('plugin:carbon', ['demo' => 0]);
        $result = Config::isDemoMode();
        $this->assertFalse($result);

        Config::setConfigurationValues('plugin:carbon', ['demo' => 1]);
        $result = Config::isDemoMode();
        $this->assertTrue($result);
    }

    public function testExitDemoMode()
    {
        $config = new GlpiConfig();
        $config->getFromDBByCrit([
            'context' => 'plugin:carbon',
            'name'    => 'demo',
        ]);
        $this->assertFalse($config->isNewItem());

        Config::exitDemoMode();
        $config = new GlpiConfig();
        $config->getFromDBByCrit([
            'context' => 'plugin:carbon',
            'name'    => 'demo',
        ]);
        $this->assertTrue($config->isNewItem());
    }

    /**
     * #CoversMethod GlpiPlugin\Carbon\Config::getGeocoder
     *
     * @return void
     */
    public function testGetGeocoder()
    {
        $result = Config::getGeocoder();
        $this->assertInstanceOf(Geocoder::class, $result);
    }
}
