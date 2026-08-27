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
use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Carbon\DataTracking\TrackedInt;
use GlpiPlugin\Carbon\Impact\Type;
use Override;
use Session;

class AbstractModel extends AbstractChildDropdown
{
    #[Override]
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

        $criteria = [];
        foreach (Type::getImpactTypes() as $type_id => $type) {
            $unit = '(' . str_replace(' ', '&nbsp;', implode(' ', Type::getImpactUnit($type))) . ')';

            $criteria[$type] = [
                'title' => Type::getEmbodiedImpactLabel($type),
                'label' => Type::getEmbodiedImpactLabel($type),
                'icon'  => Type::getCriteriaIcon($type),
                'unit'  => $unit,
            ];
        }

        $template = strtolower(basename(str_replace('\\', '/', static::class))) . '.html.twig';
        TemplateRenderer::getInstance()->display('@carbon/' . $template, [
            'params'    => $options,
            'item'      => $this,
            'criterias' => $criteria,
        ]);
    }

    #[Override]
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

        $id = SearchOptions::IMPACT_BASE;
        foreach (Type::getImpactTypes() as $type_id => $type) {
            $id = SearchOptions::IMPACT_BASE + $type_id * 3;
            $tab[] = [
                'id'                 => $id,
                'table'              => $table,
                'field'              => $type,
                'name'               => Type::getEmbodiedImpactLabel($type),
                'massiveaction'      => false,
                'datatype'           => 'decimal',
                'unit'               => implode(' ', Type::getImpactUnit($type)),
            ];
            $id++;

            $tab[] = [
                'id'                 => $id,
                'table'              => $this->getTable(),
                'field'              => "{$type}_source",
                'name'               => __('Source', 'carbon'),
                'massiveaction'      => false,
                'datatype'           => 'string',
                'unit'               => implode(' ', Type::getImpactUnit($type)),
            ];
            $id++;

            $tab[] = [
                'id'                 => $id,
                'table'              => $this->getTable(),
                'field'              => "{$type}_quality",
                'name'               => __('Quality', 'carbon'),
                'massiveaction'      => false,
                'datatype'           => 'int',
                'unit'               => implode(' ', Type::getImpactUnit($type)),
            ];
        }

        return $tab;
    }
}
