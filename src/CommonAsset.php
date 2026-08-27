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

use CommonDBTM;
use Html;
use MassiveAction;
use Override;

abstract class CommonAsset extends CommonDBTM
{
    #[Override]
    public static function showMassiveActionsSubForm(MassiveAction $ma): bool
    {
        switch ($ma->getAction()) {
            case 'MassDeleteAllImpacts':
                echo '<br /><br />' . Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                return true;
        }

        return parent::showMassiveActionsSubForm($ma);
    }

    #[Override]
    public static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids)
    {
        switch ($ma->getAction()) {
            case 'MassDeleteAllImpacts':
                foreach ($ids as $id) {
                    $itemtype = get_class($item);
                    $asset = new $itemtype();
                    $asset->getFromDb($id);
                    if (!$asset->canUpdateItem()) {
                        $ma->itemDone($itemtype, $id, MassiveAction::ACTION_KO);
                        continue;
                    }
                    $success = self::deleteAllImpacts($asset) ? MassiveAction::ACTION_OK : MassiveAction::ACTION_KO;
                    $ma->itemDone($itemtype, $id, $success);
                }
        }
    }

    /**
     * Delete all calculated environmental impacts for the given asset
     *
     * @param CommonDBTM $item
     * @return bool
     */
    public static function deleteAllImpacts(CommonDBTM $item): bool
    {
        $success = true;
        $success = self::deleteEmbodiedImpact($item);
        $success = $success && self::deleteUsageImpact($item);
        return $success;
    }

    /**
     * Delete calculated embodied impact of an asset
     *
     * @param CommonDBTM $item
     * @return bool
     */
    public static function deleteEmbodiedImpact(CommonDBTM $item): bool
    {
        $embodied_impact = new EmbodiedImpact();
        return $embodied_impact->deleteByCriteria([
            'itemtype' => get_class($item),
            'items_id' => $item->getID(),
        ]);
    }

    /**
     * Delete calculated usage impact of an asset
     *
     * @param CommonDBTM $item
     * @return bool
     */
    public static function deleteUsageImpact(CommonDBTM $item): bool
    {
        $gwp_impact_class = '\\GlpiPlugin\\Carbon\\Impact\\History\\' . get_class($item);
        $gwp_impact = new $gwp_impact_class();
        $success = $gwp_impact->resetForItem($item->getID());

        $usage_impact = new UsageImpact();
        $success = $success && $usage_impact->deleteByCriteria([
            'itemtype' => get_class($item),
            'items_id' => $item->getID(),
        ]);

        return $success;
    }
}
