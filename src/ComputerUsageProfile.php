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
use Computer as GlpiComputer;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use Html;
use MassiveAction;
use Override;
use Session;

/**
 * Usage profile of a computer
 */
class ComputerUsageProfile extends CommonDropdown
{
    #[Override]
    public static function getTypeName($nb = 0)
    {
        return _n("Computer usage profile", "Computer usage profiles", $nb, 'carbon');
    }

    #[Override]
    public static function canView(): bool
    {
        return Entity::canView();
    }

    #[Override]
    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        $new_item = static::isNewID($ID);
        $in_modal = (bool) ($_GET['_in_modal'] ?? false);
        $this->fields['time_start'] ??= '00:00:00';
        $this->fields['time_stop'] ??= '00:00:00';
        TemplateRenderer::getInstance()->display('@carbon/computerusageprofile.html.twig', [
            'item'   => $this,
            'params' => $options,
            'no_header' => !$new_item && !$in_modal,
        ]);
        return true;
    }

    #[Override]
    public function prepareInputForAdd($input)
    {
        if (!$this->inputIntegrityCheck($input)) {
            return [];
        }

        for ($day_id = 0; $day_id < 7; $day_id++) {
            $key = "day_{$day_id}";
            if (!isset($input[$key])) {
                continue;
            }
            $input[$key] = ($input[$key] != 0) ? 1 : 0;
        }

        return $input;
    }

    #[Override]
    public function prepareInputForUpdate($input)
    {
        if (!$this->inputIntegrityCheck($input)) {
            return [];
        }

        for ($day_id = 0; $day_id < 7; $day_id++) {
            $key = "day_{$day_id}";
            if (!isset($input[$key])) {
                continue;
            }
            $input[$key] = ($input[$key] != 0) ? 1 : 0;
        }

        return $input;
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
     * @return bool
     */
    protected function isValidTime(string $time): bool
    {
        $time_pattern = '/^(([01]\d|2[0-3]):[0-5]\d)|(24:00)$/';
        $found = preg_match($time_pattern, $time, $matches);
        return ($found === 1);
    }

    #[Override]
    public function getAdditionalFields()
    {
        return [
            [
                'name'      => 'time_start',
                'type'      => 'dropdownValue',
                'label'     => __('Start time', 'carbon'),
                'list'      => false,
            ],
            [
                'name'      => 'time_stop',
                'type'      => 'parent',
                'label'     => __('As child of'),
                'list'      => false,
            ],
        ];
    }

    #[Override]
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

    #[Override]
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

    #[Override]
    public static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids)
    {
        switch ($ma->getAction()) {
            case 'MassAssociateItems':
                $usage_profile_fk = ComputerUsageProfile::getForeignKeyField();
                $usage_profile_id = (int) $ma->POST[$usage_profile_fk];
                foreach ($ids as $id) {
                    $usage_profile = self::getById($usage_profile_id);
                    if ($usage_profile === false) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        continue;
                    }

                    $computer = GlpiComputer::getById($id);
                    if ($computer === false) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        continue;
                    }

                    if (!$usage_profile->assignToItem($computer)) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        continue;
                    }

                    $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                }
                return;
        }
    }

    /**
     * Assign an usage profile to an item
     *
     * @param CommonDBTM $item A computer to assign to
     * @return bool
     */
    public function assignToItem(CommonDBTM $item): bool
    {
        if ($item->getType() !== GlpiComputer::class) {
            return false;
        }
        $usage_info = new UsageInfo();
        $computers_id = $item->getID();
        $usage_profile_fk = self::getForeignKeyField();
        $usage_info->getFromDBByCrit([
            'itemtype'     => GlpiComputer::class,
            'items_id' => $computers_id,
        ]);
        if ($usage_info->isNewItem()) {
            $usage_info->add([
                'itemtype'     => GlpiComputer::class,
                'items_id' => $computers_id,
                $usage_profile_fk => $this->getID(),
            ]);
            /** @phpstan-ignore booleanNot.alwaysFalse  */
            return !$usage_info->isNewItem();
        }

        return $usage_info->update([
            'id'              => $usage_info->getID(),
            $usage_profile_fk => $this->getID(),
        ]);
    }

    /**
     * Count the days an asset is powered on
     *
     * @return int
     */
    public function countRunningDays(): int
    {
        if ($this->isNewItem()) {
            return 0;
        }
        $days = array_intersect_key($this->fields, array_flip([
            'day_1',
            'day_2',
            'day_3',
            'day_4',
            'day_5',
            'day_6',
            'day_7',
        ]));
        $days_on = 0;
        foreach ($days as $day) {
            if ($day === 0) {
                continue;
            }
            $days_on++;
        }

        return $days_on;
    }

    public function getPoweredOnRatio(): float
    {
        if ($this->isNewItem()) {
            return 0.0;
        }

        // Assume that start and stop times are HH:ii:ss
        $seconds_start = explode(':', $this->fields['time_start']);
        $seconds_stop  = explode(':', $this->fields['time_stop']);
        // Convert to integers
        $seconds_start[0] = (int) $seconds_start[0];
        $seconds_start[1] = (int) $seconds_start[1];
        $seconds_start[2] = 0;
        $seconds_stop[0] = (int) $seconds_stop[0];
        $seconds_stop[1] = (int) $seconds_stop[1];
        $seconds_stop[2] = 0;

        $seconds_start = $seconds_start[0] * 3600
            + $seconds_start[1] * 60
            + $seconds_start[2];
        $seconds_stop = $seconds_stop[0] * 3600
            + $seconds_stop[1] * 60
            + $seconds_stop[2];

        // Count the days the asset is powered on
        $days = $this->countRunningDays();
        $week_ratio = ($days * ($seconds_stop - $seconds_start)) / 604800;

        return $week_ratio;
    }
}
