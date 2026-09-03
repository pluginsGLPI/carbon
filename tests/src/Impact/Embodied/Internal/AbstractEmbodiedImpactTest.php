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

namespace GlpiPlugin\Carbon\Tests\Impact\Embodied\Internal;

use GlpiPlugin\Carbon\DataTracking\AbstractTracked;
use GlpiPlugin\Carbon\DataTracking\TrackedFloat;
use GlpiPlugin\Carbon\EmbodiedImpact;
use GlpiPlugin\Carbon\Impact\Embodied\AbstractEmbodiedImpact;
use GlpiPlugin\Carbon\Impact\Embodied\Boavizta\AbstractAsset;
use GlpiPlugin\Carbon\Impact\Embodied\Internal\Computer;
use GlpiPlugin\Carbon\Tests\Impact\Embodied\AbstractCommonEmbodiedImpactTest;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractEmbodiedImpact::class)]
class AbstractEmbodiedImpactTest extends AbstractCommonEmbodiedImpactTest
{
    protected static string $itemtype = '';
    protected static string $itemtype_type = '';
    protected static string $itemtype_model = '';

    public function test_doEvaluation_uses_user_input_impacts_when_user_input_impacts_are_set()
    {
        $glpi_asset_type = $this->createItem(static::$itemtype_type);
        $glpi_asset_model = $this->createItem(static::$itemtype_model);
        $asset_type = $this->createItem('GlpiPlugin\\Carbon\\' . static::$itemtype_type, [
            getForeignKeyFieldForItemType(static::$itemtype_type) => $glpi_asset_type->getID(),
        ]);
        $asset_model = $this->createItem('GlpiPlugin\\Carbon\\' . static::$itemtype_model, [
            getForeignKeyFieldForItemtype(static::$itemtype_model) => $glpi_asset_model->getID(),
            'gwp' => 1024,
            'gwp_quality' => AbstractTracked::DATA_QUALITY_MANUAL,
        ]);
        $asset = $this->createItem(static::$itemtype, [
            getForeignKeyFieldForItemType(static::$itemtype_type) => $glpi_asset_type->getID(),
            getForeignKeyFieldForItemType(static::$itemtype_model) => $glpi_asset_model->getID(),
        ]);

        $external_engine = $this->createStub(AbstractAsset::class);
        $external_engine->method('doEvaluation')->willReturn([
            // 0 is the ID of GWP, see impact types
            0 => new TrackedFloat(2048, null, AbstractTracked::DATA_QUALITY_ESTIMATED),
        ]);
        $instance = new Computer($asset, $external_engine);
        $instance->evaluateItem();

        $embodied_impact = $this->getItem(EmbodiedImpact::class, [
            'itemtype' => get_class($asset),
            'items_id' => $asset->getID(),
        ]);

        $this->assertEquals(1024, $embodied_impact->fields['gwp']);
    }
}
