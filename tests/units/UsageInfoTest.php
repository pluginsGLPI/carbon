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
use GlpiPlugin\Carbon\UsageInfo;
use Computer as GlpiComputer;
use Contact;
use Symfony\Component\DomCrawler\Crawler;

class UsageInfoTest extends DbTestCase
{
    /**
     * #CoversMethod GlpiPlugin\Carbon\UsageInfo::getTypeName
     *
     * @return void
     */
    public function testGetTypeName()
    {
        $usageInfo = new UsageInfo();
        $typeName = $usageInfo->getTypeName();
        $this->assertEquals(__('Usage informations', 'Carbon'), $typeName);
    }

    /**
     * #CoversMethod GlpiPlugin\Carbon\UsageInfo::getIcon
     *
     * @return void
     */
    public function testGetIcon()
    {
        $usageInfo = new UsageInfo();
        $icon = $usageInfo->getIcon();
        $this->assertEquals('fa-solid fa-solar-panel', $icon);
    }

    /**
     * #CoversMethod GlpiPlugin\Carbon\UsageInfo::getTabNameForItem
     *
     * @return void
     */
    public function testGetTabNameForItem()
    {
        $usageInfo = new UsageInfo();
        $item = new GlpiComputer();
        $tabName = $usageInfo->getTabNameForItem($item);
        $this->assertEquals('Environmental impact', $tabName);

        $item = new Contact();
        $tabName = $usageInfo->getTabNameForItem($item);
        $this->assertEquals('', $tabName);
    }
}
