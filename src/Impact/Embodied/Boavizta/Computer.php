<?php

/**
 * -------------------------------------------------------------------------
 * Carbon plugin for GLPI
 *
 * @copyright Copyright (C) 2024-2025 Teclib' and contributors.
 * @copyright Copyright (C) 2024 by the carbon plugin team.
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

namespace GlpiPlugin\Carbon\Impact\Embodied\Boavizta;

use Computer as GlpiComputer;
use GlpiPlugin\Carbon\DataSource\Lca\Boaviztapi\ComputerModelizationAdapterTrait;
use GlpiPlugin\Cloudinventory\CloudInstance;
use Override;

class Computer extends AbstractAsset
{
    use ComputerModelizationAdapterTrait;

    protected static string $itemtype = GlpiComputer::class;

    protected string $endpoint        = 'server';

    /**
     * If the plugin CloudInventory is available, this is an object from that
     * plugin representing the cloud related data of the computer
     */
    protected ?CloudInstance $cloud_instance = null;

    /**
     * @var array Description of the asset for querying Boaviztapi
     */
    protected array $description = [];

    #[Override]
    protected function doEvaluation(): ?array
    {
        $type = $this->getType($this->item);

        $response = null;
        $this->chooseEvaluationMode($type);

        // select all impact types
        $this->endpoint .= '?' . $this->getCriteriasQueryString();

        // Query Boaviztapi
        $response = $this->query($this->description);

        $impacts = $this->client->parseResponse($response, 'embedded');
        return $impacts;
    }
}
