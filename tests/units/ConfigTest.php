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

class ConfigTest extends DbTestCase
{
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
}
