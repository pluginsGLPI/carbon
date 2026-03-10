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

use CommonDBChild;
use CommonDBTM;
use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Carbon\DataTracking\TrackedInt;
use GlpiPlugin\Carbon\Impact\Type;
use Session;

class AbstractModel extends CommonDBChild
{
    public static $rightname = 'dropdown';

    public static function getIcon(): string
    {
        return 'fa-solid fa-solar-panel';
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Environmental impact', 'Environmental impact', $nb, 'carbon');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $tabName = '';
        if (!$withtemplate) {
            if ($item->getType() == static::$itemtype) {
                return self::createTabEntry(__('Carbon', 'carbon'), 0);
            }
        }
        return $tabName;
    }

    /**
     * Undocumented function
     *
     * @param CommonGLPI $item
     * @param int $tabnum
     * @param int $withtemplate
     * @return void
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        /** @var CommonDBTM $item */
        if ($item->getType() !== static::$itemtype) {
            return;
        }

        $type = new static();
        $type->getOrCreate($item);
        $type->showForItemType($type->getID());
    }

    public function prepareInputForUpdate($input)
    {
        $keys = Type::getImpactTypes();
        foreach ($keys as $key) {
            $source_key = $key . '_source';
            $input[$source_key] = trim($input[$source_key] ?? '');
        }

        return parent::prepareInputForUpdate($input);
    }

    /**
     * Get the type for the item, creating it if it doesn't exist.
     *
     * @param CommonGLPI $item
     * @return bool
     */
    protected function getOrCreate(CommonGLPI $item): bool
    {
        /** @var CommonDBTM $item */
        $item_fk = $item->getForeignKeyField();
        $this->getFromDBByCrit([$item_fk => $item->getID()]);
        if ($this->isNewItem()) {
            $input = [
                $item_fk => $item->getID(),
            ];
            $types = Type::getImpactTypes();
            foreach ($types as $type) {
                $input[$type . '_quality'] = TrackedInt::DATA_QUALITY_UNSET_VALUE;
            }
            $this->add($input);
        }
        return $this->isNewItem();
    }

    public function showForItemType($ID, $withtemplate = '')
    {
        // TODO: Design a rights system for the whole plugin
        $canedit = Session::haveRight(Config::$rightname, UPDATE);

        $options = [
            'candel'   => false,
            'can_edit' => $canedit,
        ];
        $this->initForm($this->getID(), $options);

        $criterias = [
            'gwp' => [
                'title' => __('Global warming potential', 'carbon'),
                'label' => __('Emissions of CO2 (KgCO2eq)', 'carbon'),
                'icon'  => '', // 'fa-solid fa-temperature-three-quarters'
            ],
            'adp' => [
                'title' => __('Abiotic depletion potential', 'carbon'),
                'label' => __('Abiotic depletion potential (gSbEq)', 'carbon'),
                'icon'  => '', // @todo : find an icon
            ],
            'pe' => [
                'title' => __('Primary energy', 'carbon'),
                'label' => __('Primary energy (J)', 'carbon'),
                'icon'  => '', // @todo : find an icon
            ],
        ];

        $template = strtolower(basename(str_replace('\\', '/', static::class))) . '.html.twig';
        TemplateRenderer::getInstance()->display('@carbon/' . $template, [
            'params'    => $options,
            'item'      => $this,
            'criterias' => $criterias,
        ]);
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();
        $table   = $this->getTable();

        $tab[] = [
            'id'                 => '2',
            'table'              => $table,
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false, // implicit field is id
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'       => SearchOptions::EMBODIED_IMPACT_GWP,
            'table'    => $table,
            'field'    => 'gwp',
            'name'     => __('Global warming potential', 'carbon'),
            'datatype' => 'float',
        ];

        $tab[] = [
            'id'       => SearchOptions::EMBODIED_IMPACT_GWP_SOURCE,
            'table'    => $table,
            'field'    => 'gwp_source',
            'name'     => __('Global warming potential source', 'carbon'),
            'datatype' => 'string',
        ];

        $tab[] = [
            'id'       => SearchOptions::EMBODIED_IMPACT_GWP_QUALITY,
            'table'    => $table,
            'field'    => 'gwp_quality',
            'name'     => __('Global warming potential quality', 'carbon'),
            'datatype' => 'int',
        ];

        $tab[] = [
            'id'       => SearchOptions::EMBODIED_IMPACT_ADP,
            'table'    => $table,
            'field'    => 'adp',
            'name'     => __('Abiotic depletion potential', 'carbon'),
            'datatype' => 'float',
        ];

        $tab[] = [
            'id'       => SearchOptions::EMBODIED_IMPACT_ADP_SOURCE,
            'table'    => $table,
            'field'    => 'adp_source',
            'name'     => __('Abiotic depletion potential source', 'carbon'),
            'datatype' => 'string',
        ];

        $tab[] = [
            'id'       => SearchOptions::EMBODIED_IMPACT_ADP_QUALITY,
            'table'    => $table,
            'field'    => 'adp_quality',
            'name'     => __('Abiotic depletion potential quality', 'carbon'),
            'datatype' => 'int',
        ];

        $tab[] = [
            'id'       => SearchOptions::EMBODIED_IMPACT_PE,
            'table'    => $table,
            'field'    => 'pe',
            'name'     => __('Primary energy (J)', 'carbon'),
            'datatype' => 'float',
        ];

        $tab[] = [
            'id'       => SearchOptions::EMBODIED_IMPACT_PE_SOURCE,
            'table'    => $table,
            'field'    => 'pe_source',
            'name'     => __('Primary energy source', 'carbon'),
            'datatype' => 'string',
        ];

        $tab[] = [
            'id'       => SearchOptions::EMBODIED_IMPACT_PE_QUALITY,
            'table'    => $table,
            'field'    => 'pe_quality',
            'name'     => __('Primary energy quality', 'carbon'),
            'datatype' => 'int',
        ];

        return $tab;
    }
}
