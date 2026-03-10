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
use DBmysqlIterator;

interface EmbodiedImpactInterface
{
    /**
     * Set the maximum count of items to calculate with evaluateItems()
     *
     * @param int $limit
     * @return void
     */
    public function setLimit(int $limit);

    /**
     * Get query to find items we can evaluate
     *
     * @template T of CommonDBTM
     * @param class-string<T> $itemtype
     * @param array $crit
     * @param bool $entity_restrict
     * @return array
     */
    public static function getEvaluableQuery(string $itemtype, array $crit = [], bool $entity_restrict = true): array;

    /**
     * Get an iterator of items to evaluate
     *
     * @template T of CommonDBTM
     * @param class-string<T> $itemtype
     * @param array $crit criterias
     * @return DBmysqlIterator
     */
    public static function getItemsToEvaluate(string $itemtype, array $crit = []): DBmysqlIterator;

    /**
     * Evaluate and save tne environmental impact of an asset
     *
     * @return bool true if success, false otherwise
     */
    public function evaluateItem(): bool;
}
