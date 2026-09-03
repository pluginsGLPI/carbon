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

namespace GlpiPlugin\Carbon\Command;

use DbUtils;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\Source;
use GlpiPlugin\Carbon\Source_Zone;
use GlpiPlugin\Carbon\Zone;
use Override;
use Plugin;
use RuntimeException;
use SplFileObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZipArchive;

class UpdateEmberDataCommand extends Command
{
    private OutputInterface $output;

    private $download_url = 'https://ourworldindata.org/grapher/carbon-intensity-electricity.zip?v=1&csvType=full&useColumnShortNames=false';

    #[Override]
    protected function configure()
    {
        $this
            ->setName('plugins:carbon:update_ember_carbon_intensity_data')
            ->setDescription('Download and import yearly carbon intensity data from Ember ')
            ->setHelp('This command download and import yearly carbon intensity data');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        if (!extension_loaded('zip')) {
            $output->writeln('<error>Zip extension is required to process the downloaded resource.</error>');
            return Command::FAILURE;
        }

        try {
            // Download ZIP archive
            $zip_file = $this->downloadZipArchive();
            $output->writeln('<info>Downloaded carbon intensity data archive</info>');

            // Extract CSV from ZIP
            $csv_file = $this->extractCsvFromZip($zip_file);
            $output->writeln('<info>Extracted carbon-intensity-electricity.csv</info>');

            // Import CSV data to database
            $this->importCsvData($csv_file);
            $output->writeln('<info>Imported carbon intensity data to database</info>');

            // Update the csv file embedded in the plugin
            $dest_file = Plugin::getPhpDir('carbon') . '/install/data/carbon_intensity/carbon-intensity-electricity.csv';
            if (is_writable($dest_file)) {
                $copy_success = copy($csv_file, $dest_file);
                if (!$copy_success) {
                    $output->writeln('<warn>Failed to update the installation-time CSV for yearly carbon intensities.</warn>');
                }
            } else {
                $output->writeln('<warn>Cannot update the installation-time CSV for yearly carbon intensities.</warn>');
            }

            // Cleanup
            if (file_exists($zip_file)) {
                unlink($zip_file);
            }
            if (file_exists($csv_file)) {
                unlink($csv_file);
            }

            return Command::SUCCESS;
        } catch (RuntimeException $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }

    /**
     * Download the ZIP archive from the remote URL
     *
     * @return string Path to the downloaded ZIP file
     * @throws RuntimeException
     */
    private function downloadZipArchive(): string
    {
        $temp_dir = GLPI_TMP_DIR;
        $zip_file = $temp_dir . '/carbon-intensity-' . time() . '.zip';

        $file_content = @file_get_contents($this->download_url);
        if ($file_content === false) {
            throw new RuntimeException("Failed to download resource from {$this->download_url}");
        }

        if (file_put_contents($zip_file, $file_content) === false) {
            throw new RuntimeException("Failed to write downloaded file to {$zip_file}");
        }

        return $zip_file;
    }

    /**
     * Extract carbon-intensity-electricity.csv from ZIP archive
     *
     * @param string $zip_file Path to the ZIP archive
     * @return string Path to the extracted CSV file
     * @throws RuntimeException
     */
    private function extractCsvFromZip(string $zip_file): string
    {
        $temp_dir = GLPI_TMP_DIR;
        $csv_file = $temp_dir . '/carbon-intensity-electricity.csv';

        $zip = new ZipArchive();
        if ($zip->open($zip_file) !== true) {
            throw new RuntimeException("Failed to open ZIP archive: {$zip_file}");
        }

        $csv_filename = 'carbon-intensity-electricity.csv';
        if ($zip->locateName($csv_filename) === false) {
            $zip->close();
            throw new RuntimeException("File {$csv_filename} not found in ZIP archive");
        }

        if ($zip->extractTo($temp_dir, $csv_filename) === false) {
            $zip->close();
            throw new RuntimeException("Failed to extract {$csv_filename} from ZIP archive");
        }

        $zip->close();

        return $csv_file;
    }

    /**
     * Import CSV data to database
     *
     * @param string $csv_file Path to the CSV file
     * @throws RuntimeException
     */
    private function importCsvData(string $csv_file): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        $dbUtil = new DbUtils();
        $table = $dbUtil->getTableForItemType(CarbonIntensity::class);

        // Create data source in DB
        $source = new Source();
        $source->getOrCreate([
            'fallback_level' => 2,
            'is_carbon_intensity_source' => 1,
        ], [
            'name' => 'Ember - Energy Institute',
        ]);
        $source_id = $source->getID();

        try {
            $file = new SplFileObject($csv_file, 'r');
        } catch (RuntimeException|\LogicException $e) {
            throw new RuntimeException("Failed to open CSV file: " . $e->getMessage(), $e->getCode(), $e);
        }

        $file->seek(PHP_INT_MAX);
        $rows_count = $file->key() - 1;
        $file->rewind();
        $file->setFlags(SplFileObject::READ_CSV);

        if (isset($this->output)) {
            $this->output->writeln("Importing carbon intensity data from Ember");
            $progress_bar = new ProgressBar($this->output, $rows_count);
        }

        $line_number = 0;
        while (($line = $file->fgetcsv(',', '"', '\\')) !== false) {
            $line_number++;
            if (isset($progress_bar)) {
                $progress_bar->advance();
            }

            if ($line_number === 1 || count($line) < 4) {
                continue; // Skip header or lines with insufficient data
            }

            $entity = $line[0];
            $code = $line[1];
            $year = (int) $line[2];
            $intensity = (float) $line[3];

            // Skip if the code is empty
            if ($code === '') {
                continue;
            }

            $zone = new Zone();
            $zone->getOrCreate([
                'plugin_carbon_sources_id_historical' => $source_id,
            ], [
                'name' =>  $entity,
            ]);
            $zone_id = $zone->getID();

            $source_zone = new Source_Zone();
            $source_zone->getOrCreate([
                'code' => '',
            ], [
                'plugin_carbon_sources_id' => $source_id,
                'plugin_carbon_zones_id'   => $zone_id,
            ]);

            // Insert or update in the database
            try {
                $DB->updateOrInsert($table, [
                    'intensity' => $intensity,
                    'data_quality' => 2, // constant GlpiPlugin\Carbon\DataTracking::DATA_QUALITY_ESTIMATED
                ], [
                    'date' => "$year-01-01 00:00:00",
                    'plugin_carbon_sources_id' => $source_id,
                    'plugin_carbon_zones_id'   => $zone_id,
                ]);
            } catch (RuntimeException $e) {
                $file = null;
                throw new RuntimeException("Failed to insert data for year $year; reason: " . $e->getMessage(), $e->getCode(), $e);
            }
        }

        if (isset($progress_bar)) {
            $progress_bar->setProgress($rows_count);
            $this->output->writeln('');
        }
        $file = null;
    }
}
