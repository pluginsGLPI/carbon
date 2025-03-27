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
use CommonDBTM;
use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;
use Monitor;
use NetworkEquipment;
use Plugin;

/**
 * Relation between a computer and a usage profile
 */
class UsageInfo extends CommonDBChild
{
    public static $itemtype = 'itemtype';
    public static $items_id = 'items_id';

    public static function getTypeName($nb = 0)
    {
        return __('Usage informations', 'Carbon');
    }

    public static function getIcon()
    {
        return 'fa-solid fa-solar-panel';
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $tabName = '';
        if (!$withtemplate) {
            if (in_array($item->getType(), PLUGIN_CARBON_TYPES)) {
                $tabName = self::getTypeName();
            }
        }
        return $tabName;
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
        /** @var CommonDBTM $item */
        $asset_itemtype  = $item->getType();
        if (in_array($asset_itemtype, PLUGIN_CARBON_TYPES)) {
            $usage_info = new self();
            $usage_info->getFromDBByCrit([
                'itemtype' => $item->getType(),
                'items_id' => $item->getID()
            ]);
            if ($usage_info->isNewItem()) {
                $usage_info->add(
                    [
                        'itemtype' => $item->getType(),
                        'items_id' => $item->getID()
                    ],
                    [],
                    false
                );
            }
            $usage_info->showForItem($usage_info->getID());
        }
    }

    public function post_updateItem($history = true)
    {
        parent::post_updateItem($history);

        if (!$history) {
            return;
        }
    }

    public function showForItem($ID, $withtemplate = '')
    {
        // TODO: Design a rights system for the whole plugin
        $canedit = self::canUpdate();

        $options = [
            'candel'   => false,
            'can_edit' => $canedit,
            'target'   => Plugin::getWebDir('carbon') . '/front/usageimpact.form.php',
        ];
        $this->initForm($this->getID(), $options);
        TemplateRenderer::getInstance()->display('@carbon/usageinfo.html.twig', [
            'params'   => $options,
            'item'     => $this,
        ]);
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

    public static function showCharts(CommonDBTM $item)
    {
        $embodied_impact = new EmbodiedImpact();
        $embodied_impact->getFromDBByCrit([
            'itemtype' => $item->getType(),
            'items_id' => $item->getID(),
        ]);

        TemplateRenderer::getInstance()->display('@carbon/environmentalimpact-item.html.twig', [
            'item' => $item,
            'embodied_impact' => $embodied_impact,
        ]);
    }
}
