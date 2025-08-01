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

use DateTimeImmutable;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\Zone;
use GlpiPlugin\Carbon\DataSource\CarbonIntensityRTE;
use GlpiPlugin\Carbon\DataSource\RestApiClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

// 6 months

define('DATE_MIN', 'P6M');

class CollectCarbonIntensityCommand extends Command
{
    /** @var int ID of the data source being processed */
    private int $source_id;

    private OutputInterface $output;

    protected function configure()
    {
        $this
           ->setName('plugin:carbon:collect_carbon_intensity')
           ->setDescription("Read carbon dioxyde intensity from external sources");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $message = __("Creating data source name", 'carbon');
        $output->writeln("<info>$message</info>");

        // Create source if not exists
        $data_source = new CarbonIntensitySource();
        $source_name = 'RTE';
        if (!$data_source->getFromDBByCrit(['name' => $source_name])) {
            $data_source->add([
                'name' => $source_name,
            ]);
        }
        $this->source_id = $data_source->getID();

        $zone_name = 'France';
        $zone = new Zone();
        $zone->getFromDBByCrit(['name' => $zone_name]);
        if (!$zone->getID()) {
            $message = __("Zone not found", 'carbon');
            $output->writeln("<error>$message</error>");
            return Command::FAILURE;
        }
        $carbon_intensity = new CarbonIntensity();

        $message = __("Reading eco2mix data...", 'carbon');
        $output->writeln("<info>$message</info>");

        $downloader = new CarbonIntensityRTE(new RestApiClient([]));

        $carbon_intensity->downloadOneZone($downloader, $zone_name, 0, new ProgressBar($this->output));

        $start_date = $carbon_intensity->getDownloadStartDate($zone_name, $downloader);
        $gaps = $carbon_intensity->findGaps($this->source_id, $zone->getID(), $start_date);
        $not_downlaoded_hours = 0;
        foreach ($gaps as $gap) {
            $gap_start = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $gap['start']);
            $gap_end = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $gap['end']);
            $diff = $gap_start->diff($gap_end);
            $not_downlaoded_hours += $diff->days * 24 + $diff->h;
        }

        // Show message if some hours were not downloaded
        if ($not_downlaoded_hours > 0) {
            $message = __("$not_downlaoded_hours hours were not downloaded", 'carbon');
            $output->writeln("<info>$message</info>");
        }

        return Command::SUCCESS;
    }
}
