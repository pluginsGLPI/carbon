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

namespace GlpiPlugin\Carbon;

use Override;

/**
 * Embodied impact of assets
 *
 * Embodied impact is the impact of the asset while it is built and destroyed or recycled
 */
class EmbodiedImpact extends AbstractImpact
{
    #[Override]
    public static function getTypeName($nb = 0)
    {
        return _n("Embodied impact", "Embodied impacts", $nb, 'carbon');
    }

    public function calculateImpact(string $lca_type, int $limit = 0): int
    {
        $crit = [];
        if ($limit > 0) {
            $crit['LIMIT'] = $limit;
        }
        $iterator = self::getItemsToEvaluate($lca_type::getItemtype(), $crit);
        $count = 0;
        foreach ($iterator as $item) {
            $lca = new $lca_type($item['id']);
            $lca::calculate($item);
            $count++;
        }

        return $iterator->count();
    }
}
