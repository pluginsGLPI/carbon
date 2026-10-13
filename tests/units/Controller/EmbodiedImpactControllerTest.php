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

use Exception;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Http\RedirectResponse;
use GlpiPlugin\Carbon\EmbodiedImpact;
use GlpiPlugin\Carbon\Controller\EmbodiedImpactController;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use Html;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(EmbodiedImpactController::class)]
final class EmbodiedImpactControllerTest extends DbTestCase
{
    public function test_handle_dispatches_reset_all_action(): void
    {
        $this->login('glpi', 'glpi');

        $response = (new EmbodiedImpactController())->handle(
            Request::create('/', 'POST', ['reset_all' => '1'])
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(Html::getBackUrl(), $response->getTargetUrl());
    }

    public function test_handle_rejects_unknown_action(): void
    {
        $this->login('glpi', 'glpi');

        $this->expectException(BadRequestHttpException::class);

        (new EmbodiedImpactController())->handle(Request::create('/', 'POST'));
    }

    public static function protectedActionsProvider(): iterable
    {
        yield 'update' => [['update' => '1']];
        yield 'reset all' => [['reset_all' => '1']];
        yield 'reset' => [['reset' => '1']];
        yield 'calculate' => [['calculate' => '1']];
        yield 'no action' => [[]];
    }

    #[DataProvider('protectedActionsProvider')]
    public function test_handle_denies_users_without_report_read_right(array $parameters): void
    {
        $this->login('glpi', 'glpi');
        $_SESSION['glpiactiveprofile'][EmbodiedImpact::$rightname] = 0;

        $this->expectException(AccessDeniedHttpException::class);

        (new EmbodiedImpactController())->handle(Request::create('/', 'POST', $parameters));
    }

    public function test_reset_returns_redirect_when_arguments_are_missing(): void
    {
        $this->login('glpi', 'glpi');

        $response = $this->callPrivateMethod(
            new EmbodiedImpactController(),
            'reset',
            Request::create('/', 'POST')
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertNotEmpty($_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR] ?? []);
        $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
    }

    public function test_calculate_returns_redirect_when_arguments_are_missing(): void
    {
        $this->login('glpi', 'glpi');

        $response = $this->callPrivateMethod(
            new EmbodiedImpactController(),
            'calculate',
            Request::create('/', 'POST')
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertNotEmpty($_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR] ?? []);
        $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
    }

    public function test_calculate_rejects_invalid_asset_type(): void
    {
        $this->login('glpi', 'glpi');

        $request = Request::create('/', 'POST', [
            'itemtype' => 'UnknownAsset',
            'items_id' => '42',
        ]);

        $response = $this->callPrivateMethod(new EmbodiedImpactController(), 'calculate', $request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertNotEmpty($_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR] ?? []);
        $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
    }
}
