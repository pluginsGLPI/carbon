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

use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\Profile;
use Profile as GlpiProfile;
use Symfony\Component\DomCrawler\Crawler;

class ProfileTest extends DbTestCase
{
    /**
     * @covers GlpiPlugin\Carbon\Profile::getTabNameForItem
     *
     * @return void
     */
    public function testGetTabNameForItem()
    {
        $profile = new Profile();
        $item = new GlpiProfile();
        $tabName = $profile->getTabNameForItem($item);
        $this->assertEquals(__('Environmental impact', 'carbon'), $tabName);
    }

    /**
     * @covers GlpiPlugin\Carbon\Profile::showForm
     *
     * @return void
     */
    public function testShowForm()
    {
        $this->login('glpi', 'glpi');
        $glpi_profile = GlpiProfile::getById(4); // Super admin
        $profile = new Profile();
        $output = '';
        ob_start(function ($buffer) use ($output) {
            $output .= $buffer;
        });
        $result = $profile->showForm($profile->getID());
        $output = ob_get_clean();
        $this->assertTrue($result);
        $crawler = new Crawler($output);
        $checkboxes = $crawler->filter('input[type="checkbox"]');
        $this->assertCount(4, $checkboxes);

        $save = $crawler->filter('button[type="submit"]');
        $this->assertCount(1, $save);
    }
}
