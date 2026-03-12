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

namespace GlpiPlugin\Carbon\Impact\Usage\Boavizta\Tests;

use Computer as GlpiComputer;
use ComputerModel as GlpiComputerModel;
use ComputerType as GlpiComputerType;
use GlpiPlugin\Carbon\Impact\Usage\Boavizta\Computer as BoaviztaComputer;
use GlpiPlugin\Carbon\Tests\Impact\Usage\Boavizta\AbstractAsset;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BoaviztaComputer::class)]
class ComputerTest extends AbstractAsset
{
    protected static string $instance_type = BoaviztaComputer::class;
    protected static string $itemtype = GlpiComputer::class;
    protected static string $itemtype_type = GlpiComputerType::class;
    protected static string $itemtype_model = GlpiComputerModel::class;

    public function testGetEvaluableQuery()
    {
        global $DB;
        parent::testGetEvaluableQuery();

        // Test an asset without a category
        [$asset, $glpi_location, $location, $glpi_asset_type, $asset_type, $infocom, $usage_info] = $this->getEvaluableAsset();
        $asset_type->update(['category' => 0] + $asset_type->fields);
        $instance = new static::$instance_type($asset);
        $request = $instance->getEvaluableQuery(
            get_class($asset),
            [
                $asset::getTableField('id') => $asset->getID(),
            ]
        );
        $iterator = $DB->request($request);
        $this->assertEquals(0, $iterator->count());
    }
}
