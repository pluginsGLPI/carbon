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

use Computer as GlpiComputer;
use ComputerType as GlpiComputerType;
use DbUtils;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\EmbodiedImpact;
use GlpiPlugin\Carbon\Location;
use GlpiPlugin\Carbon\Source;
use GlpiPlugin\Carbon\Source_Zone;
use GlpiPlugin\Carbon\UsageInfo;
use GlpiPlugin\Carbon\Zone;
use Location as GlpiLocation;
use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
class HookTest extends DbTestCase
{
    public function testRelationsArePuegedOnAssetPurge()
    {
        $computer = $this->createItem(GlpiComputer::class);
        $carbon_emission = $this->createItem(CarbonEmission::class, [
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID()
        ]);
        $embodied_impact = $this->createItem(EmbodiedImpact::class, [
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID()
        ]);
        $usage_info = $this->createItem(UsageInfo::class, [
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID()
        ]);

        // Data must remain in DB after a delete
        $computer->delete($computer->fields);
        $count = (new DbUtils())->countElementsInTable($carbon_emission::getTable(), [
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID()
        ]);
        $this->assertEquals(1, $count);
        $count = (new DbUtils())->countElementsInTable($embodied_impact::getTable(), [
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID()
        ]);
        $this->assertEquals(1, $count);
        $count = (new DbUtils())->countElementsInTable($usage_info::getTable(), [
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID()
        ]);
        $this->assertEquals(1, $count);
        // Data must be dropped fron DB after a purge
        $computer->delete($computer->fields, true);
        $count = (new DbUtils())->countElementsInTable($carbon_emission::getTable(), [
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID()
        ]);
        $this->assertEquals(0, $count);
        $count = (new DbUtils())->countElementsInTable($embodied_impact::getTable(), [
            'itemtype' => $computer->getType(),
            'items_id' => $computer->getID()
        ]);
        $this->assertEquals(0, $count);
    }

    public function testCarbonAssetTypeIsPurgedOnAssetTypePurge()
    {
        $computer_type = $this->createItem(GlpiComputerType::class);
        $carbon_computer_type = $this->createItem(ComputerType::class, [
            'computertypes_id' => $computer_type->getID(),
        ]);

        $computer_type->delete($computer_type->fields, 1);
        $count = (new DbUtils())->countElementsInTable($carbon_computer_type::getTable(), [
            'computertypes_id' => $computer_type->getID()
        ]);
        $this->assertEquals(0, $count);
    }

    public function testAssetUpdateEnablesZoneDownload()
    {
        $zone = $this->createItem(Zone::class, ['name' => 'a zone']);
        $source = $this->createItem(Source::class, [
            'name' => 'a source',
            'is_carbon_intensity_source' => 1,
        ]);
        $fallback_source = $this->createItem(Source::class, [
            'name' => 'a fallback source',
            'is_carbon_intensity_source' => 1,
            'fallback_level' => 1
        ]);
        $source_zone = $this->createItem(Source_Zone::class, [
            $source::getForeignKeyField() => $source->getID(),
            $zone::getForeignKeyField() => $zone->getID(),
        ]);
        $fallback_source_zone = $this->createItem(Source_Zone::class, [
            $fallback_source::getForeignKeyField() => $fallback_source->getID(),
            $zone::getForeignKeyField() => $zone->getID(),
        ]);
        $glpi_location = $this->createItem(GlpiLocation::class, ['name' => 'a location']);
        $location = $this->createItem(Location::class, [
            'locations_id' => $glpi_location->getID(),
            $source_zone::getForeignKeyField() => $source_zone->getID(),
        ]);
        $computer = $this->createItem(GlpiComputer::class);
        $this->assertEquals(0, $source_zone->fields['is_download_enabled']);
        $computer->update([
            'id' => $computer->getID(),
            'locations_id' => $glpi_location->getID(),
        ]);
        $source_zone->getFromDB($source_zone->getID());
        $this->assertEquals(1, $source_zone->fields['is_download_enabled']);
    }
}
