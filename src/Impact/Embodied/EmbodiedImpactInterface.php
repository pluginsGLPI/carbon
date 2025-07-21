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

namespace GlpiPlugin\Carbon\Impact\Embodied;

use CommonDBTM;
use GlpiPlugin\Carbon\Engine\V1\EngineInterface;

interface EmbodiedImpactInterface
{
    /**
     * Get an instance of the impact calculation engine for the itemtype of the analyzed object
     *
     * @param CommonDBTM $item
     * @return EngineInterface
     */
    // public static function getEngine(CommonDBTM $item): EngineInterface;

    /**
     * Get  the itemtype of the asset handled by this class
     *
     * @return string
     */
    public static function getItemtype(): string;

    /**
     * Set the maximum count of items to calculate with evaluateItems()
     *
     * @param integer $limit
     * @return void
     */
    public function setLimit(int $limit);

    /**
     * Get query to find items we can evaluate
     *
     * @param array $crit
     * @param boolean $entity_restrict
     * @return array
     */
    public function getEvaluableQuery(array $crit = [], bool $entity_restrict = true): array;

    /**
     * Start the evaluation of all items
     *
     * @return int count of successfully evaluated assets
     */
    public function evaluateItems(): int;

    /**
     * Evaluate all impacts of the asset
     *
     * @param integer $id
     * @return bool true if success, false otherwise
     */
    public function evaluateItem(int $id): bool;
}
