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

namespace GlpiPlugin\Carbon\Command\Tests;

use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\Command\UpdateEmberDataCommand;
use GlpiPlugin\Carbon\Tests\DbTestCase;

class UpdateEmberDataCommandTest extends DbTestCase
{
    public function testImportFromZipCreatesRecords(): void
    {
        // Prepare a ZIP archive containing a carbon-intensity CSV
        $tmp = GLPI_TMP_DIR;
        $zipPath = $tmp . '/test_carbon_' . uniqid() . '.zip';
        $csvName = 'carbon-intensity-electricity.csv';
        $csvContent = implode("\n", [
            'Entity,Code,Year,Carbon intensity of electricity - gCO2/kWh',
            'France,FR,2020,50.5',
            'Quebec,QC,2019,30.2',
        ]) . "\n";

        $zip = new \ZipArchive();
        $res = $zip->open($zipPath, \ZipArchive::CREATE);
        $this->assertTrue($res, 'Unable to create test ZIP');
        $zip->addFromString($csvName, $csvContent);
        $zip->close();

        // Instantiate the command and call private methods via reflection
        $command = new UpdateEmberDataCommand();
        $ref = new \ReflectionClass($command);

        $extract = $ref->getMethod('extractCsvFromZip');
        version_compare(PHP_VERSION, '8.5', '<') ? $extract->setAccessible(true) : null;
        $extractedCsv = $extract->invoke($command, $zipPath);

        $this->assertFileExists($extractedCsv, 'CSV was not extracted');

        $import = $ref->getMethod('importCsvData');
        version_compare(PHP_VERSION, '8.5', '<') ? $import->setAccessible(true) : null;
        $import->invoke($command, $extractedCsv);

        // Verify data was inserted/updated in DB
        global $DB;
        $dbUtil = new \DbUtils();
        $table = $dbUtil->getTableForItemType(CarbonIntensity::class);

        $row = $DB->request(['FROM' => $table, 'WHERE' => ['date' => '2020-01-01 00:00:00']])->current();
        $this->assertNotNull($row, 'Expected carbon intensity row for 2020 not found');
        $this->assertEquals('50.5', (string) $row['intensity']);

        // Cleanup
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }
        if (file_exists($extractedCsv)) {
            unlink($extractedCsv);
        }
    }

}
