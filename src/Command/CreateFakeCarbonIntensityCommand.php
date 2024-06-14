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

namespace GlpiPlugin\Carbon\Command;

use DateTime;
use DateInterval;
use DateTimeZone;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\CarbonIntensityZone;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

// 6 months

define('DATE_MIN', 'P6M');

class CreateFakeCarbonIntensityCommand extends Command
{
    /** @var int ID of the data source being processed */
    private int $source_id, $zone_id;

    private InputInterface $input;
    private OutputInterface $output;

    protected function configure()
    {
        $this
           ->setName('plugin:carbon:create_carbon_intensity')
           ->setDescription("Create fake carbon intenssity data");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $message = __("Creating data source name", 'carbon');
        $output->writeln("<info>$message</info>");
        $dataSource = new CarbonIntensitySource();
        $source_name = 'Fake data';
        if (!$dataSource->getFromDBByCrit(['name' => $source_name])) {
            $dataSource->add([
                'name' => $source_name,
            ]);
        }
        $this->source_id = $dataSource->getID();

        $message = __("Creating data zone name", 'carbon');
        $output->writeln("<info>$message</info>");
        $zone = new CarbonIntensityZone();
        $zone_name = 'France';
        if (!$zone->getFromDBByCrit(['name' => $zone_name])) {
            $zone->add([
                'name' => $zone_name,
            ]);
        }
        $this->zone_id = $zone->getID();

        $message = __("Creating fake data...", 'carbon');
        $output->writeln("<info>$message</info>");

        $start_date = new DateTime('01-01-2024 00:00:00', new DateTimeZone('UTC'));
        $end_date = new DateTime('31-05-2024 23:59:59', new DateTimeZone('UTC'));
        $this->generateFakeData($start_date, $end_date);

        return Command::SUCCESS;
    }

    protected function generateFakeData(DateTime $start_date, DateTime $end_date)
    {
        // Initialize progress bar
        $days = (int) $start_date->diff($end_date)->format('%a');
        $progress_bar = new ProgressBar($this->output, $days);

        // iterate day by day from $start_date to $end_date
        $previous_intensity = null;
        $processing_date = clone $start_date;
        while ($processing_date < $end_date) {
            $intensities = [];
            for ($hour = 0; $hour < 24; $hour++) {
                $processing_date->setTime($hour, 0, 0);
                $intensities[$processing_date->format('Y-m-d H:i:s')] = $this->generateIntensity($processing_date, $previous_intensity);
                $previous_intensity = $intensities[$processing_date->format('Y-m-d H:i:s')];
            }

            // Save data
            $carbon_intensity = new CarbonIntensity();
            foreach ($intensities as $date => $intensity) {
                $carbon_intensity->add([
                    'plugin_carbon_carbonintensitysources_id' => $this->source_id,
                    'plugin_carbon_carbonintensityzones_id' => $this->zone_id,
                    'emission_date' => $date, // Eco2mix seems to provide datetime in
                    'intensity'     => $intensity,
                ]);
            }

            //increment date
            $processing_date->add(new DateInterval('P1D'));
            $progress_bar->advance();
        }
    }

    protected function generateIntensity(DateTime $date, ?float $previous_intensity = null)
    {
        static $intensity_per_hour = [
            '00' => ['min' => 63, 'max' => 80, 'dec' =>  5, 'inc' =>  2],
            '01' => ['min' => 63, 'max' => 80, 'dec' =>  5, 'inc' =>  2],
            '02' => ['min' => 63, 'max' => 80, 'dec' =>  5, 'inc' =>  2],
            '03' => ['min' => 63, 'max' => 80, 'dec' =>  5, 'inc' =>  2],
            '04' => ['min' => 63, 'max' => 70, 'dec' =>  5, 'inc' =>  2],
            '05' => ['min' => 63, 'max' => 80, 'dec' =>  5, 'inc' =>  1],
            '06' => ['min' => 36, 'max' => 50, 'dec' =>  5, 'inc' =>  1],
            '07' => ['min' => 36, 'max' => 50, 'dec' =>  5, 'inc' =>  1],
            '08' => ['min' => 36, 'max' => 60, 'dec' =>  5, 'inc' =>  1],
            '09' => ['min' => 36, 'max' => 45, 'dec' => 15, 'inc' =>  1],
            '10' => ['min' => 18, 'max' => 40, 'dec' => 15, 'inc' =>  3],
            '11' => ['min' => 18, 'max' => 35, 'dec' => 15, 'inc' =>  3],
            '12' => ['min' => 18, 'max' => 30, 'dec' =>  5, 'inc' =>  3],
            '13' => ['min' => 18, 'max' => 35, 'dec' =>  5, 'inc' =>  3],
            '14' => ['min' => 18, 'max' => 35, 'dec' =>  5, 'inc' =>  3],
            '15' => ['min' => 24, 'max' => 30, 'dec' =>  2, 'inc' =>  4],
            '16' => ['min' => 24, 'max' => 40, 'dec' =>  2, 'inc' =>  4],
            '17' => ['min' => 24, 'max' => 50, 'dec' =>  2, 'inc' =>  4],
            '18' => ['min' => 24, 'max' => 50, 'dec' =>  2, 'inc' =>  6],
            '19' => ['min' => 39, 'max' => 60, 'dec' =>  2, 'inc' =>  6],
            '20' => ['min' => 39, 'max' => 60, 'dec' =>  1, 'inc' =>  8],
            '21' => ['min' => 45, 'max' => 70, 'dec' =>  1, 'inc' =>  8],
            '22' => ['min' => 60, 'max' => 80, 'dec' =>  1, 'inc' =>  8],
            '23' => ['min' => 60, 'max' => 80, 'dec' =>  1, 'inc' =>  8],
        ];

        $hour = $date->format('H');

        if ($previous_intensity === null) {
            $intensity = rand($intensity_per_hour[$hour]['min'], $intensity_per_hour[$hour]['max']);
        } else {
            $intensity = rand($previous_intensity - $intensity_per_hour[$hour]['dec'], $previous_intensity + $intensity_per_hour[$hour]['inc']);
        }
        $intensity = max($intensity_per_hour[$hour]['min'], $intensity);
        $intensity = min($intensity_per_hour[$hour]['max'], $intensity);

        return $intensity;
    }
}
