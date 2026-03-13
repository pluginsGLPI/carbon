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

namespace GlpiPlugin\Carbon\Tests\Impact\Usage\Boavizta;

use CommonDBTM;
use Computer as GlpiComputer;
use ComputerType as GLPIComputerType;
use DBmysql;
use Glpi\Asset\Asset_PeripheralAsset;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\Impact\Usage\AbstractUsageImpact;
use GlpiPlugin\Carbon\Location;
use GlpiPlugin\Carbon\MonitorType;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use GlpiPlugin\Carbon\UsageImpact;
use GlpiPlugin\Carbon\UsageInfo;
use Infocom;
use Location as GlpiLocation;
use Monitor as GlpiMonitor;
use MonitorType as GlpiMonitorType;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractUsageImpact::class)]
abstract class AbstractAsset extends DbTestCase
{
    protected static string $instance_type = '';

    /** @var class-string<CommonDBTM> itemtype of the an asset (i.e. Computer, Monotir, ...) */
    protected static string $itemtype = '';

    /** @var class-string<CommonDBTM> itemtype of the type of an asset (i.e. ComputerType, MonitorType) */
    protected static string $itemtype_type = '';

    /** @var class-string<CommonDBTM> itemtype of the model of an asset (i.e. ComputerModel, MonitorModel) */
    protected static string $itemtype_model = '';

    /**
     * Get an asset with all conditions to be evaluable, with all necessary objects
     *
     * @return array ordered list of objects
     *               - an asset like a computer, a monitor, a network equipment, ...
     *               - a location
     *               - a plugin location (extending the locations properties)
     *               - a type of asset
     *               - an infocom object (financial information)
     *               - an isage information object
     *               - optional: other  objects for more complex case (see monitors, attached to a computer)
     */
    abstract protected function getEvaluableAsset(): array;

