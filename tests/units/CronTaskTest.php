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

use Config;
use CronTask as GlpiCronTask;
use Geocoder\Exception\QuotaExceeded;
use Geocoder\Geocoder;
use Geocoder\Model\AddressCollection;
use Geocoder\Model\AdminLevel;
use Geocoder\Model\AdminLevelCollection;
use Geocoder\Model\Country;
use Geocoder\Provider\Nominatim\Model\NominatimAddress;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\CronTask;
use GlpiPlugin\Carbon\DataSource\CarbonIntensity\ClientInterface;
use GlpiPlugin\Carbon\Source;
use GlpiPlugin\Carbon\Source_Zone;
use GlpiPlugin\Carbon\Zone;
use Location as GlpiLocation;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CronTask::class)]
class CronTaskTest extends DbTestCase
{
    public function test_downloadCarbonIntensityFromSource_returns_0_when_no_data_is_downloaded()
    {
        $source = $this->createItem(Source::class);
        $zone = $this->createItem(Zone::class);
        $source_zone = $this->createItem(Source_Zone::class, [
            $source->getForeignKeyField() => $source->getID(),
            $zone->getForeignKeyField() => $zone->getID(),
            'code' => 'FOO',
            'is_download_enabled' => 1,
        ]);

        $data_source = $this->createStub(ClientInterface::class);
        $data_source->method('getZones')->willReturn([['name' => 'FOO']]);
        $data_source->method('isZoneSetupComplete')->willReturn(true);
        $data_source->method('getSourceZones')->willReturn([$source_zone->fields]);
        $carbon_intensity = $this->createStub(CarbonIntensity::class);
        $carbon_intensity->method('downloadOneZone')->willReturn(0);
        $cron_task = new CronTask();
        $glpi_cron_task = new GlpiCronTask();
        $glpi_cron_task->fields['param'] = 1000;
        $output = $cron_task->downloadCarbonIntensityFromSource($glpi_cron_task, $data_source, $carbon_intensity);
        $this->assertEquals(0, $output);
    }

    public function test_downloadCarbonIntensityFromSource_returns_1_when_a_positive_count_of_samples_are_downloaded()
    {
        $source = $this->createItem(Source::class);
        $zone = $this->createItem(Zone::class);
        $source_zone = $this->createItem(Source_Zone::class, [
            $source->getForeignKeyField() => $source->getID(),
            $zone->getForeignKeyField() => $zone->getID(),
            'code' => 'FOO',
            'is_download_enabled' => 1,
        ]);

        $data_source = $this->createStub(ClientInterface::class);
        $data_source->method('getZones')->willReturn([['name' => 'FOO']]);
        $data_source->method('isZoneSetupComplete')->willReturn(true);
        $data_source->method('getSourceZones')->willReturn([$source_zone->fields]);
        $carbon_intensity = $this->createStub(CarbonIntensity::class);
        $carbon_intensity->method('downloadOneZone')->willReturn(1024);
        $cron_task = new CronTask();
        $glpi_cron_task = new GlpiCronTask();
        $glpi_cron_task->fields['param'] = 1000;
        $output = $cron_task->downloadCarbonIntensityFromSource($glpi_cron_task, $data_source, $carbon_intensity);
        $this->assertEquals(1, $output);
    }

    public function test_downloadCarbonIntensityFromSource_returns_minus_1_when_a_negative_count_of_samples_are_downloaded()
    {
        // When the count of downloaded samples is negative the count is this absolute value,
        // and the negative sign means that an error occurred
        $source = $this->createItem(Source::class);
        $zone = $this->createItem(Zone::class);
        $source_zone = $this->createItem(Source_Zone::class, [
            $source->getForeignKeyField() => $source->getID(),
            $zone->getForeignKeyField() => $zone->getID(),
            'code' => 'FOO',
            'is_download_enabled' => 1,
        ]);

        $data_source = $this->createStub(ClientInterface::class);
        $data_source->method('getZones')->willReturn([['name' => 'FOO']]);
        $data_source->method('isZoneSetupComplete')->willReturn(true);
        $data_source->method('getSourceZones')->willReturn([$source_zone->fields]);
        $carbon_intensity = $this->createStub(CarbonIntensity::class);
        $carbon_intensity->method('downloadOneZone')->willReturn(-5);
        $cron_task = new CronTask();
        $glpi_cron_task = new GlpiCronTask();
        $glpi_cron_task->fields['param'] = 1000;
        $output = $cron_task->downloadCarbonIntensityFromSource($glpi_cron_task, $data_source, $carbon_intensity);
        $this->assertEquals(-1, $output);
    }

    public function testFillIncompleteLocations()
    {
        $cron_task = new CronTask();
        $glpi_cron_task = new GlpiCronTask();
        $glpi_cron_task = $this->getItem(GlpiCronTask::class, [
            'WHERE' => [
                'itemtype' => CronTask::class,
                'name'     => 'LocationCountryCode',
            ],
        ]);

        // Mock the getGeocoder method to return a callable that simulates geocoding
        $address_collection = new AddressCollection([
            new NominatimAddress(
                '',
                new AdminLevelCollection([
                    new AdminLevel(1, 'Île-de-France', 'IDF'),
                    new AdminLevel(2, 'Paris', '75'),
                ]),
                null,
                null,
                null,
                null,
                '75000',
                'Paris',
                null,
                new Country(
                    'France',
                    'FR'
                ),
            ),
        ]);
        $geocoder = $this->createStub(Geocoder::class);
        $geocoder->method('geocodeQuery')->willReturn($address_collection);
        $cron_task->setGeocoder(function () use ($geocoder) {
            return $geocoder;
        });

        // Disable geocoding while preparing the test
        Config::setConfigurationValues('plugin:carbon', [
            'geocoding_enabled' => '0',
        ]);

        // Test a single
        $glpi_locations = $this->createItems([
            GlpiLocation::class => [
                [
                    'name' => 'Valid Location',
                    'town' => 'Valid Town',
                    'country' => 'Valid Country',
                ],
            ],
        ]);

        Config::setConfigurationValues('plugin:carbon', [
            'geocoding_enabled' => '1',
        ]);
        $result = $cron_task->fillIncompleteLocations($glpi_cron_task);
        $this->assertEquals(1, $result);

        // Try again to geocode the location
        $result = $cron_task->fillIncompleteLocations($glpi_cron_task);
        $this->assertEquals(0, $result);

        $geocoder->method('geocodeQuery')->willThrowException(new QuotaExceeded("Quota exceeded"));
        $result = $cron_task->fillIncompleteLocations($glpi_cron_task);
        $this->assertEquals(0, $result);

        // Check that the result is as expected
        $this->assertGreaterThanOrEqual(0, $result);
    }
}
