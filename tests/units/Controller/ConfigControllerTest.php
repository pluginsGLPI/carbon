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

use Glpi\Exception\RedirectException;
use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use GlpiPlugin\Carbon\Controller\ConfigController;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(ConfigController::class)]
final class ConfigControllerTest extends DbTestCase
{
    public function test_alert_redirects_to_carbon_configuration_tab(): void
    {
        $this->expectException(RedirectException::class);

        (new ConfigController())->alert(Request::create('/front/config.form.php', 'GET'));
    }

    public function test_alert_requires_authenticated_user(): void
    {
        $method = new ReflectionMethod(ConfigController::class, 'alert');
        $security_attribute = $method->getAttributes(SecurityStrategy::class)[0]->newInstance();

        $this->assertSame(Firewall::STRATEGY_AUTHENTICATED, $security_attribute->strategy);
    }
}
