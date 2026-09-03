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
use CommonGLPI;
use Datacenter as GlpiDatacenter;
use Glpi\Application\View\TemplateRenderer;
use Override;

class Datacenter extends CommonDBChild
{
    // From CommonDBRelation
    public static $itemtype       = GlpiDatacenter::class;
    public static $items_id       = 'datacenters_id';

    #[Override]
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item instanceof GlpiDatacenter) {
            return self::createTabEntry(__('Environmental impact', 'carbon'), 0);
        }
        return '';
    }

    #[Override]
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof GlpiDatacenter) {
            /** @var GlpiDatacenter $item */
            $location = new self();
            $location->showForDatacenter($item);
        }
        return true;
    }

    public function showForDatacenter(GlpiDatacenter $item, array $options = [])
    {
        $this->getFromDBByCrit(['datacenters_id' => $item->getID()]);
        if ($this->isNewItem()) {
            $this->add(['datacenters_id' => $item->getID()]);
        }

        TemplateRenderer::getInstance()->display('@carbon/datacenter.html.twig', [
            'item' => $this,
            'params' => [
                'candel' => false,
            ],
        ]);

        return true;
    }
}
