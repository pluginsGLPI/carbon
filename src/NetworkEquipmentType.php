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

namespace GlpiPlugin\Carbon;

use CommonDBTM;
use Html;
use MassiveAction;
use NetworkEquipmentType as GlpiNetworkEquipmentType;

class NetworkEquipmentType extends AbstractType
{
    public static $itemtype = GlpiNetworkEquipmentType::class;
    public static $items_id = 'networkequipmenttypes_id';

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
     * Update the power consumption associated to a net equipment type
     *
     * @param CommonDBTM $item Monitor to update
     * @param integer $power pwoer consumption to set
     * @return bool
     */
    public static function updatePowerConsumption(CommonDBTM $item, int $power)
    {
        $monitor_type = new NetworkEquipmentType();
        $core_monitor_type_id = $item->getID();
        $id = $monitor_type->getFromDBByCrit([
            'networkequipmenttypes_id' => $core_monitor_type_id,
        ]);
        if ($monitor_type->isNewItem()) {
            $id = $monitor_type->add([
                'networkequipmenttypes_id'  => $core_monitor_type_id,
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
