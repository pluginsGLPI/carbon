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

use Computer;
use GlpiPlugin\Carbon\EmbodiedImpact;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(EmbodiedImpact::class)]
class EmbodiedImpactTest extends DbTestCase
{
    public function testCanEdit()
    {
        $asset = $this->createItem(Computer::class);
        $instance = $this->createItem(EmbodiedImpact::class, [
            'itemtype' => Computer::class,
            'items_id' => $asset->getID(),
        ]);
        $result = $instance->canEdit($instance->getID());
        $this->assertFalse($result);
    }

    public function testNotCalculatedAssetMustBeCalculated()
    {
        $itemtypes = PLUGIN_CARBON_TYPES; // Get supported itemtypes
        foreach ($itemtypes as $itemtype) {
            // Setup test case
            $not_calculated_asset_1 = $this->createItem($itemtype);
            $not_calculated_asset_2 = $this->createItem($itemtype);
            $calculated_asset = $this->createItem($itemtype);
            $calculated_impact = $this->createItem(EmbodiedImpact::class, [
                'itemtype' => $itemtype,
                'items_id' => $calculated_asset->getID(),
            ]);

            // Check that we get all not calculated assets for the given itemtype
            $iterator = EmbodiedImpact::getAssetsToCalculate($itemtype);
            $this->assertEquals(2, $iterator->count());

            // Check that we can filter not calculated assets by ID
            $iterator = EmbodiedImpact::getAssetsToCalculate($itemtype, [
                'WHERE' => [
                    'NOT' => [$itemtype::getTableField('id') => $not_calculated_asset_2->getID()],
                ],
            ]);
            $this->assertEquals(1, $iterator->count());
        }
    }
}
