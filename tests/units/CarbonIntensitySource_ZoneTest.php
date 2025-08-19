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

use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\CarbonIntensitySource_Zone;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use GlpiPlugin\Carbon\Zone;

class CarbonIntensitySource_ZoneTest extends DbTestCase
{
    public function testShowForSource()
    {
        $source = $this->getItem(CarbonIntensitySource::class, [
            'name' => 'foo'
        ]);

        $zone = $this->getItem(Zone::class, [
            'name' => 'bar'
        ]);

        $instance = $this->getItem(CarbonIntensitySource_Zone::class, [
            $source::getForeignKeyField() => $source->getID(),
            $zone::getForeignKeyField() => $zone->getID(),
        ]);

        $output = '';
        ob_start(function ($buffer) use ($output) {
            $output .= $buffer;
        });
        $result = $instance->showForSource($source);
        $output = ob_get_clean();
        $this->assertEquals('', $output);

        $this->login('glpi', 'glpi');
        $output = '';
        ob_start(function ($buffer) use ($output) {
            $output .= $buffer;
        });
        $result = $instance->showForSource($source);
        $output = ob_get_clean();
        $this->assertNotEmpty($output);
    }

    public function testShowForZone()
    {
        $source = $this->getItem(CarbonIntensitySource::class, [
            'name' => 'foo'
        ]);

        $zone = $this->getItem(Zone::class, [
            'name' => 'bar'
        ]);

        $instance = $this->getItem(CarbonIntensitySource_Zone::class, [
            $source::getForeignKeyField() => $source->getID(),
            $zone::getForeignKeyField() => $zone->getID(),
        ]);

        $output = '';
        ob_start(function ($buffer) use ($output) {
            $output .= $buffer;
        });
        $result = $instance->showForZone($zone);
        $output = ob_get_clean();
        $this->assertEquals('', $output);

        $this->login('glpi', 'glpi');
        $output = '';
        ob_start(function ($buffer) use ($output) {
            $output .= $buffer;
        });
        $result = $instance->showForZone($zone);
        $output = ob_get_clean();
        $this->assertNotEmpty($output);
    }
}
