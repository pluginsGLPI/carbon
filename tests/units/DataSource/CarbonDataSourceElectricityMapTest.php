<?php

/**
 * -------------------------------------------------------------------------
 * carbon plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2024 Teclib' and contributors.
 * @license   MIT https://opensource.org/licenses/mit-license.php
 * @link      https://github.com/pluginsGLPI/carbon
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Carbon\DataSource\Tests;

use DateTime;
use GlpiPlugin\Carbon\DataSource\CarbonDataSourceElectricityMap;
use GlpiPlugin\Carbon\DataSource\RestApiClientInterface;
use GlpiPlugin\Carbon\Tests\CommonTestCase;

class CarbonDataSourceElectricityMapTest extends CommonTestCase
{
    const RESPONSE_1 = '{"zone":"FR","history":[{"zone":"FR","carbonIntensity":19,"datetime":"2024-07-04T11:00:00.000Z","updatedAt":"2024-07-05T09:49:19.074Z","createdAt":"2024-07-01T11:51:52.559Z","emissionFactorType":"lifecycle","isEstimated":false,"estimationMethod":null},{"zone":"FR","carbonIntensity":20,"datetime":"2024-07-04T12:00:00.000Z","updatedAt":"2024-07-05T06:50:12.469Z","createdAt":"2024-07-01T12:53:35.480Z","emissionFactorType":"lifecycle","isEstimated":false,"estimationMethod":null},{"zone":"FR","carbonIntensity":20,"datetime":"2024-07-04T13:00:00.000Z","updatedAt":"2024-07-05T06:50:12.469Z","createdAt":"2024-07-01T13:48:59.815Z","emissionFactorType":"lifecycle","isEstimated":false,"estimationMethod":null},{"zone":"FR","carbonIntensity":19,"datetime":"2024-07-04T14:00:00.000Z","updatedAt":"2024-07-05T06:50:12.469Z","createdAt":"2024-07-01T14:52:12.268Z","emissionFactorType":"lifecycle","isEstimated":false,"estimationMethod":null},{"zone":"FR","carbonIntensity":19,"datetime":"2024-07-04T15:00:00.000Z","updatedAt":"2024-07-05T09:48:43.796Z","createdAt":"2024-07-01T15:48:54.178Z","emissionFactorType":"lifecycle","isEstimated":false,"estimationMethod":null},{"zone":"FR","carbonIntensity":18,"datetime":"2024-07-04T16:00:00.000Z","updatedAt":"2024-07-04T18:49:59.712Z","createdAt":"2024-07-01T16:48:10.284Z","emissionFactorType":"lifecycle","isEstimated":false,"estimationMethod":null},{"zone":"FR","carbonIntensity":17,"datetime":"2024-07-04T17:00:00.000Z","updatedAt":"2024-07-04T19:52:00.546Z","createdAt":"2024-07-01T17:47:56.975Z","emissionFactorType":"lifecycle","isEstimated":false,"estimationMethod":null},{"zone":"FR","carbonIntensity":17,"datetime":"2024-07-04T18:00:00.000Z","updatedAt":"2024-07-04T20:50:28.105Z","createdAt":"2024-07-01T18:49:30.191Z","emissionFactorType":"lifecycle","isEstimated":false,"estimationMethod":null},{"zone":"FR","carbonIntensity":16,"datetime":"2024-07-04T19:00:00.000Z","updatedAt":"2024-07-04T21:50:19.879Z","createdAt":"2024-07-01T19:50:32.766Z","emissionFactorType":"lifecycle","isEstimated":false,"estimationMethod":null},{"zone":"FR","carbonIntensity":17,"datetime":"2024-07-04T20:00:00.000Z","updatedAt":"2024-07-04T22:47:53.400Z","createdAt":"2024-07-01T20:50:36.071Z","emissionFactorType":"lifecycle","isEstimated":false,"estimationMethod":null},{"zone":"FR","carbonIntensity":17,"datetime":"2024-07-04T21:00:00.000Z","updatedAt":"2024-07-05T09:49:19.074Z","createdAt":"2024-07-01T21:49:04.629Z","emissionFactorType":"lifecycle","isEstimated":false,"estimationMethod":null},{"zone":"FR","carbonIntensity":20,"datetime":"2024-07-04T22:00:00.000Z","updatedAt":"2024-07-05T09:49:10.859Z","createdAt":"2024-07-01T22:47:46.787Z","emissionFactorType":"lifecycle","isEstimated":false,"estimationMethod":null},{"zone":"FR","carbonIntensity":17,"datetime":"2024-07-04T23:00:00.000Z","updatedAt":"2024-07-05T09:48:16.286Z","createdAt":"2024-07-01T23:47:39.393Z","emissionFactorType":"lifecycle","isEstimated":false,"estimationMethod":null},{"zone":"FR","carbonIntensity":18,"datetime":"2024-07-05T00:00:00.000Z","updatedAt":"2024-07-05T09:48:16.286Z","createdAt":"2024-07-02T00:50:14.538Z","emissionFactorType":"lifecycle","isEstimated":false,"estimationMethod":null},{"zone":"FR","carbonIntensity":19,"datetime":"2024-07-05T01:00:00.000Z","updatedAt":"2024-07-05T09:49:19.074Z","createdAt":"2024-07-02T01:49:00.776Z","emissionFactorType":"lifecycle","isEstimated":false,"estimationMethod":null},{"zone":"FR","carbonIntensity":19,"datetime":"2024-07-05T02:00:00.000Z","updatedAt":"2024-07-05T09:49:19.074Z","createdAt":"2024-07-02T02:47:55.386Z","emissionFactorType":"lifecycle","isEstimated":false,"estimationMethod":null},{"zone":"FR","carbonIntensity":18,"datetime":"2024-07-05T03:00:00.000Z","updatedAt":"2024-07-05T09:49:10.859Z","createdAt":"2024-07-02T03:50:45.189Z","emissionFactorType":"lifecycle","isEstimated":false,"estimationMethod":null},{"zone":"FR","carbonIntensity":19,"datetime":"2024-07-05T04:00:00.000Z","updatedAt":"2024-07-05T09:48:43.796Z","createdAt":"2024-07-02T04:47:27.202Z","emissionFactorType":"lifecycle","isEstimated":false,"estimationMethod":null},{"zone":"FR","carbonIntensity":19,"datetime":"2024-07-05T05:00:00.000Z","updatedAt":"2024-07-05T09:48:16.286Z","createdAt":"2024-07-02T05:46:25.522Z","emissionFactorType":"lifecycle","isEstimated":false,"estimationMethod":null},{"zone":"FR","carbonIntensity":18,"datetime":"2024-07-05T06:00:00.000Z","updatedAt":"2024-07-05T09:48:16.286Z","createdAt":"2024-07-02T06:46:15.925Z","emissionFactorType":"lifecycle","isEstimated":false,"estimationMethod":null},{"zone":"FR","carbonIntensity":18,"datetime":"2024-07-05T07:00:00.000Z","updatedAt":"2024-07-05T09:49:19.074Z","createdAt":"2024-07-02T07:48:49.630Z","emissionFactorType":"lifecycle","isEstimated":false,"estimationMethod":null},{"zone":"FR","carbonIntensity":18,"datetime":"2024-07-05T08:00:00.000Z","updatedAt":"2024-07-05T09:48:43.796Z","createdAt":"2024-07-02T08:50:03.320Z","emissionFactorType":"lifecycle","isEstimated":true,"estimationMethod":"TIME_SLICER_AVERAGE"},{"zone":"FR","carbonIntensity":18,"datetime":"2024-07-05T09:00:00.000Z","updatedAt":"2024-07-05T09:48:16.286Z","createdAt":"2024-07-02T09:47:44.047Z","emissionFactorType":"lifecycle","isEstimated":true,"estimationMethod":"TIME_SLICER_AVERAGE"},{"zone":"FR","carbonIntensity":19,"datetime":"2024-07-05T10:00:00.000Z","updatedAt":"2024-07-05T09:48:43.796Z","createdAt":"2024-07-02T10:50:42.737Z","emissionFactorType":"lifecycle","isEstimated":true,"estimationMethod":"TIME_SLICER_AVERAGE"}]}';

    public function testFetchCarbonIntensity()
    {
        $client = $this->createStub(RestApiClientInterface::class);
        $client->method('request')->willReturn(json_decode(self::RESPONSE_1, true));

        $source = new CarbonDataSourceElectricityMap($client);

        $intensities = $source->fetchCarbonIntensity();

        $this->assertIsArray($intensities);
        $this->assertArrayHasKey('source', $intensities);
        $this->assertEquals('ElectricityMap', $intensities['source']);
        $this->assertArrayHasKey('FR', $intensities);
        $this->assertIsArray($intensities['FR']);
        $this->assertEquals(24, count($intensities['FR']));
    }
}
