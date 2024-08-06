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
use GlpiPlugin\Carbon\CarbonIntensitySource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

// 6 months

define('DATE_MIN', 'P6M');
define('ECO2MIX_BASE_URL', 'https://eco2mix.rte-france.com/curves/eco2mixWeb?type=co2');

class CollectCarbonIntensityCommand extends Command
{
    /** @var int ID of the data source being processed */
    private int $source_id;

    private InputInterface $input;
    private OutputInterface $output;

    protected function configure()
    {
        $this
           ->setName('plugin:carbon:collect_carbon_intensity')
           ->setDescription("Read carbon dioxyde intensity from external sources");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $message = __("Creating data source name", 'carbon');
        $output->writeln("<info>$message</info>");

        $dataSource = new CarbonIntensitySource();
        $source_name = 'eco2mix';
        if (!$dataSource->getFromDBByCrit(['name' => $source_name])) {
            $dataSource->add([
                'name' => $source_name,
            ]);
        }
        $this->source_id = $dataSource->getID();

        $message = __("Reading eco2mix data...", 'carbon');
        $output->writeln("<info>$message</info>");

        $start_date = new DateTime('now', new DateTimeZone('UTC'));
        $start_date->sub(new DateInterval(DATE_MIN));
        $last_known_date = $this->getLastRecordDate();
        if ($last_known_date !== null) {
            $start_date = max($start_date, $last_known_date);
        }
        $start_date->setTime(0, 0, 0);

        $this->getFromEco2mix($start_date);

        return Command::SUCCESS;
    }

    protected function getFromEco2mix(DateTime $start_date)
    {
        $end_date = new DateTime('now', new DateTimeZone('UTC'));
        $end_date->setTime(0, 0, 0);

        // Initialize progress bar
        $days = (int) $start_date->diff($end_date)->format('%a');
        $progress_bar = new ProgressBar($this->output, $days);

        // iterate day by day from $start_date to $end_date
        $processing_date = clone $start_date;
        while ($processing_date < $end_date) {
            $date = $processing_date->format('d/m/Y');
            $url = ECO2MIX_BASE_URL . '&dateDeb=' . $date . '&dateFin=' . $date . '&mode=NORM&_=1715936961077';

            // Request data
            $data = file_get_contents($url);
            $intensities = $this->parseEco2mixData($data);

            // Save data
            $carbon_intensity = new CarbonIntensity();
            foreach ($intensities as $date => $intensity) {
                $carbon_intensity->add([
                    'plugin_carbon_colletors_carbonintensitysources_id' => $this->source_id,
                    'plugin_carbon_colletors_carbonintensityzones_id' => 1,
                    'date'          => $date, // Eco2mix seems to provide datetime in
                    'intensity'     => $intensity,
                ]);
            }

            //increment date
            $processing_date->add(new DateInterval('P1D'));
            $progress_bar->advance();
        }
    }

