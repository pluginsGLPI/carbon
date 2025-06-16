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
use GlpiPlugin\Carbon\Dashboard\Provider;
use GlpiPlugin\Carbon\Dashboard\Widget;
use Html;
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

    public static $rightname = 'carbon:report';

    public static function getTypeName($nb = 0)
    {
        return __('Usage informations', 'Carbon');
    }

    public static function getIcon()
    {
        return 'fa-solid fa-solar-panel';
    }

    public function canPurgeItem(): bool
    {
        if ($this->isNewItem()) {
            return false;
        }

        $itemtype = $this->fields['itemtype'];
        // Check that itemtype inherits from CommonDBTM
        if (!is_subclass_of($itemtype, CommonDBTM::class)) {
            return false;
        }
        $item = new $itemtype();
        if (!$item->getFromDB($this->fields['items_id'])) {
            return false;
        }

        return $item->canDelete();
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $tabName = '';
        if (!$withtemplate) {
            if (in_array($item->getType(), PLUGIN_CARBON_TYPES)) {
                $tabName = __('Environmental impact', 'carbon');
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
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        // TODO: Design a rights system for the whole plugin
        $canedit = self::canUpdate();

        $target = $CFG_GLPI['root_doc'] . '/plugins/carbon/front/usageimpact.form.php';
        if (version_compare(GLPI_VERSION, '11.0', '<')) {
            $target = Plugin::getWebDir('carbon') . '/front/usageimpact.form.php';
        }

        $options = [
            'candel'   => false,
            'can_edit' => $canedit,
            'target'   => $target,
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
            'id'                 => SearchOptions::USAGE_INFO_COMPUTER_USAGE_PROFILE,
            'table'              => $my_table,
            'field'              => ComputerUsageProfile::getForeignKeyField(),
            'name'               => ComputerUsageProfile::getTypeName(1),
            'datatype'           => 'linkfield',
            'nodisplay'          => true,
        ];

        return $tab;
    }

    public static function showCharts(CommonDBTM $asset)
    {
        /** @var array $CFG_GLPI  */
        global $CFG_GLPI;

        $embodied_impact = new EmbodiedImpact();
        $embodied_impact->getFromDBByCrit([
            'itemtype' => $asset->getType(),
            'items_id' => $asset->getID(),
        ]);

        $usage_impact = new UsageImpact();
        $usage_impact->getFromDBByCrit([
            'itemtype' => $asset->getType(),
            'items_id' => $asset->getID(),
        ]);

        $usage_info = new self();
        $usage_info->getFromDBByCrit([
            'itemtype' => $asset->getType(),
            'items_id' => $asset->getID(),
        ]);
        if (in_array($asset->getType(), [Computer::class, NetworkEquipment::class, Monitor::class])) {
            // TODO: decide if we show or not this impact.
            unset($usage_impact->fields['pe']);
        }

        $data = Provider::getUsageCarbonEmissionPerMonth([
            'itemtype' => $asset->getType(),
            'items_id' => $asset->getID(),
        ]);

        $url = Documentation::getInfoLink('carbon_emission');
        $tooltip = __('Evaluates the carbon emission in CO₂ equivalent. %s More information %s', 'carbon');
        $tooltip = sprintf($tooltip, '<br /><a target="_blank" href="' . $url . '">', '</a>');
        $carbon_emission_tooltip_html = Html::showToolTip($tooltip, [
            'display' => false,
            'applyto' => 'carbon_emission_tip',
        ]);

        $url = Documentation::getInfoLink('abiotic_depletion_impact');
        $tooltip = __('Evaluates the consumption of non renewable resources in Antimony equivalent. %s More information %s', 'carbon');
        $tooltip = sprintf($tooltip, '<br /><a target="_blank" href="' . $url . '">', '</a>');
        $usage_abiotic_depletion_tooltip_html = Html::showToolTip($tooltip, [
            'display' => false,
            'applyto' => 'usage_abiotic_depletion_tip',
        ]);
        $embodied_abiotic_depletion_tooltip_html = Html::showToolTip($tooltip, [
            'display' => false,
            'applyto' => 'embodied_abiotic_depletion_tip',
        ]);

        $url = Documentation::getInfoLink('primary_energy');
        $tooltip = __('Evaluates the primary energy consumed. %s More information %s', 'carbon');
        $tooltip = sprintf($tooltip, '<br /><a target="_blank" href="' . $url . '">', '</a>');
        $embodied_primary_energy_tooltip_html = Html::showToolTip($tooltip, [
            'display' => false,
            'applyto' => 'embodied_primary_energy_tip',
        ]);

        $usage_imapct_action_url    = $CFG_GLPI['root_doc'] . '/plugins/carbon/front/usageimpact.form.php';
        $embodied_impact_action_url = $CFG_GLPI['root_doc'] . '/plugins/carbon/front/embodiedimpact.form.php';
        if (version_compare(GLPI_VERSION, '11.0', '<')) {
            $usage_imapct_action_url    = Plugin::getWebDir('carbon') . '/front/usageimpact.form.php';
            $embodied_impact_action_url = Plugin::getWebDir('carbon') . '/front/embodiedimpact.form.php';
        }
        TemplateRenderer::getInstance()->display('@carbon/environmentalimpact-item.html.twig', [
            'usage_info'      => $usage_info,
            'asset'           => $asset,
            'embodied_impact' => $embodied_impact,
            'usage_impact'    => $usage_impact,
            'usage_carbon_emission_graph' => Widget::DisplayGraphUsageCarbonEmissionPerMonth($data),
            'carbon_emission_tooltip_html' => $carbon_emission_tooltip_html,
            'usage_abiotic_depletion_tooltip_html' => $usage_abiotic_depletion_tooltip_html,
            'embodied_abiotic_depletion_tooltip_html' => $embodied_abiotic_depletion_tooltip_html,
            'embodied_primary_energy_tooltip_html' => $embodied_primary_energy_tooltip_html,
            'usage_impact_action_url'    => $usage_imapct_action_url,
            'embodied_impact_action_url' => $embodied_impact_action_url,
        ]);
    }
}
