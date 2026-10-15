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
use GlpiPlugin\Carbon\AbstractImpact;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(AbstractImpact::class)]
abstract class AbstractImpactTest extends DbTestCase
{
    /**
     * Data provider of the itemtype to test
     *
     * @return array[][class-string]
     */
    abstract public static function typeProvider(): array;

    #[DataProvider('typeProvider')]
    public function testCanEdit(string $tested_type)
    {
        $asset = $this->createItem(Computer::class);
        $instance = $this->createItem($tested_type, [
            'itemtype' => Computer::class,
            'items_id' => $asset->getID(),
        ]);
        $result = $instance->canEdit($instance->getID());
        $this->assertFalse($result);
    }
}
