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
use CommonDropdown;
use CommonGLPI;
use Computer as GlpiComputer;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use Html;
use MassiveAction;
use Session;

/**
 * Usage profile of a computer
 */
class ComputerUsageProfile extends CommonDropdown
{
    public static function getTypeName($nb = 0)
    {
        return _n("Computer usage profile", "Computer usage profiles", $nb, 'carbon');
    }

    public static function canView(): bool
    {
        return Entity::canView();
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $env       = new self();
        /** @var \CommonDBTM $item */
        $found_env = $env->find([static::getForeignKeyField() => $item->getID()]);
        $nb        = $_SESSION['glpishow_count_on_tabs'] ? count($found_env) : 0;
        return self::createTabEntry(self::getTypeName($nb), $nb);
    }

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        $new_item = static::isNewID($ID);
        $in_modal = (bool) ($_GET['_in_modal'] ?? false);
        TemplateRenderer::getInstance()->display('@carbon/computerusageprofile.html.twig', [
            'item'   => $this,
            'params' => $options,
            'no_header' => !$new_item && !$in_modal
        ]);
        return true;
    }

    public function prepareInputForAdd($input)
    {
        return $this->inputIntegrityCheck($input);
    }

    public function prepareInputForUpdate($input)
    {
        return $this->inputIntegrityCheck($input);
    }

    /**
     * Check integrity of fields
     *
     * @param array $input
     * @return array
     */
    protected function inputIntegrityCheck(array $input): array
    {
        if (isset($input['time_start']) && !$this->isValidTime($input['time_start'])) {
            Session::addMessageAfterRedirect(__('Start time is invalid', 'carbon'), true, ERROR);
            return [];
        }

        if (isset($input['time_stop']) && !$this->isValidTime($input['time_stop'])) {
            Session::addMessageAfterRedirect(__('Stop time is invalid', 'carbon'), true, ERROR);
            return [];
        }

        return $input;
    }

    /**
     * Check format of time string against HH:MM:SS pattern
     *
     * @param string $time
     * @return boolean
     */
    protected function isValidTime(string $time): bool
    {
        $time_pattern = '/^([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/';
        $found = preg_match($time_pattern, $time, $matches);
        return ($found === 1);
    }

    public function getAdditionalFields()
    {
        return [
            [
                'name'      => 'time_start',
                'type'      => 'dropdownValue',
                'label'     => __('Start time', 'carbon'),
                'list'      => false
            ],
            [
                'name'      => 'time_stop',
                'type'      => 'parent',
                'label'     => __('As child of'),
                'list'      => false
            ]
        ];
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();
        $my_table = self::getTable();

        $tab[] = [
            'id'                 => SearchOptions::COMPUTER_USAGE_PROFILE_START_TIME,
            'table'              => $my_table,
            'field'              => 'time_start',
            'name'               => __('Start time', 'carbon'),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => SearchOptions::COMPUTER_USAGE_PROFILE_STOP_TIME,
            'table'              => $my_table,
            'field'              => 'time_stop',
            'name'               => __('Stop time', 'carbon'),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => SearchOptions::COMPUTER_USAGE_PROFILE_DAY_1,
            'table'              => $my_table,
            'field'              => 'day_1',
            'name'               => __('Monday'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => SearchOptions::COMPUTER_USAGE_PROFILE_DAY_2,
            'table'              => $my_table,
            'field'              => 'day_2',
            'name'               => __('Tuesday'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => SearchOptions::COMPUTER_USAGE_PROFILE_DAY_3,
            'table'              => $my_table,
            'field'              => 'day_3',
            'name'               => __('Wednesday'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => SearchOptions::COMPUTER_USAGE_PROFILE_DAY_4,
            'table'              => $my_table,
            'field'              => 'day_4',
            'name'               => __('Thursday'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => SearchOptions::COMPUTER_USAGE_PROFILE_DAY_5,
            'table'              => $my_table,
            'field'              => 'day_5',
            'name'               => __('Friday'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => SearchOptions::COMPUTER_USAGE_PROFILE_DAY_6,
            'table'              => $my_table,
            'field'              => 'day_6',
            'name'               => __('Saturday'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => SearchOptions::COMPUTER_USAGE_PROFILE_DAY_7,
            'table'              => $my_table,
            'field'              => 'day_7',
            'name'               => __('Sunday'),
            'datatype'           => 'bool',
        ];

        return $tab;
    }

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        switch ($ma->getAction()) {
            case 'MassAssociateItems':
                self::dropdown();
                echo '<br /><br />' . Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                return true;
        }

        return parent::showMassiveActionsSubForm($ma);
    }

    public static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids)
    {
        switch ($ma->getAction()) {
            case 'MassAssociateItems':
                $usage_profile_fk = ComputerUsageProfile::getForeignKeyField();
                $usage_profile_id = $ma->POST[$usage_profile_fk];
                foreach ($ids as $id) {
                    if ($item->getFromDB($id) && self::assignToItem($item, $usage_profile_id)) {
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
     * Assign an usage profile to an item
     *
     * @param CommonDBTM $item A computer to assign to
     * @param integer $usage_profile_id usage profile to assign
     * @return bool
     */
    public static function assignToItem(CommonDBTM $item, int $usage_profile_id): bool
    {
        $usage_info = new UsageInfo();
        $computers_id = $item->getID();
        $usage_profile_fk = ComputerUsageProfile::getForeignKeyField();
        $usage_info->getFromDBByCrit([
            'itemtype'     => GlpiComputer::class,
            'items_id' => $computers_id,
        ]);
        if ($usage_info->isNewItem()) {
            $usage_info->add([
                'itemtype'     => GlpiComputer::class,
                'items_id' => $computers_id,
                $usage_profile_fk => $usage_profile_id,
            ]);
            return true;
        }

        return $usage_info->update([
            'id'              => $usage_info->getID(),
            $usage_profile_fk => $usage_profile_id,
        ]);
    }
}
