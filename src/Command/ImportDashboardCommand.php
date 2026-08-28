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

use Glpi\Dashboard\Dashboard as GlpiDashboard;
use Override;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportDashboardCommand extends Command
{
    private OutputInterface $output;

    #[Override]
    protected function configure()
    {
        $this
            ->setName('plugins:carbon:import_report_dashboard')
            ->setDescription('imports the report dashboard description')
            ->setHelp('This command imports the report dashboard description from a JSON file');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $dashboard_key = 'plugin_carbon_board';
        $this->deleteDashboard($dashboard_key);
        $dashboard = new GlpiDashboard();
        $dashboard->add([
            'key'     => $dashboard_key,
            'name'    => __('Environmental impact', 'carbon'),
            'context' => 'mini_core',
        ]);
        if (!$dashboard->resetToDefault($dashboard_key)) {
            $this->output->writeln("<error>Failed to reset the dashboard to default</error>");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function deleteDashboard(string $dashboard_key)
    {
        $dashboard = new GlpiDashboard();
        $dashboard->getFromDB($dashboard_key);
        if ($dashboard->isNewItem()) {
            return;
        }
        $dashboard->delete($dashboard->fields);
    }
}
