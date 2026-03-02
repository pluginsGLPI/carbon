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

use Computer as GlpiComputer;
use CommonDBChild;
use CommonDBTM;
use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Carbon\Dashboard\Provider;
use GlpiPlugin\Carbon\Dashboard\Widget;
use GlpiPlugin\Carbon\Impact\Type;
use Html;
use Monitor;
use NetworkEquipment;

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
                $tabName = self::createTabEntry(__('Environmental impact', 'carbon'));
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

        // TODO: drop this early exit when enabling planned lifespan of assets
        if ($this->fields['itemtype'] !== GlpiComputer::class) {
            // Nothing to edit (for now)
            return;
        }

        // TODO: Design a rights system for the whole plugin
        $canedit = self::canUpdate();

        $target = $CFG_GLPI['root_doc'] . '/plugins/carbon/front/usageimpact.form.php';
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
        if (in_array($asset->getType(), [GlpiComputer::class, NetworkEquipment::class, Monitor::class])) {
            // TODO: decide if we show or not this impact.
            unset($usage_impact->fields['pe']);
        }
        $usage_carbon_emission_count = countElementsInTable(CarbonEmission::getTable(), [
            'itemtype' => $asset->getType(),
            'items_id' => $asset->getID(),
        ]);

        $data = Provider::getUsageCarbonEmissionPerMonth([
            'itemtype' => $asset->getType(),
            'items_id' => $asset->getID(),
        ]);

        $embodied_tooltips = [];
        $usage_tooltips = [];
        $embodied_labels = [];
        $usage_labels = [];
        foreach (Type::getImpactTypes() as $impact_type) {
            $tooltip = Type::getCriteriaTooltip($impact_type);
            $url = Type::getCriteriaInfoLink($impact_type);
            if ($url !== '') {
                $tooltip = sprintf(
                    $tooltip . __('%s More information %s', 'carbon'),
                    '<br /><a target="_blank" href="' . $url . '">',
                    '</a>'
                );
            }
            $embodied_tooltips[$impact_type] = '';
            $usage_tooltips[$impact_type] = '';
            if ($tooltip !== '') {
                $embodied_tooltips[$impact_type] = Html::showToolTip($tooltip, [
                    'display' => false,
                    'applyto' => 'embodied_' . $impact_type . '_tip',
                ]);
                $usage_tooltips[$impact_type] = Html::showToolTip($tooltip, [
                    'display' => false,
                    'applyto' => 'usage_' . $impact_type . '_tip',
                ]);
            }
            $embodied_labels[$impact_type] = Type::getEmbodiedImpactLabel($impact_type);
            $usage_labels[$impact_type] = Type::getUsageImpactLabel($impact_type);
        }

        $usage_imapct_action_url    = $CFG_GLPI['root_doc'] . '/plugins/carbon/front/usageimpact.form.php';
        $embodied_impact_action_url = $CFG_GLPI['root_doc'] . '/plugins/carbon/front/embodiedimpact.form.php';
        TemplateRenderer::getInstance()->display('@carbon/environmentalimpact-item.html.twig', [
            'usage_info'      => $usage_info,
            'asset'           => $asset,
            'usage_carbon_emission_count' => $usage_carbon_emission_count,
            'embodied_impact' => $embodied_impact,
            'usage_impact'    => $usage_impact,
            'embodied_labels' => $embodied_labels,
            'usage_labels' => $usage_labels,
            'embodied_tooltips' => $embodied_tooltips,
            'usage_tooltips' => $usage_tooltips,
            'usage_carbon_emission_graph' => Widget::DisplayGraphUsageCarbonEmissionPerMonth($data),
            'usage_impact_action_url'    => $usage_imapct_action_url,
            'embodied_impact_action_url' => $embodied_impact_action_url,
        ]);
    }
}
