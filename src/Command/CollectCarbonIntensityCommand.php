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

use Config as GlpiConfig;
use DateTimeImmutable;
use Glpi\Console\AbstractCommand;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\DataSource\CarbonIntensity\ClientFactory;
use GlpiPlugin\Carbon\DataSource\CarbonIntensity\ClientInterface;
use GlpiPlugin\Carbon\Source;
use GlpiPlugin\Carbon\Source_Zone;
use GlpiPlugin\Carbon\Zone;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

// 6 months

define('DATE_MIN', 'P6M');

class CollectCarbonIntensityCommand extends AbstractCommand
{
    /** @var int ID of the data source being processed */
    /** @var Source_Zone The relatin between a source and a zone to describe which data to download and save */
    private Source_Zone $source_zone;
    private ?ClientInterface $client = null;
    private array $zones = [];

    protected function configure()
    {
        $this
           ->setName('plugins:carbon:collect_carbon_intensity')
           ->setDescription(__('Read carbon dioxyde intensity from external sources', 'carbon'))
           ->addArgument('source', InputArgument::REQUIRED, '')
           ->addArgument('zone', InputArgument::REQUIRED, '')
           ->addOption('cache', null, InputOption::VALUE_NEGATABLE, 'Use cache. Cache is not read is disabled, but still fed by requests.')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        // Set data source argument if not provided
        if (is_null($input->getArgument('source'))) {
            $question_helper = new QuestionHelper();
            $choices = (new Source())->getDownloadableSources();
            $choices = ClientFactory::getClientNames();
            $question = new ChoiceQuestion(__('Source:', 'carbon'), array_values($choices));
            $value = $question_helper->ask($input, $output, $question);
            $input->setArgument('source', $value);
        }

        // Set data zone argument if not provided
        if (is_null($input->getArgument('zone'))) {
            $question_helper = new QuestionHelper();
            $this->zones = $this->getSupportedZones($input->getArgument('source'));
            if (count($this->zones) === 0) {
                // Try to find a zone from an address
                $message = __('The selected source does not enumerates its supported zones. Trying to identify a zone from an address', 'carbon');
                $output->writeln("<info>$message</info>");
                $enabled = GlpiConfig::getConfigurationValue('plugin:carbon', 'geocoding_enabled');
                if ($enabled != '1') {
                    $message = __('Geocoding is not enabled. Cannot resolve an address into a zone', 'carbon');
                    $output->writeln("<error>$message</error>");
                    return;
                }
                $question_helper = new QuestionHelper();
                $question = new Question(__('Address:', 'carbon'));
                $value = $question_helper->ask($input, $output, $question);
            } elseif (count($this->zones) > 1) {
                $question = new ChoiceQuestion(__('Zone:', 'carbon'), $this->zones);
                $value = $question_helper->ask($input, $output, $question);
                $input->setArgument('zone', $value);
            } else {
                $input->setArgument('zone', null);
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (count($this->zones) > 1 && $input->getArgument('zone') === null) {
            // Null is not a valid key if there are several zones available for the source
            return Command::FAILURE;
        }

        $message = __('Creating data source name', 'carbon');
        $output->writeln("<info>$message</info>");

        // Check the source exists
        $data_source = new Source();
        $source_name = $input->getArgument('source');
        if (!$data_source->getFromDBByCrit(['name' => $source_name])) {
            $message = __("This source does not exist", 'casrbon');
            $output->writeln("<error>$message</error>");
            return Command::FAILURE;
        }

        $zone_code = $input->getArgument('zone');
        $use_cache = $input->getOption('cache');
        $zone = new Zone();
        $zone->getFromDBByCrit(['name' => $this->zones[$zone_code]]);
        $carbon_intensity = new CarbonIntensity();

        // Check the relation between source and zone
        $source_zone = new Source_Zone();
        $input = [
            $data_source::getForeignKeyField() => $data_source->getID(),
            $zone::getForeignKeyField() => $zone->getID(),
        ];
        $source_zone->getFromDbByCrit($input);
        if ($source_zone->isNewItem()) {
            $message = __("The zone is not handled by the data source", 'casrbon');
            $output->writeln("<error>$message</error>");
            return Command::FAILURE;
        }
        $this->source_zone = $source_zone;

        $message = __("Reading data...", 'carbon');
        $output->writeln("<info>$message</info>");

        // Create the client
        // May be created when asking some questions
        if ($this->client === null) {
            $this->client = ClientFactory::createByName($source_name);
        }
        if ($use_cache === false) {
            $this->client->disableCache();
        }

        $carbon_intensity->downloadOneZone($this->client, $this->source_zone, 0, new ProgressBar($this->output));

        // Find start and stop dates to cover
        $start_date = $carbon_intensity->getDownloadStartDate();
        $gaps = $carbon_intensity->findGaps($this->source_zone, $start_date);

        // Count the hours not covered by any sample
        $not_downlaoded_hours = 0;
        foreach ($gaps as $gap) {
            $gap_start = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $gap['start']);
            $gap_end = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $gap['end']);
            $diff = $gap_start->diff($gap_end);
            $not_downlaoded_hours += $diff->days * 24 + $diff->h;
        }

        // Show message if there are gaps
        if ($not_downlaoded_hours > 0) {
            $message = __("$not_downlaoded_hours hours were not downloaded", 'carbon');
            $output->writeln("<info>$message</info>");
        }

        return Command::SUCCESS;
    }

    protected function getSupportedZones(string $source_name)
    {
        if ($this->client === null) {
            $this->client = ClientFactory::createByName($source_name);
        }
        return $this->client->getSupportedZones();
    }
}
