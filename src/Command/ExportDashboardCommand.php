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

use DBmysql;
use DbUtils;
use Glpi\Dashboard\Dashboard;
use Glpi\Dashboard\Item;
use Plugin;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportDashboardCommand extends Command
{
    private OutputInterface $output;

    private string $output_path;

    private array $dashboard_description = [];

    protected function configure()
    {
        $this
            ->setName('plugins:carbon:export_report_dashboard')
            ->setDescription('exports the report dashboard description')
            ->setHelp('This command exports the report dashboard description to a JSON file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var DBmysql $DB */
        global $DB;

        $this->output_path = Plugin::getPhpDir('carbon') . '/install/data/report_dashboard.json';
        $this->output = $output;

        $message = __('Updating the report dashboard description', 'carbon');
        $this->output->writeln("<info>$message</info>");

        $dashboard = new Dashboard();
        if (!$dashboard->getFromDB('plugin_carbon_board')) {
            $message = __('Dashboard not found', 'carbon');
            $this->output->writeln("<error>$message</error>");
            return Command::FAILURE;
        }

        $item_table = Item::getTable();
        $iterator = $DB->request([
            'FROM' => $item_table,
            'WHERE' => [
                'dashboards_dashboards_id' => $dashboard->fields['id'],
            ],
        ]);

        $db_utils = new DbUtils();
        foreach ($iterator as $row) {
            $key = $row['card_id'];
            unset($row['id'], $row['gridstack_id'], $row['card_id'], $row['dashboards_dashboards_id']);
            $row['card_options'] = $db_utils->importArrayFromDB($row['card_options']);
            // remove UUID from gridstack_id, using regex
            $this->dashboard_description[$key] = $row;
        }

        file_put_contents(
            $this->output_path,
            json_encode($this->dashboard_description, JSON_PRETTY_PRINT)
        );
        $message = sprintf(__('Dashboard description saved to %s', 'carbon'), $this->output_path);
        $this->output->writeln("<info>$message</info>");

        return Command::SUCCESS;
    }
}
