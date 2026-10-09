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
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Http\Firewall;
use Glpi\Http\RedirectResponse;
use Glpi\Security\Attribute\SecurityStrategy;
use GlpiPlugin\Carbon\Config;
use GlpiPlugin\Carbon\Report;
use Html;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReportController extends AbstractController
{
    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    #[Route(
        path: 'front/report.php',
        name: 'carbon_report',
        methods: ['GET']
    )]
    public function __invoke(Request $request): Response
    {
        if (!Report::canView()) {
            throw new AccessDeniedHttpException();
        }

        if ($request->query->has('disable_demo')) {
            Session::checkRight(GlpiConfig::$rightname, UPDATE);
            Config::exitDemoMode();

            return new RedirectResponse(Html::getBackUrl());
        }

        return new Response(Report::showInstantReport());
    }
}
