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

namespace GlpiPlugin\Carbon\Tests\Controller;

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Http\RedirectResponse;
use GlpiPlugin\Carbon\Controller\ReportController;
use GlpiPlugin\Carbon\Report;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(ReportController::class)]
final class ReportControllerTest extends DbTestCase
{
    public function test_invoke_throws_access_denied_without_view_right(): void
    {
        $this->login('glpi', 'glpi');
        $_SESSION['glpiactiveprofile'][Report::$rightname] = 0;

        $this->expectException(AccessDeniedHttpException::class);

        (new ReportController())->__invoke(Request::create('/front/report.php', 'GET'));
    }

    public function test_invoke_returns_instant_report_for_authorized_user(): void
    {
        $this->login('glpi', 'glpi');
        $_SERVER['REQUEST_URI'] = '/front/report.php';

        $response = (new ReportController())->__invoke(Request::create('/front/report.php', 'GET'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotSame('', trim((string) $response->getContent()));
    }

    public function test_invoke_disable_demo_redirects_to_previous_page(): void
    {
        $this->login('glpi', 'glpi');

        $response = (new ReportController())->__invoke(
            Request::create('/front/report.php', 'GET', ['disable_demo' => '1'])
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }
}
