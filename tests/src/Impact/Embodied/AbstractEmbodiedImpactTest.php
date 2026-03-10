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

namespace GlpiPlugin\Carbon\Tests\Impact\Embodied;

use DBmysql;
use GlpiPlugin\Carbon\EmbodiedImpact;
use GlpiPlugin\Carbon\Impact\Embodied\AbstractEmbodiedImpact;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractEmbodiedImpact::class)]
class AbstractEmbodiedImpactTest extends DbTestCase
{
    protected static string $itemtype = '';
    protected static string $itemtype_type = '';
    protected static string $itemtype_model = '';

    public function testGetItemsToEvaluate()
    {
        if (static::$itemtype === '' || static::$itemtype_type === '' || static::$itemtype_model === '') {
            // Ensure that the inherited test class is properly implemented for this test
            $this->fail('Itemtype properties not set in ' . static::class);
        }

        // Test the asset is evaluable when no embodied impact is in the DB
        $glpi_asset_type = $this->createItem(static::$itemtype_type);
        $asset_type = $this->createItem('GlpiPlugin\\Carbon\\' . static::$itemtype_type, [
            getForeignKeyFieldForItemType(static::$itemtype_type) => $glpi_asset_type->getID(),
        ]);
        $asset = $this->createItem(static::$itemtype, [
            getForeignKeyFieldForItemType(static::$itemtype_type) => $glpi_asset_type->getID(),
        ]);
        $iterator = AbstractEmbodiedImpact::getItemsToEvaluate(static::$itemtype, [
            $asset->getTableField('id') => $asset->getID(),
        ]);
        $this->assertEquals(1, $iterator->count());

        // Test the asset is no longer evaluable when there is embodied impact in the DB
        $glpi_asset_type = $this->createItem(static::$itemtype_type);
        $asset_type = $this->createItem('GlpiPlugin\\Carbon\\' . static::$itemtype_type, [
            getForeignKeyFieldForItemType(static::$itemtype_type) => $glpi_asset_type->getID(),
        ]);
        $asset = $this->createItem(static::$itemtype, [
            getForeignKeyFieldForItemType(static::$itemtype_type) => $glpi_asset_type->getID(),
        ]);
        $embodied_impact = $this->createItem(EmbodiedImpact::class, [
            'itemtype' => $asset->getType(),
            'items_id' => $asset->getID(),
            'recalculate' => 0,
        ]);
        $iterator = AbstractEmbodiedImpact::getItemsToEvaluate(static::$itemtype, [
            $asset::getTableField('id') => $asset->getID(),
        ]);
        $this->assertEquals(0, $iterator->count());

        // Test the asset is evaluable when there is embodied impact in the DB but recamculate is set
        $glpi_asset_type = $this->createItem(static::$itemtype_type);
        $asset_type = $this->createItem('GlpiPlugin\\Carbon\\' . static::$itemtype_type, [
            getForeignKeyFieldForItemType(static::$itemtype_type) => $glpi_asset_type->getID(),
        ]);
        $asset = $this->createItem(static::$itemtype, [
            getForeignKeyFieldForItemType(static::$itemtype_type) => $glpi_asset_type->getID(),
        ]);
        $embodied_impact = $this->createItem(EmbodiedImpact::class, [
            'itemtype' => $asset->getType(),
            'items_id' => $asset->getID(),
            'recalculate' => 1,
        ]);
        $iterator = AbstractEmbodiedImpact::getItemsToEvaluate(static::$itemtype, [
            $asset::getTableField('id') => $asset->getID(),
        ]);
        $this->assertEquals(1, $iterator->count());
    }

    public function testGetEvaluableQuery()
    {
        /** @var DBmysql $DB */
        global $DB;

        // Test an asset is evaluable
        $itemtype_table = getTableForItemType(static::$itemtype);
        $itemtype_type_fk = getForeignKeyFieldForItemType(static::$itemtype_type);
        $glpi_asset_type = $this->createItem(static::$itemtype_type);
        $asset_type = $this->createItem('GlpiPlugin\\Carbon\\' . static::$itemtype_type, [
            $itemtype_type_fk => $glpi_asset_type->getID(),
        ]);
        $asset = $this->createItem(static::$itemtype, [
            $itemtype_type_fk => $glpi_asset_type->getID(),
        ]);

        $request = AbstractEmbodiedImpact::getEvaluableQuery(
            static::$itemtype,
            [
                $itemtype_table . '.id' => $asset->getID(),
            ]
        );
        $this->assertArrayHasKey('SELECT', $request);
        $this->assertArrayHasKey('FROM', $request);
        $this->assertArrayHasKey('LEFT JOIN', $request);
        $this->assertArrayHasKey('WHERE', $request);
        $iterator = $DB->request($request);
        $this->assertEquals(1, $iterator->count());

        // Test an asset is not evaluable
        $glpi_asset_type = $this->createItem(static::$itemtype_type);
        $asset_type = $this->createItem('GlpiPlugin\\Carbon\\' . static::$itemtype_type, [
            $itemtype_type_fk => $glpi_asset_type->getID(),
            'is_ignore' => 1,
        ]);
        $asset = $this->createItem(static::$itemtype, [
            $itemtype_type_fk => $glpi_asset_type->getID(),
        ]);

        $request = AbstractEmbodiedImpact::getEvaluableQuery(
            static::$itemtype,
            [
                $itemtype_table . '.id' => $asset->getID(),
            ]
        );
        $this->assertArrayHasKey('SELECT', $request);
        $this->assertArrayHasKey('FROM', $request);
        $this->assertArrayHasKey('LEFT JOIN', $request);
        $this->assertArrayHasKey('WHERE', $request);
        $iterator = $DB->request($request);
        $this->assertEquals(0, $iterator->count());
    }

    public function testResetForItem()
    {
        $asset = $this->createItem(static::$itemtype);
        $instance = $this->createItem(EmbodiedImpact::class, [
            'itemtype' => get_class($asset),
            'items_id' => $asset->getID(),
        ]);

        $result = AbstractEmbodiedImpact::resetForItem($asset);
        $this->assertTrue($result);
        $result = EmbodiedImpact::getById($instance->getID());
        $this->assertFalse($result);
    }
}