    /**
     * sample of eco2mix data
     * <?xml version="1.0" encoding="UTF-8"?>
     * <liste>
     * <date_actuelle>2024-05-17 09:21:09</date_actuelle>
     * <date_debut>2024-05-16</date_debut>
     * <date_fin>2024-05-17</date_fin>
     * <date_consolidee>2023-01-31</date_consolidee>
     * <date_definitive>2022-10-31</date_definitive>
     * <date_minimale_calendrier>2012-01-01</date_minimale_calendrier>
     * <echantillon>1</echantillon>
     * <mixtr date='2024-05-16'>
     * <type v='Taux de Co2' perimetre='France' granularite='Global' qual='1'>
     * <valeur periode='0'>12</valeur>
     * <valeur periode='1'>12</valeur>
     * <valeur periode='2'>12</valeur>
     * <valeur periode='3'>12</valeur>
     * <valeur periode='4'>12</valeur>
     * <valeur periode='5'>12</valeur>
     * <valeur periode='6'>12</valeur>
     * <valeur periode='7'>12</valeur>
     * <valeur periode='8'>12</valeur>
     * <valeur periode='9'>12</valeur>
     * <valeur periode='10'>12</valeur>
     * <valeur periode='11'>12</valeur>
     * <valeur periode='12'>12</valeur>
     * <valeur periode='13'>12</valeur>
     * <valeur periode='14'>12</valeur>
     * <valeur periode='15'>13</valeur>
     * <valeur periode='16'>13</valeur>
     * <valeur periode='17'>13</valeur>
     * <valeur periode='18'>13</valeur>
     * <valeur periode='19'>13</valeur>
     * <valeur periode='20'>13</valeur>
     * <valeur periode='21'>13</valeur>
     * <valeur periode='22'>13</valeur>
     * <valeur periode='23'>13</valeur>
     * <valeur periode='24'>13</valeur>
     * <valeur periode='25'>12</valeur>
     * <valeur periode='26'>12</valeur>
     * <valeur periode='27'>12</valeur>
     * <valeur periode='28'>12</valeur>
     * <valeur periode='29'>12</valeur>
     * <valeur periode='30'>12</valeur>
     * <valeur periode='31'>12</valeur>
     * <valeur periode='32'>12</valeur>
     * <valeur periode='33'>12</valeur>
     * <valeur periode='34'>11</valeur>
     * <valeur periode='35'>11</valeur>
     * <valeur periode='36'>11</valeur>
     * <valeur periode='37'>12</valeur>
     * <valeur periode='38'>12</valeur>
     * <valeur periode='39'>12</valeur>
     * <valeur periode='40'>12</valeur>
     * <valeur periode='41'>12</valeur>
     * <valeur periode='42'>12</valeur>
     * <valeur periode='43'>12</valeur>
     * <valeur periode='44'>12</valeur>
     * <valeur periode='45'>12</valeur>
     * <valeur periode='46'>12</valeur>
     * <valeur periode='47'>12</valeur>
     * <valeur periode='48'>12</valeur>
     * <valeur periode='49'>13</valeur>
     * <valeur periode='50'>13</valeur>
     * <valeur periode='51'>13</valeur>
     * <valeur periode='52'>13</valeur>
     * <valeur periode='53'>13</valeur>
     * <valeur periode='54'>13</valeur>
     * <valeur periode='55'>13</valeur>
     * <valeur periode='56'>13</valeur>
     * <valeur periode='57'>13</valeur>
     * <valeur periode='58'>13</valeur>
     * <valeur periode='59'>13</valeur>
     * <valeur periode='60'>13</valeur>
     * <valeur periode='61'>13</valeur>
     * <valeur periode='62'>15</valeur>
     * <valeur periode='63'>15</valeur>
     * <valeur periode='64'>14</valeur>
     * <valeur periode='65'>15</valeur>
     * <valeur periode='66'>15</valeur>
     * <valeur periode='67'>15</valeur>
     * <valeur periode='68'>15</valeur>
     * <valeur periode='69'>15</valeur>
     * <valeur periode='70'>15</valeur>
     * <valeur periode='71'>14</valeur>
     * <valeur periode='72'>14</valeur>
     * <valeur periode='73'>16</valeur>
     * <valeur periode='74'>16</valeur>
     * <valeur periode='75'>15</valeur>
     * <valeur periode='76'>15</valeur>
     * <valeur periode='77'>15</valeur>
     * <valeur periode='78'>15</valeur>
     * <valeur periode='79'>15</valeur>
     * <valeur periode='80'>15</valeur>
     * <valeur periode='81'>15</valeur>
     * <valeur periode='82'>15</valeur>
     * <valeur periode='83'>15</valeur>
     * <valeur periode='84'>15</valeur>
     * <valeur periode='85'>15</valeur>
     * <valeur periode='86'>15</valeur>
     * <valeur periode='87'>15</valeur>
     * <valeur periode='88'>16</valeur>
     * <valeur periode='89'>14</valeur>
     * <valeur periode='90'>13</valeur>
     * <valeur periode='91'>13</valeur>
     * <valeur periode='92'>13</valeur>
     * <valeur periode='93'>12</valeur>
     * <valeur periode='94'>12</valeur>
     * <valeur periode='95'>13</valeur>
     * </type>
     * </mixtr>
     * <mixtr date='2024-05-17'>
     * <type v='Taux de Co2' perimetre='France' granularite='Global' qual='1'>
     * <valeur periode='0'>12</valeur>
     * <valeur periode='1'>12</valeur>
     * <valeur periode='2'>12</valeur>
     * <valeur periode='3'>13</valeur>
     * <valeur periode='4'>13</valeur>
     * <valeur periode='5'>13</valeur>
     * <valeur periode='6'>13</valeur>
     * <valeur periode='7'>13</valeur>
     * <valeur periode='8'>13</valeur>
     * <valeur periode='9'>13</valeur>
     * <valeur periode='10'>13</valeur>
     * <valeur periode='11'>13</valeur>
     * <valeur periode='12'>13</valeur>
     * <valeur periode='13'>13</valeur>
     * <valeur periode='14'>13</valeur>
     * <valeur periode='15'>13</valeur>
     * <valeur periode='16'>13</valeur>
     * <valeur periode='17'>13</valeur>
     * <valeur periode='18'>13</valeur>
     * <valeur periode='19'>13</valeur>
     * <valeur periode='20'>13</valeur>
     * <valeur periode='21'>13</valeur>
     * <valeur periode='22'>13</valeur>
     * <valeur periode='23'>13</valeur>
     * <valeur periode='24'>13</valeur>
     * <valeur periode='25'>12</valeur>
     * <valeur periode='26'>12</valeur>
     * <valeur periode='27'>12</valeur>
     * <valeur periode='28'>12</valeur>
     * <valeur periode='29'>12</valeur>
     * <valeur periode='30'>12</valeur>
     * <valeur periode='31'>12</valeur>
     * <valeur periode='32'>12</valeur>
     * <valeur periode='33'>12</valeur>
     * <valeur periode='34'>11</valeur>
     * <valeur periode='35'>11</valeur>
     * <valeur periode='36'>11</valeur>
     * <valeur periode='37'>11</valeur>
     * <valeur periode='38'>11</valeur>
     * <valeur periode='39'>11</valeur>
     * <valeur periode='40'>11</valeur>
     * <valeur periode='41'>11</valeur>
     * <valeur periode='42'>12</valeur>
     * <valeur periode='43'>12</valeur>
     * <valeur periode='44'>11</valeur>
     * </type>
     * </mixtr>
     * </liste>
     */