    /**
     * Create an asset with all required data to make it evaluable
     *
     * @return array<CommonDBTM> An asset and related objects
     */
    protected function getEvaluableComputer(): array
    {
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = $this->createItem(Location::class, [
            $glpi_location->getForeignKeyField() => $glpi_location->getID(),
            'boavizta_zone' => 'FRA',
        ]);
        $glpi_computer_type = $this->createItem(GLPIComputerType::class);
        $computer_type = $this->createItem(ComputerType::class, [
            $glpi_computer_type->getForeignKeyField() => $glpi_computer_type->getID(),
            'power_consumption' => 42,
            'category' => ComputerType::CATEGORY_DESKTOP,
        ]);
        $computer = $this->createItem(GlpiComputer::class, [
            $glpi_computer_type->getForeignKeyField() => $glpi_computer_type->getID(),
            $glpi_location->getForeignKeyField() => $glpi_location->getID(),
        ]);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype' => get_class($computer),
            'items_id' => $computer->getID(),
            'use_date' => '2024-01-01',
        ]);
        $usage_info = $this->createItem(UsageInfo::class, [
            'itemtype' => get_class($computer),
            'items_id' => $computer->getID(),
            ComputerUsageProfile::getForeignKeyField() => 2, // Office hours ID
        ]);

        return [
            $computer,
            $glpi_location,
            $location,
            $glpi_computer_type,
            $computer_type,
            $infocom,
            $usage_info,
        ];
    }

    /**
     * Create an asset with all required data to make it evaluable
     *
     * @return array<CommonDBTM> An asset and related objects
     */
    protected function getEvaluableMonitor(): array
    {
        [
            $glpi_computer,
            $glpi_location,
            $location,
            $glpi_computer_type,
            $computer_type,
            $glpi_computer_infocom,
            $glpi_computer_usage_info,
        ] = $this->getEvaluableComputer();

        $glpi_monitor_type = $this->createItem(GlpiMonitorType::class);
        $monitor_type = $this->createItem(MonitorType::class, [
            $glpi_monitor_type->getForeignKeyField() => $glpi_monitor_type->getID(),
            'power_consumption' => 42,
        ]);
        $glpi_monitor = $this->createItem(GlpiMonitor::class, [
            $glpi_monitor_type->getForeignKeyField() => $glpi_monitor_type->getID(),
            $glpi_location->getForeignKeyField() => $glpi_location->getID(),
        ]);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype' => get_class($glpi_monitor),
            'items_id' => $glpi_monitor->getID(),
            'use_date' => '2024-01-01',
        ]);
        $usage_info = $this->createItem(UsageInfo::class, [
            'itemtype' => get_class($glpi_monitor),
            'items_id' => $glpi_monitor->getID(),
        ]);

        // Associate the monitor to the computer
        $asset_peripheralasset = $this->createItem(Asset_PeripheralAsset::class, [
            'itemtype_peripheral' => get_class($glpi_monitor),
            'itemtype_asset'      => get_class($glpi_computer),
            'items_id_peripheral' => $glpi_monitor->getID(),
            'items_id_asset'      => $glpi_computer->getID(),
        ]);

        return [
            $glpi_monitor,
            $glpi_location,
            $location,
            $glpi_monitor_type,
            $monitor_type,
            $infocom,
            $usage_info,
            $asset_peripheralasset,
            $glpi_computer,
        ];
    }

    public function test_GetItemsToEvaluate_is_evaluable_when_no_impacts_exists()
    {
        [$asset] = $this->getEvaluableAsset();
        $instance = new static::$instance_type($asset);
        $iterator = $instance->getItemsToEvaluate(static::$itemtype, [
            $asset->getTableField('id') => $asset->getID(),
        ]);
        $this->assertEquals(1, $iterator->count());
    }

    public function test_GetItemsToEvaluate_is_not_evaluable_when_impacts_exists()
    {
        [$asset] = $this->getEvaluableAsset();
        $usage_impact = $this->createItem(UsageImpact::class, [
            'itemtype' => $asset->getType(),
            'items_id' => $asset->getID(),
            'recalculate' => 0,
        ]);
        $instance = new static::$instance_type($asset);
        $iterator = $instance->getItemsToEvaluate(static::$itemtype, [
            $asset::getTableField('id') => $asset->getID(),
        ]);
        $this->assertEquals(0, $iterator->count());
    }

    public function test_GetItemsToEvaluate_is_not_evaluable_when_impacts_to_recalculate_exists()
    {
        [$asset] = $this->getEvaluableAsset();
        $usage_impact = $this->createItem(UsageImpact::class, [
            'itemtype' => $asset->getType(),
            'items_id' => $asset->getID(),
            'recalculate' => 1,
        ]);
        $instance = new static::$instance_type($asset);
        $iterator = $instance->getItemsToEvaluate(static::$itemtype, [
            $asset::getTableField('id') => $asset->getID(),
        ]);
        $this->assertEquals(1, $iterator->count());
    }

    public function test_getEvaluableQuery_returns_one_when_asset_mets_all_requirements()
    {
        /** @var DBmysql $DB */
        global $DB;

        [$asset] = $this->getEvaluableAsset();
        $instance = new static::$instance_type($asset);
        $request = $instance->getEvaluableQuery(
            get_class($asset),
            [
                $asset::getTableField('id') => $asset->getID(),
            ]
        );
        $iterator = $DB->request($request);
        $this->assertEquals(1, $iterator->count());
    }

    public function test_getEvaluableQuery_returns_zero_when_asset_is_in_trash_bin()
    {
        /** @var DBmysql $DB */
        global $DB;

        [$asset, $glpi_location, $location, $glpi_asset_type, $asset_type, $infocom, $usage_info] = $this->getEvaluableAsset();
        $asset->delete($asset->fields, false);
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

    public function test_getEvaluableQuery_returns_zero_when_asset_is_a_template()
    {
        /** @var DBmysql $DB */
        global $DB;

        [$asset, $glpi_location, $location, $glpi_asset_type, $asset_type, $infocom, $usage_info] = $this->getEvaluableAsset();
        $asset->update(['is_template' => 1] + $asset->fields);
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

    public function test_getEvaluableQuery_returns_zero_when_asset_has_no_location()
    {
        /** @var DBmysql $DB */
        global $DB;

        [$asset, $glpi_location, $location, $glpi_asset_type, $asset_type, $infocom, $usage_info] = $this->getEvaluableAsset();
        $asset->update(['locations_id' => 0] + $asset->fields);
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

    public function test_getEvaluableQuery_returns_zero_when_asset_has_no_boavizta_zone()
    {
        /** @var DBmysql $DB */
        global $DB;

        [$asset, $glpi_location, $location, $glpi_asset_type, $asset_type, $infocom, $usage_info] = $this->getEvaluableAsset();
        $location->update(['boavizta_zone' => ''] + $location->fields);
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

    public function test_getEvaluableQuery_returns_zero_when_asset_has_no_power_consumption()
    {
        /** @var DBmysql $DB */
        global $DB;

        [$asset, $glpi_location, $location, $glpi_asset_type, $asset_type, $infocom, $usage_info] = $this->getEvaluableAsset();
        $asset_type->update(['power_consumption' => 0] + $asset_type->fields);
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

    public function test_getEvaluableQuery_returns_zero_when_asset_has_no_infocom()
    {
        /** @var DBmysql $DB */
        global $DB;

        [$asset, $glpi_location, $location, $glpi_asset_type, $asset_type, $infocom, $usage_info] = $this->getEvaluableAsset();
        $infocom->update(['use_date' => null] + $infocom->fields);
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
