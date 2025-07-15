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
use GlpiPlugin\Carbon\SearchOptions;
use Monitor;
use NetworkEquipment;
use Search;

class SearchTest extends DbTestCase
{
    public function testSearchOptions()
    {
        $this->login('glpi', 'glpi');

        $criterias = [
            'criteria' => [
                ['field' => SearchOptions::IS_HISTORIZABLE,
                    'searchtype' => 'equals',
                    'value'      => '1'
                ],
            ],
            'reset'    => 'reset'
        ];

        $data = Search::getDatas(Computer::class, $criterias);
        $sql = $data['sql']['search'];
        $this->assertIsArray($data);

        $criterias = [
            'criteria' => [
                ['field' => SearchOptions::IS_HISTORIZABLE,
                    'searchtype' => 'equals',
                    'value'      => '1'
                ],
            ],
            'reset'    => 'reset'
        ];

        $data = Search::getDatas(NetworkEquipment::class, $criterias);
        $sql = $data['sql']['search'];
        $this->assertIsArray($data);

        $criterias = [
            'criteria' => [
                ['field' => SearchOptions::IS_HISTORIZABLE,
                    'searchtype' => 'equals',
                    'value'      => '1'
                ],
            ],
            'reset'    => 'reset'
        ];

        $data = Search::getDatas(Monitor::class, $criterias);
        $sql = $data['sql']['search'];
        $this->assertIsArray($data);
    }
}
