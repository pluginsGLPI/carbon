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
use DBmysql;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\Impact\Usage\AbstractUsageImpact;
use GlpiPlugin\Carbon\Location;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use GlpiPlugin\Carbon\UsageImpact;
use GlpiPlugin\Carbon\UsageInfo;
use Infocom;
use Location as GlpiLocation;
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
     * Create an asset with all required data to make it evaluable
     *
     * @return array<CommonDBTM> An asset and related objects
     */
    protected function getEvaluableAsset(): array
    {
        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = $this->createItem(Location::class, [
            $glpi_location->getForeignKeyField() => $glpi_location->getID(),
            'boavizta_zone' => 'FRA',
        ]);
        $glpi_asset_type = $this->createItem(static::$itemtype_type);
        $asset_type = $this->createItem('GlpiPlugin\\Carbon\\' . static::$itemtype_type, [
            $glpi_asset_type->getForeignKeyField() => $glpi_asset_type->getID(),
            'power_consumption' => 42,
            'category' => ComputerType::CATEGORY_DESKTOP,
        ]);
        $asset = $this->createItem(static::$itemtype, [
            $glpi_asset_type->getForeignKeyField() => $glpi_asset_type->getID(),
            $glpi_location->getForeignKeyField() => $glpi_location->getID(),
        ]);
        $infocom = $this->createItem(Infocom::class, [
            'itemtype' => get_class($asset),
            'items_id' => $asset->getID(),
            'use_date' => '2024-01-01',
        ]);
        $usage_info = $this->createItem(UsageInfo::class, [
            'itemtype' => get_class($asset),
            'items_id' => $asset->getID(),
            ComputerUsageProfile::getForeignKeyField() => 2, // Office hours ID
        ]);

        return [
            $asset,
            $glpi_location,
            $location,
            $glpi_asset_type,
            $asset_type,
            $infocom,
            $usage_info,
        ];
    }

    public function testGetItemsToEvaluate()
    {
        if (static::$itemtype === '' || static::$itemtype_type === '' || static::$itemtype_model === '') {
            // Ensure that the inherited test class is properly implemented for this test
            $this->fail('Itemtype propertiy not set in ' . static::class);
        }

        // Test the asset is evaluable when no impact is in the DB
        [$asset] = $this->getEvaluableAsset();
        $instance = new static::$instance_type($asset);
        $iterator = $instance->getItemsToEvaluate(static::$itemtype, [
            $asset->getTableField('id') => $asset->getID(),
        ]);
        $this->assertEquals(1, $iterator->count());

        // Test the asset is no longer evaluable when there is impact in the DB
        [$asset] = $this->getEvaluableAsset();
        $usage_impact = $this->createItem(UsageImpact::class, [
            'itemtype' => $asset->getType(),
            'items_id' => $asset->getID(),
            'recalculate' => 0,
        ]);
        $iterator = $instance->getItemsToEvaluate(static::$itemtype, [
            $asset::getTableField('id') => $asset->getID(),
        ]);
        $this->assertEquals(0, $iterator->count());

        // Test the asset is evaluable when there is impact in the DB but recamculate is set
        [$asset] = $this->getEvaluableAsset();
        $usage_impact = $this->createItem(UsageImpact::class, [
            'itemtype' => $asset->getType(),
            'items_id' => $asset->getID(),
            'recalculate' => 1,
        ]);
        $iterator = $instance->getItemsToEvaluate(static::$itemtype, [
            $asset::getTableField('id') => $asset->getID(),
        ]);
        $this->assertEquals(1, $iterator->count());
    }

    public function testGetEvaluableQuery()
    {
        /** @var DBmysql $DB */
        global $DB;

        // Test an asset with all requirements
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

        // Test an asset without a location
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

        // Test an asset without a boavizta_zone
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


        // Test an asset without usage info
        [$asset, $glpi_location, $location, $glpi_asset_type, $asset_type, $infocom, $usage_info] = $this->getEvaluableAsset();
        $usage_info->delete($usage_info->fields, true);
        $instance = new static::$instance_type($asset);
        $request = $instance->getEvaluableQuery(
            get_class($asset),
            [
                $asset::getTableField('id') => $asset->getID(),
            ]
        );
        $iterator = $DB->request($request);
        $this->assertEquals(0, $iterator->count());

        // Test an asset without usage profile
        [$asset, $glpi_location, $location, $glpi_asset_type, $asset_type, $infocom, $usage_info] = $this->getEvaluableAsset();
        $usage_info->update(['plugin_carbon_computerusageprofiles_id' => 0] + $usage_info->fields);
        $instance = new static::$instance_type($asset);
        $request = $instance->getEvaluableQuery(
            get_class($asset),
            [
                $asset::getTableField('id') => $asset->getID(),
            ]
        );
        $iterator = $DB->request($request);
        $this->assertEquals(0, $iterator->count());

        // Test an asset in the bin
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

        // Test an asset set as a template
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

        // Test an asset without a power consumption
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

        // Test an asset without a infocom date
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

    // public function testResetForItem()
    // {
    //     $asset = $this->createItem(static::$itemtype);
    //     $instance = $this->createItem(UsageImpact::class, [
    //         'itemtype' => get_class($asset),
    //         'items_id' => $asset->getID(),
    //     ]);

    //     $result = AbstractUsageImpact::resetForItem($asset);
    //     $this->assertTrue($result);
    //     $result = UsageImpact::getById($instance->getID());
    //     $this->assertFalse($result);
    // }
}
