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
use ComputerType as GlpiComputerType;
use Dropdown;
use Html;
use MassiveAction;

class ComputerType extends AbstractType
{
    public static $itemtype = GlpiComputerType::class;
    public static $items_id = 'computertypes_id';

    const CATEGORY_UNDEFINED  = 0;
    const CATEGORY_DESKTOP    = 1;
    const CATEGORY_SERVER     = 2;
    const CATEGORY_LAPTOP     = 3;
    const CATEGORY_TABLET     = 4;
    const CATEGORY_SMARTPHONE = 5;

    public static function getCategories(): array
    {
        return [
            self::CATEGORY_UNDEFINED  => __('Unspecified', 'carbon'),
            self::CATEGORY_DESKTOP    => _n('Computer', 'Computers', 1),
            self::CATEGORY_SERVER     => __('Server', 'carbon'),
            self::CATEGORY_LAPTOP     => __('Laptop', 'carbon'),
            self::CATEGORY_TABLET     => __('Tablet', 'carbon'),
            self::CATEGORY_SMARTPHONE => __('Smartphone', 'carbon'),
        ];
    }

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

            case 'MassUpdateCategory':
                echo '<div>';
                echo __('Category', 'carbon') . '&nbsp;';
                self::dropdownType('category');
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
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                    }
                }
                return;

            case 'MassUpdateCategory':
                foreach ($ids as $id) {
                    if ($item->getFromDB($id) && self::updateCategory($item, $ma->POST['category'])) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                    }
                }
                return;
        }
    }

    /**
     * Update the power consumption associated to a computer type
     *
     * @param CommonDBTM $item Computer to update
     * @param integer $power pwoer consumption to set
     * @return bool
     */
    public static function updatePowerConsumption(CommonDBTM $item, int $power): bool
    {
        $computer_type = new self();
        $core_computer_type_id = $item->getID();
        $computer_type->getFromDBByCrit([
            'computertypes_id' => $core_computer_type_id,
        ]);
        if ($computer_type->isNewItem()) {
            $id = $computer_type->add([
                'computertypes_id'  => $core_computer_type_id,
                'power_consumption' => $power,
            ]);
            return !$computer_type->isNewId($id);
        } else {
            return $computer_type->update([
                'id'                => $computer_type->getID(),
                'power_consumption' => $power,
            ]);
        }
    }

    /**
     * Update the category of a computer
     *
     * @param CommonDBTM $item Computer to update
     * @param integer $category pwoer consumption to set
     * @return bool
     */
    public static function updateCategory(CommonDBTM $item, int $category): bool
    {
        $computer_type = new self();
        $core_computer_type_id = $item->getID();
        $computer_type->getFromDBByCrit([
            'computertypes_id' => $core_computer_type_id,
        ]);
        if ($computer_type->isNewItem()) {
            $id = $computer_type->add([
                'computertypes_id'  => $core_computer_type_id,
                'category' => $category,
            ]);
            return !$computer_type->isNewId($id);
        } else {
            return $computer_type->update([
                'id'                => $computer_type->getID(),
                'category' => $category,
            ]);
        }
    }

    /**
     * Show or return HTML code displaying a dropdown of computer types
     * @see constants TYPE_*
     *
     * @param string $name
     * @param array $options
     * @return integer|string
     */
    public static function dropdownType(string $name, array $options = [])
    {
        $items = self::getCategories();
        return Dropdown::showFromArray($name, $items, $options);
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        switch ($field) {
            case 'category':
                $categories = self::getCategories();
                return $categories[$values['category']] ?? '';
        }

        return '';
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        $options['values'] = $values;
        return self::dropdownType($name, $options);
    }
}
