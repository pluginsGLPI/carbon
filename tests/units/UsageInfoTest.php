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

use Computer as GlpiComputer;
use ComputerType as GlpiComputerType;
use Contact;
use DBmysql;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\EmbodiedImpact;
use GlpiPlugin\Carbon\UsageImpact;
use GlpiPlugin\Carbon\UsageInfo;
use Infocom;
use Monitor as GlpiMonitor;
use NetworkEquipment as GlpiNetworkEquipment;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DomCrawler\Crawler;

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
        $this->login('glpi', 'glpi');
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
        $itemtypes = PLUGIN_CARBON_TYPES;
        foreach ($itemtypes as $itemtype) {
            // Test charts and data are visible for an asset
            $item = $this->createItem($itemtype);
            $instance = $this->createItem(UsageInfo::class, [
                'itemtype' => $item::getType(),
                'items_id' => $item->getID(),
            ]);
            $embodied_impact = $this->createItem(EmbodiedImpact::class, [
                'itemtype' => $item::getType(),
                'items_id' => $item->getID(),
                'gwp' => 10,
                'adp' => 11,
                'pe'  => 12,
            ]);
            ob_start();
            UsageInfo::showCharts($item);
            $output = ob_get_clean();
            $crawler = new Crawler($output);
            $monthlyCarbonEmissionChart = $crawler->filter('#carbonEmissionPerMonthChart');
            $this->assertEquals(1, $monthlyCarbonEmissionChart->count());
            $this->assertTrue($this->testEmbodiedGwp($crawler));
            $this->assertTrue($this->testEmbodiedAdp($crawler));
            $this->assertTrue($this->testEmbodiedPe($crawler));

            // Test charts are visible for an asset - no embodied data
            $item = $this->createItem($itemtype);
            $instance = $this->createItem(UsageInfo::class, [
                'itemtype' => $item::getType(),
                'items_id' => $item->getID(),
            ]);
            ob_start();
            UsageInfo::showCharts($item);
            $output = ob_get_clean();
            $crawler = new Crawler($output);
            $monthlyCarbonEmissionChart = $crawler->filter('#carbonEmissionPerMonthChart');
            $this->assertEquals(1, $monthlyCarbonEmissionChart->count());
            $this->assertFalse($this->testEmbodiedGwp($crawler));
            $this->assertFalse($this->testEmbodiedAdp($crawler));
            $this->assertFalse($this->testEmbodiedPe($crawler));

            // Test charts are visible for an asset - empty embodied data
            $item = $this->createItem($itemtype);
            $instance = $this->createItem(UsageInfo::class, [
                'itemtype' => $item::getType(),
                'items_id' => $item->getID(),
            ]);
            $embodied_impact = $this->createItem(EmbodiedImpact::class, [
                'itemtype' => $item::getType(),
                'items_id' => $item->getID(),
                'gwp' => null,
                'adp' => null,
                'pe'  => null,
            ]);
            ob_start();
            UsageInfo::showCharts($item);
            $output = ob_get_clean();
            $crawler = new Crawler($output);
            $monthlyCarbonEmissionChart = $crawler->filter('#carbonEmissionPerMonthChart');
            $this->assertEquals(1, $monthlyCarbonEmissionChart->count());
            $this->assertFalse($this->testEmbodiedGwp($crawler));
            $this->assertFalse($this->testEmbodiedAdp($crawler));
            $this->assertFalse($this->testEmbodiedPe($crawler));
        }
    }

    private function testEmbodiedGwp(Crawler $crawler): bool
    {
        $items = $crawler->filter('#plugin_carbon_embodied_impacts #embodied_gwp_tip');
        return $items->count() === 1;
    }

    private function testEmbodiedAdp(Crawler $crawler): bool
    {
        $items = $crawler->filter('#plugin_carbon_embodied_impacts #embodied_adp_tip');
        return $items->count() === 1;
    }

    private function testEmbodiedPe(Crawler $crawler): bool
    {
        $items = $crawler->filter('#plugin_carbon_embodied_impacts #embodied_pe_tip');
        return $items->count() === 1;
    }

    public function test_getLifespanInHours_returns_null_when_no_decomission_date()
    {
        $glpi_computer = $this->createItem(GlpiComputer::class, [
            'date_creation' => '2024-02-03 11:00:00',
        ]);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype' => get_class($glpi_computer),
            'items_id' => $glpi_computer->getID(),
            'delivery_date'     => '2025-02-03',
            'use_date'          => '2023-02-03',
            'buy_date'          => '2022-02-03',
            'decommission_date' => null,
        ]);

        $usage_info = new UsageInfo();
        $result = $usage_info->getLifespanInHours($infocom);
        $this->assertNull($result);
    }

    public function test_getLifespanInHours_returns_null_when_no_inventory_entry_date()
    {
        $glpi_computer = $this->createItem(GlpiComputer::class, [
            'date_creation' => null,
        ]);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype' => get_class($glpi_computer),
            'items_id' => $glpi_computer->getID(),
            'delivery_date'     => null,
            'use_date'          => null,
            'buy_date'          => null,
            'decommission_date' => '2026-02-03 11:00:00',
        ]);

        $usage_info = new UsageInfo();
        $result = $usage_info->getLifespanInHours($glpi_computer);
        $this->assertNull($result);
    }

    public function test_getLifespanInHours_returns_hours_when_all_data_are_present()
    {
        $glpi_computer = $this->createItem(GlpiComputer::class, [
            'date_creation' => null,
        ]);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype' => get_class($glpi_computer),
            'items_id' => $glpi_computer->getID(),
            'delivery_date'     => null,
            'use_date'          => '2026-02-01 11:00:00', // Time is not stored in DB, will be processed as 00:00:00
            'buy_date'          => null,
            'decommission_date' => '2026-02-03 11:00:00',
        ]);

        $usage_info = new UsageInfo();
        $result = $usage_info->getLifespanInHours($glpi_computer);
        $this->assertSame(59, $result);
    }

    public function test_getLifespanInHours_returns_hours_when_date_creation()
    {
        $glpi_computer = $this->createItem(GlpiComputer::class, [
            'date_creation' => '2022-01-01',
        ]);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype' => get_class($glpi_computer),
            'items_id' => $glpi_computer->getID(),
            'delivery_date'     => null,
            'use_date'          => null,
            'buy_date'          => null,
            'decommission_date' => '2026-02-03 11:00:00',
        ]);

        $usage_info = new UsageInfo();
        $result = $usage_info->getLifespanInHours($glpi_computer);
        $this->assertSame(35867, $result);
    }

    public function test_getLifespanInHours_returns_hours_when_no_decommission_date_but_has_planned_lifetime()
    {
        $glpi_computer = $this->createItem(GlpiComputer::class);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype' => get_class($glpi_computer),
            'items_id' => $glpi_computer->getID(),
            'delivery_date'     => null,
            'use_date'          => '2022-01-01',
            'buy_date'          => null,
            'decommission_date' => null,
        ]);
        $usage_info = $this->createItem(UsageInfo::class, [
            'itemtype' => get_class($glpi_computer),
            'items_id' => $glpi_computer->getID(),
            'planned_lifespan'  => 60, // 5 years = 60 months
        ]);

        $usage_info = new UsageInfo();
        $result = $usage_info->getLifespanInHours($glpi_computer);
        $this->assertSame(43824, $result);
    }


    public function test_post_updateItem_does_not_invalidate_usage_impact_when_decommission_date_is_set()
    {
        $glpi_asset = $this->createItem(GlpiComputer::class);
        $usage_impact = $this->createItem(UsageImpact::class, [
            'itemtype' => get_class($glpi_asset),
            'items_id' => $glpi_asset->getID(),
            'recalculate' => 0,
        ]);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype' => get_class($glpi_asset),
            'items_id' => $glpi_asset->getID(),
            'decomission_date' => '2026-06-06',
        ]);
        $usage_info = $this->createItem(UsageInfo::class, [
            'itemtype' => get_class($glpi_asset),
            'items_id' => $glpi_asset->getID(),
        ]);
        $usage_info->update(['id' => $usage_info->getID(), 'planned_lifespan' => 60]);
        $usage_impact->getFromDB($usage_impact->getID());
        $this->assertSame(0, $usage_impact->fields['recalculate']);
    }

    public function test_post_updateItem_invalidate_usage_impact_when_decommission_date_is_not_set()
    {
        $glpi_asset = $this->createItem(GlpiComputer::class);
        $usage_impact = $this->createItem(UsageImpact::class, [
            'itemtype' => get_class($glpi_asset),
            'items_id' => $glpi_asset->getID(),
            'recalculate' => 0,
        ]);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype' => get_class($glpi_asset),
            'items_id' => $glpi_asset->getID(),
            'decomission_date' => null,
        ]);
        $usage_info = $this->createItem(UsageInfo::class, [
            'itemtype' => get_class($glpi_asset),
            'items_id' => $glpi_asset->getID(),
        ]);
        $usage_info->update(['id' => $usage_info->getID(), 'planned_lifespan' => 60]);
        $usage_impact->getFromDB($usage_impact->getID());
        $this->assertSame(0, $usage_impact->fields['recalculate']);
    }
}
