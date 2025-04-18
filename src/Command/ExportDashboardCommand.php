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
            ->setName('plugin:carbon:export_report_dashboard')
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
        /** @phpstan-ignore argument.type */
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
