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

// Redefine glob() in the namespace context to mock
function glob($pattern, $flags = 0)
{
    return [
        '/var/www/glpi/plugins/carbon/src/DataSource/Carbonintensity/Foo',
        '/var/www/glpi/plugins/carbon/src/DataSource/Lca/Bar',
        '/var/www/glpi/plugins/carbon/src/DataSource/Lca/Baz',
    ];
}

#[CoversClass(CronTaskProvider::class)]
class CronTaskProviderTest extends CommonTestCase
{
    public function test_getCronTaskTypes_returns_()
    {
        $result = CronTaskProvider::getCronTaskTypes();
        $expected = [
            'Foo'    => 'GlpiPlugin\\Carbon\\DataSource\\CarbonIntensity\\Foo\\CronTask',
            'Bar'    => 'GlpiPlugin\\Carbon\\DataSource\\Lca\\Bar\\CronTask',
            'Baz' => 'GlpiPlugin\\Carbon\\DataSource\\Lca\\Baz\\CronTask',
        ];
        $this->assertSame($expected, $result);
    }
}
