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
use CommonDropdown;
use CommonGLPI;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use Html;
use MassiveAction;

/**
 * Usage profile of a computer
 */
class ComputerUsageProfile extends CommonDropdown
{
    public static function getTypeName($nb = 0)
    {
        return _n("Computer usage profile", "Computer usage profiles", $nb, 'carbon');
    }

    public static function canView()
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

    public function getAdditionalFields()
    {
        return [
            [
                'name'      => 'time_start',
                'type'      => 'dropdownValue',
                'label'     => __('Knowbase category', 'formcreator'),
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
            'id'                 => '6',
            'table'              => $my_table,
            'field'              => 'time_start',
            'name'               => __('Start time', 'carbon'),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => $my_table,
            'field'              => 'time_stop',
            'name'               => __('Stop time', 'carbon'),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '201',
            'table'              => $my_table,
            'field'              => 'day_1',
            'name'               => __('Monday'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '202',
            'table'              => $my_table,
            'field'              => 'day_2',
            'name'               => __('Tuesday'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '203',
            'table'              => $my_table,
            'field'              => 'day_3',
            'name'               => __('Wednesday'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '204',
            'table'              => $my_table,
            'field'              => 'day_4',
            'name'               => __('Thursday'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '205',
            'table'              => $my_table,
            'field'              => 'day_5',
            'name'               => __('Friday'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '206',
            'table'              => $my_table,
            'field'              => 'day_6',
            'name'               => __('Saturday'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '207',
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

    public static function assignToItem(CommonDBTM $item, int $usage_profile_id)
    {
        $environmental_imapct = new EnvironnementalImpact();
        $computers_id = $item->getID();
        $usage_profile_fk = ComputerUsageProfile::getForeignKeyField();
        $environmental_imapct->getFromDBByCrit([
            'computers_id' => $computers_id
        ]);
        if ($environmental_imapct->isNewItem()) {
            $environmental_imapct->add([
                'computers_id'    => $computers_id,
                $usage_profile_fk => $usage_profile_id,
            ]);
        } else {
            $environmental_imapct->update([
                'id'              => $environmental_imapct->getID(),
                $usage_profile_fk => $usage_profile_id,
            ]);
        }
    }
}
