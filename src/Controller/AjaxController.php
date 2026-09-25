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

namespace GlpiPlugin\Carbon\Controller;

use Config as GlpiConfig;
use Glpi\Controller\AbstractController;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use GlpiPlugin\Carbon\Source;
use GlpiPlugin\Carbon\Source_Zone;
use GlpiPlugin\Carbon\Zone;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AjaxController extends AbstractController
{
    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    #[Route(
        path: 'ajax/dropdownZone.php',
        name: 'ajax dropdownZone',
        methods: ['GET', 'POST'])]
    public function showDropdownBySourceCondition(Request $request): Response
    {
        // if method is GET, then throw an exception, workaround bug in GLPI up to 11.0.7
        if ($request->isMethod('GET')) {
            return new Response('', 403);
        }

        if (!Zone::canView()) {
            return new Response('', 403);
        }

        $source_id = (int) $_POST['plugin_carbon_sources_id'];
        $html = Zone::dropdown([
            'display' => false,
            'rand' => (int) $_POST['dom_id'],
            'condition' => Zone::getRestrictBySourceCondition($source_id),
            'specific_tags' => ($source_id === 0 ? ['disabled' => 'disabled'] : []),
        ]);
        return new Response($html);
    }
}
