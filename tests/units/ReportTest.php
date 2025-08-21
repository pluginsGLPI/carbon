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

use GlpiPlugin\Carbon\Report;
use Session;
use Symfony\Component\DomCrawler\Crawler;

class ReportTest extends DbTestCase
{
    /**
     * @covers GlpiPlugin\Carbon\Report::getTypeName
     *
     * @return void
     */
    public function testGetTypeName()
    {
        $result = Report::getTypeName(1);
        $this->assertEquals('Carbon report', $result);

        $result = Report::getTypeName(Session::getPluralNumber());
        $this->assertEquals('Carbon reports', $result);
    }

    public function testGetIcon()
    {
        $result = Report::getIcon();
        $this->assertEquals('fa-solid fa-solar-panel', $result);
    }

    public function testGetMenuContent()
    {
        $this->login('glpi', 'glpi');
        $result = Report::getMenuContent();
        $this->assertIsArray($result);
        $this->assertEquals('Carbon reports', $result['title']);
        $this->assertEquals('fa-solid fa-solar-panel', $result['icon']);
    }

    public function testShowInstantReport()
    {
        $this->login('glpi', 'glpi');
        $_SERVER['REQUEST_URI'] = '/ajax/dashboard.php';
        ob_start();
        Report::showInstantReport();
        $output = ob_get_clean();
        $crawler = new Crawler($output);
        $this->assertCount(1, $crawler->filter('div.plugin_carbon_quick_report'));
        $this->assertCount(1, $crawler->filter('div.dashboard.mini'));
    }
}
