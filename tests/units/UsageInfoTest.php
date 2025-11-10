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

use GlpiPlugin\Carbon\UsageInfo;
use Computer as GlpiComputer;
use Contact;
use DBmysql;
use Monitor as GlpiMonitor;
use NetworkEquipment as GlpiNetworkEquipment;
use Symfony\Component\DomCrawler\Crawler;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UsageInfo::class)]
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

    public function testCanPurgeItem()
    {
        /** @var DBmysql $DB */
        global $DB;

        // Test an empty insrance
        $instance = new UsageInfo();
        $result = $instance->canPurgeItem();
        $this->assertFalse($result);

        // Test an instance for a invalid asset type
        $success = $DB->insert(getTableForItemType(UsageInfo::class), [
            'itemtype' => 'InvalidType',
        ]);
        $this->assertTrue($success);
        $instance = UsageInfo::getById($DB->insertId());
        $this->assertFalse($result);

        // Test an instance for a non existing asset
        $glpi_computer = $this->createItem(GlpiComputer::class);
        $instance = $this->createItem(UsageInfo::class, [
            'itemtype' => $glpi_computer::getType(),
            'items_id' => $glpi_computer->getID(),
        ]);
        $DB->delete($glpi_computer::getTable(), [
            'id' => $glpi_computer->getID(),
        ]);
        $this->assertFalse($result);

        // Test an instance for an existing asset without any right
        $glpi_computer = $this->createItem(GlpiComputer::class);
        $instance = $this->createItem(UsageInfo::class, [
            'itemtype' => $glpi_computer::getType(),
            'items_id' => $glpi_computer->getID(),
        ]);
        $this->assertFalse($result);

        // Test an instance for an existing asset
        $this->login('glpi', 'glpi');
        $glpi_computer = $this->createItem(GlpiComputer::class);
        $instance = $this->createItem(UsageInfo::class, [
            'itemtype' => $glpi_computer::getType(),
            'items_id' => $glpi_computer->getID(),
        ]);
        $this->assertFalse($result);
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
        $result = $usageInfo->getTabNameForItem($item);
        $crawler = new Crawler($result);
        $this->assertEquals('Environmental impact', $crawler->text());

        $item = new Contact();
        $tabName = $usageInfo->getTabNameForItem($item);
        $this->assertEquals('', $tabName);
    }

    public function testShowForItem()
    {
        // Test that the usage profile shows for a computer
        $glpi_computer = $this->createItem(GlpiComputer::class);
        $instance = $this->createItem(UsageInfo::class, [
            'itemtype' => $glpi_computer::getType(),
            'items_id' => $glpi_computer->getID(),
        ]);
        ob_start();
        $instance->showForItem($instance->getID());
        $output = ob_get_clean();
        $crawler = new Crawler($output);
        $usage_profile_dropdown = $crawler->filter('select[name="plugin_carbon_computerusageprofiles_id"]');
        $this->assertEquals(1, $usage_profile_dropdown->count());

        // Test that the usage profile does not shows for a monitor
        $glpi_monitor = $this->createItem(GlpiMonitor::class);
        $instance = $this->createItem(UsageInfo::class, [
            'itemtype' => $glpi_monitor::getType(),
            'items_id' => $glpi_monitor->getID(),
        ]);
        ob_start();
        $instance->showForItem($instance->getID());
        $output = ob_get_clean();
        $crawler = new Crawler($output);
        $usage_profile_dropdown = $crawler->filter('select[name="plugin_carbon_computerusageprofiles_id"]');
        $this->assertEquals(0, $usage_profile_dropdown->count());

        // Test that the usage profile does not shows for a network equipment
        $glpi_networkequipment = $this->createItem(GlpiNetworkEquipment::class);
        $instance = $this->createItem(UsageInfo::class, [
            'itemtype' => $glpi_networkequipment::getType(),
            'items_id' => $glpi_networkequipment->getID(),
        ]);
        ob_start();
        $instance->showForItem($instance->getID());
        $output = ob_get_clean();
        $crawler = new Crawler($output);
        $usage_profile_dropdown = $crawler->filter('select[name="plugin_carbon_computerusageprofiles_id"]');
        $this->assertEquals(0, $usage_profile_dropdown->count());
    }

    public function testShowcharts()
    {
        // Test that the charts shows for a computer
        $glpi_computer = $this->createItem(GlpiComputer::class);
        $instance = $this->createItem(UsageInfo::class, [
            'itemtype' => $glpi_computer::getType(),
            'items_id' => $glpi_computer->getID(),
        ]);
        ob_start();
        UsageInfo::showCharts($glpi_computer);
        $output = ob_get_clean();
        $crawler = new Crawler($output);
        $usage_profile_dropdown = $crawler->filter('select[name="plugin_carbon_computerusageprofiles_id"]');
        $this->assertEquals(1, $usage_profile_dropdown->count());

    }
}
