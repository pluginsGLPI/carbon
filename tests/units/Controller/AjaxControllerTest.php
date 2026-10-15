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

use GlpiPlugin\Carbon\Controller\AjaxController;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(AjaxController::class)]
final class AjaxControllerTest extends DbTestCase
{
    public function test_show_dropdown_by_source_condition_returns_forbidden_on_get(): void
    {
        $response = (new AjaxController())->showDropdownBySourceCondition(
            Request::create('/', 'GET', ['dom_id' => '42'])
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_show_dropdown_by_source_condition_returns_forbidden_without_zone_right(): void
    {
        $this->login('glpi', 'glpi');
        $_SESSION['glpiactiveprofile']['dropdown'] = 0;

        $response = (new AjaxController())->showDropdownBySourceCondition(
            Request::create('/', 'POST', [
                'plugin_carbon_sources_id' => '1',
                'dom_id' => '42',
            ])
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_toggle_zone_download_returns_forbidden_without_dropdown_right(): void
    {
        $this->login('glpi', 'glpi');
        $_SESSION['glpiactiveprofile']['dropdown'] = 0;

        $response = (new AjaxController())->toggleZoneDownload(
            Request::create('/', 'POST', ['id' => '1'])
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_toggle_zone_download_returns_forbidden_without_config_update_right(): void
    {
        $this->login('glpi', 'glpi');
        $_SESSION['glpiactiveprofile']['config'] = READ;

        $response = (new AjaxController())->toggleZoneDownload(
            Request::create('/', 'POST', ['id' => '1'])
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_toggle_zone_download_returns_forbidden_on_get(): void
    {
        $response = (new AjaxController())->toggleZoneDownload(
            Request::create('/', 'GET')
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_toggle_zone_download_returns_bad_request_without_id(): void
    {
        $this->login('glpi', 'glpi');

        $response = (new AjaxController())->toggleZoneDownload(
            Request::create('/', 'POST')
        );

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(__('Bad request.', 'carbon'), $response->getContent());
    }
}
