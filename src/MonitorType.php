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
use MonitorType as GlpiMonitorType;
use MassiveAction;

class MonitorType extends AbstractType
{
    public static $itemtype = GlpiMonitorType::class;
    public static $items_id = 'monitortypes_id';

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        switch ($ma->getAction()) {
            case 'MassUpdatePower':
                echo '<div>';
                echo __('Power consumption', 'carbon') . '&nbsp;';
                echo Html::input('power_consumption', ['type' => 'number']);
                echo '</div>';
                echo '<br /><br />' . Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                return true;
        }

        return parent::showMassiveActionsSubForm($ma);
    }


    public static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids)
    {
        switch ($ma->getAction()) {
            case 'MassUpdatePower':
                foreach ($ids as $id) {
                    if ($item->getFromDB($id) && self::updatePowerConsumption($item, $ma->POST['power_consumption'])) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                    } else {
                        // Example of ko count
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                    }
                }
                return;
        }
    }

    /**
     * Update the power consumption associated to a monitor type
     *
     * @param CommonDBTM $item Monitor to update
     * @param integer $power pwoer consumption to set
     * @return bool
     */
    public static function updatePowerConsumption(CommonDBTM $item, int $power)
    {
        $monitor_type = new MonitorType();
        $core_monitor_type_id = $item->getID();
        $id = $monitor_type->getFromDBByCrit([
            'computertypes_id' => $core_monitor_type_id,
        ]);
        if ($monitor_type->isNewItem()) {
            $id = $monitor_type->add([
                'monitortypes_id'  => $core_monitor_type_id,
                'power_consumption' => $power,
            ]);
            return !$monitor_type->isNewId($id);
        } else {
            return $monitor_type->update([
                'id'                => $id,
                'power_consumption' => $power,
            ]);
        }
    }
}
