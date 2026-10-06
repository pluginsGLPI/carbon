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

namespace GlpiPlugin\Carbon\DataSource;

use GlpiPlugin\Carbon\Tests\CommonTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

require_once dirname(__DIR__, 2) . '/fixtures/FakeDataSources.php';

#[CoversClass(CronTaskProvider::class)]
class CronTaskProviderTest extends CommonTestCase
{
    public function test_getCronTaskTypes_returns_()
    {
        $stub_carbon_intensity_dir = $this->createStub(\DirectoryIterator::class);
        $stub_carbon_intensity_dir->method('getBasename')->willReturn('CarbonIntensity');
        $foo_item = $this->createStub(\DirectoryIterator::class);
        $foo_item->method('isDot')->willReturn(false);
        $foo_item->method('isDir')->willReturn(true);
        $foo_item->method('getBasename')->willReturn('Foo');
        $foo_item->method('getPathName')->willReturn('/path/to/CarbonIntensity/Foo');
        $internal_iterator = new \ArrayIterator([
            $foo_item,
        ]);
        $stub_carbon_intensity_dir->method('rewind')->willReturnCallback(fn() => $internal_iterator->rewind());
        $stub_carbon_intensity_dir->method('valid')->willReturnCallback(fn() => $internal_iterator->valid());
        $stub_carbon_intensity_dir->method('current')->willReturnCallback(fn() => $internal_iterator->current());
        $stub_carbon_intensity_dir->method('key')->willReturnCallback(fn() => $internal_iterator->key());
        $stub_carbon_intensity_dir->method('next')->willReturnCallback(fn() => $internal_iterator->next());

        $stub_lca_dir = $this->createStub(\DirectoryIterator::class);
        $stub_lca_dir->method('getBasename')->willReturn('Lca');
        $bar_item = $this->createStub(\DirectoryIterator::class);
        $bar_item->method('isDot')->willReturn(false);
        $bar_item->method('isDir')->willReturn(true);
        $bar_item->method('getBasename')->willReturn('Bar');
        $bar_item->method('getPathName')->willReturn('/path/to/Lca/Bar');
        $baz_item = $this->createStub(\DirectoryIterator::class);
        $baz_item->method('isDot')->willReturn(false);
        $baz_item->method('isDir')->willReturn(true);
        $baz_item->method('getBasename')->willReturn('Baz');
        $baz_item->method('getPathName')->willReturn('/path/to/Lca/Baz');
        $internal_iterator_lca = new \ArrayIterator([
            $bar_item,
            $baz_item,
        ]);
        $stub_lca_dir->method('rewind')->willReturnCallback(fn() => $internal_iterator_lca->rewind());
        $stub_lca_dir->method('valid')->willReturnCallback(fn() => $internal_iterator_lca->valid());
        $stub_lca_dir->method('current')->willReturnCallback(fn() => $internal_iterator_lca->current());
        $stub_lca_dir->method('key')->willReturnCallback(fn() => $internal_iterator_lca->key());
        $stub_lca_dir->method('next')->willReturnCallback(fn() => $internal_iterator_lca->next());

        $dirs = [
            $stub_carbon_intensity_dir,
            $stub_lca_dir,
        ];

        $result = CronTaskProvider::getCronTaskTypes($dirs);
        $expected = [
            'Foo'    => 'GlpiPlugin\\Carbon\\DataSource\\CarbonIntensity\\Foo\\CronTask',
            'Bar'    => 'GlpiPlugin\\Carbon\\DataSource\\Lca\\Bar\\CronTask',
            'Baz' => 'GlpiPlugin\\Carbon\\DataSource\\Lca\\Baz\\CronTask',
        ];
        $this->assertSame($expected, $result);
    }
}
