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
use GlpiPlugin\Carbon\CronTask;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\Location;
use CronTask as GlpiCronTask;
use Geocoder\Geocoder;
use Geocoder\Model\AddressCollection;
use Geocoder\Model\AdminLevel;
use Geocoder\Model\AdminLevelCollection;
use Geocoder\Model\Country;
use Geocoder\Provider\Nominatim\Model\NominatimAddress;
use GlpiPlugin\Carbon\DataSource\CarbonIntensity\ClientInterface;
use Location as GlpiLocation;

class CronTaskTest extends DbTestCase
{
    public function downloadSourceProvider()
    {
        $data_source1 = $this->createStub(ClientInterface::class);
        $data_source1->method('getZones')->willReturn([['name' => 'test_zone']]);
        $intensity1 = $this->createStub(CarbonIntensity::class);
        $intensity1->method('downloadOneZone')->willReturn(0);
        yield 'download empty data' => [
            $data_source1,
            $intensity1,
            0,
        ];

        $data_source2 = $this->createStub(ClientInterface::class);
        $data_source2->method('getZones')->willReturn([['name' => 'test_zone']]);
        $intensity2 = $this->createStub(CarbonIntensity::class);
        $intensity2->method('downloadOneZone')->willReturn(1024);
        yield 'download complete' => [
            $data_source2,
            $intensity2,
            1,
        ];

        $data_source3 = $this->createStub(ClientInterface::class);
        $data_source3->method('getZones')->willReturn([['name' => 'test_zone']]);
        $intensity3 = $this->createStub(CarbonIntensity::class);
        $intensity3->method('downloadOneZone')->willReturn(-5);
        yield 'download incomplete' => [
            $data_source3,
            $intensity3,
            -1,
        ];
    }

    public function testDownloadCarbonIntensityFromSource()
    {
        foreach ($this->downloadSourceProvider() as $data) {
            list ($data_source, $intensity, $expected) = $data;
            $cron_task = new CronTask();
            $glpi_cron_task = new GlpiCronTask();
            $glpi_cron_task->fields['param'] = 1000;
            $output = $this->callPrivateMethod($cron_task, 'downloadCarbonIntensityFromSource', $glpi_cron_task, $data_source, $intensity);

            $this->assertEquals($expected, $output);
        }
    }

    public function testFillIncompleteLocations()
    {
        $cron_task = new CronTask();
        $glpi_cron_task = new GlpiCronTask();
        $glpi_cron_task->getFromDBByCrit([
            'itemtype' => get_class($cron_task),
            'name'     => 'LocationCountryCode',
        ]);

        // Mock the getGeocoder method to return a callable that simulates geocoding
        $geocoder_collection = new AddressCollection([
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
        $geocoder->method('geocodeQuery')->willReturn($geocoder_collection);
        $cron_task->setGeocoder(function () use ($geocoder) {
            return $geocoder;
        });

        // Disable geocoding while preparing the test
        Config::setConfigurationValues('plugin:carbon', [
            'geocoding_enabled' => '0',
        ]);

        // Test a single
        $glpi_locations = $this->getItems([
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

        $geocoder->method('geocodeQuery')->willThrowException(new \Geocoder\Exception\QuotaExceeded("Quota exceeded"));
        $result = $cron_task->fillIncompleteLocations($glpi_cron_task);
        $this->assertEquals(0, $result);

        // Check that the result is as expected
        $this->assertGreaterThanOrEqual(0, $result);
    }
}