    /**
     * Undocumented function
     *
     * @param string $data
     * @return array
     */
    protected function parseEco2mixData(string $data): array
    {
        $intensities = [];
        $xml = simplexml_load_string($data);
        $date = new DateTime((string) $xml->mixtr['date'], new DateTimeZone('UTC'));
        $values = $xml->mixtr->type;
        // 4 samples per hour (total 96 samples per 24 day)
        $intensity = 0;
        foreach ($values->valeur as $value) {
            // Calculate date from period
            $period =  (int) $value['periode'];
            if ($period % 4 === 0) {
                $intensity = (float) $value;
            } else {
                $intensity += (float) $value;
                if ($period % 4 === 3) {
                    $date->setTime($period / 4, 0, 0);
                    $intensities[$date->format('Y-m-d H:i:s')] = $intensity / 4;
                }
            }
        }

        return $intensities;
    }

    /**
     * Retrieves the last record date based on the provided source ID.
     *
     */
    protected function getLastRecordDate(): ?DateTime
    {
        $carbon_intensity = new CarbonIntensity();
        $rows = $carbon_intensity->find(['plugin_carbon_colletors_carbonintensitysources_id' => $this->source_id], ['date DESC'], 1);
        if (count($rows) === 0) {
            return null;
        }
        $row = array_pop($rows);
        return new DateTime($row['date'], new DateTimeZone('UTC'));
    }
}
