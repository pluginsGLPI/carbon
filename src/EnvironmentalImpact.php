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

use Computer;
use CommonDBChild;
use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;
use Monitor;
use NetworkEquipment;

/**
 * Relation between a computer and a usage profile
 */
class EnvironmentalImpact extends CommonDBChild
{
    public static $itemtype = Computer::class;
    public static $items_id = 'computers_id';

    // Use core computer right
    public static $rightname = 'computer';

    public static function getTypeName($nb = 0)
    {
        return __('Environnemental impact', 'Carbon');
    }

    public static function getIcon()
    {
        return 'fa-solid fa-solar-panel';
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $tabNames = [];
        if (!$withtemplate) {
            if ($item->getType() == Computer::class) {
                $tabNames[1] = self::getTypeName();
            } else if ($item->getType() == Monitor::class) {
                $tabNames[1] = self::getTypeName();
            } else if ($item->getType() == NetworkEquipment::class) {
                $tabNames[1] = self::getTypeName();
            }
        }
        return $tabNames;
    }

    /**
     * Undocumented function
     *
     * @param CommonGLPI $item
     * @param integer $tabnum
     * @param integer $withtemplate
     * @return void
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        /** @var Computer $item */
        if ($item->getType() == Computer::class) {
            $environnementalImpact = new self();
            $environnementalImpact->getFromDBByCrit([$item->getForeignKeyField() => $item->getID()]);
            if ($environnementalImpact->isNewItem()) {
                $environnementalImpact->add(
                    [
                        $item->getForeignKeyField() => $item->getID()
                    ],
                    [],
                    false
                );
            }
            $environnementalImpact->showForComputer($environnementalImpact->getID());
        } else if ($item->getType() == Monitor::class) {
            $environnementalImpact = new self();
            $environnementalImpact->showForMonitor(0);
        } else if ($item->getType() == NetworkEquipment::class) {
            $environnementalImpact = new self();
            $environnementalImpact->showForNetworkEquipment(0);
        }
    }

    public function post_updateItem($history = true)
    {
        parent::post_updateItem($history);

        if (!$history) {
            return;
        }
    }

    public function showForComputer($ID, $withtemplate = '')
    {
        // TODO: Design a rights system for the whole plugin
        $canedit = self::canUpdate();

        $options = [
            'candel'   => false,
            'can_edit' => $canedit,
        ];
        $this->initForm($this->getID(), $options);
        TemplateRenderer::getInstance()->display('@carbon/environmentalimpact.html.twig', [
            'params'   => $options,
            'item'     => $this,
        ]);
    }

    public function showForMonitor($ID, $withtemplate = '')
    {
        // Empty as there is no usage profile for monitor at the moment
        // this method is needed to trigger Hooks::POST_SHOW_TAB in GLPI
        // and show historization status
    }

    public function showForNetworkEquipment($ID, $withtemplate = '')
    {
        // Empty as there is no usage profile for Network equipment at the moment
        // this method is needed to trigger Hooks::POST_SHOW_TAB in GLPI
        // and show historization status
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();
        $my_table = self::getTable();

        $tab[] = [
            'id'                 => SearchOptions::ENVIRONMENTAL_IMPACT_COMPUTER_TYPE,
            'table'              => $my_table,
            'field'              => Computer::getForeignKeyField(),
            'name'               => Computer::getTypeName(1),
            'datatype'           => 'linkfield',
            'nodisplay'          => true,
        ];

        $tab[] = [
            'id'                 => SearchOptions::ENVIRONMENTAL_IMPACT_COMPUTER_USAGE_PROFILE,
            'table'              => $my_table,
            'field'              => ComputerUsageProfile::getForeignKeyField(),
            'name'               => ComputerUsageProfile::getTypeName(1),
            'datatype'           => 'linkfield',
            'nodisplay'          => true,
        ];

        return $tab;
    }

    public static function showCharts(CommonGLPI $item)
    {
        TemplateRenderer::getInstance()->display('@carbon/environmentalimpact-item.html.twig', [
            'item' => $item,
        ]);
    }
}
