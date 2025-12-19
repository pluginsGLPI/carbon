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

namespace GlpiPlugin\Carbon\DataSource\Lca\Boaviztapi;

use Config as GlpiConfig;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Carbon\DataSource\Lca\Boaviztapi\Config;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Extension\StringLoaderExtension;

#[CoversClass(Config::class)]
class ConfigTest extends DbTestCase
{
    public function testGetSecuredConfig()
    {
        $result = Config::getSecuredConfigs();
        $expected = [];
        $this->assertSame($expected, $result);
    }

    public function testGetconfigTemplate()
    {
        $context = [
            'current_config' => [
                'boaviztapi_base_url' => 'foo',
                'geocoding_enabled'   => true,
            ]
        ];
        $this->login('glpi', 'glpi');
        $instance = new Config();
        putenv(Config::ENV_BOAVIZTAPI_BASE_URL); // Env var unset
        $result = $instance->getConfigTemplate();
        $renderer = TemplateRenderer::getInstance();
        if (!$renderer->getEnvironment()->hasExtension(StringLoaderExtension::class)) {
            $renderer->getEnvironment()->addExtension(new StringLoaderExtension());
        }
        $result_html = $renderer->renderFromStringTemplate($result, $context);
        $crawler = new Crawler($result_html);
        $boaviztapi_url = $crawler->filter('input[name="boaviztapi_base_url"]');
        $geocoding = $crawler->filter('input[type="checkbox"][name="geocoding_enabled"]');
        $this->assertEquals(1, $boaviztapi_url->count());
        $this->assertEquals(1, $geocoding->count());

        putenv(Config::ENV_BOAVIZTAPI_BASE_URL . '=bar');
        $result = $instance->getConfigTemplate();
        $result_html = $renderer->renderFromStringTemplate($result, $context);
        $crawler = new Crawler($result_html);
        $boaviztapi_url = $crawler->filter('input[name="boaviztapi_base_url"]');
        $geocoding = $crawler->filter('input[type="checkbox"][name="geocoding_enabled"]');
        $this->assertEquals(0, $boaviztapi_url->count());
        $this->assertEquals(1, $geocoding->count());
    }

    public static function configUpdateProvider()
    {
        yield [
            [
                'boaviztapi_base_url' => '',
            ], [
                'boaviztapi_base_url' => '',
            ]
        ];

        // TODO: requires code change to test boaviztapi_base_url with a not-empty value
        // this triggers creation of an object then HTTP request, should be avoided in tests context
    }

    /**
     * @param array $input
     * @param array $expected
     * @return void
     */
    #[DataProvider('configUpdateProvider')]
    public function testConfigUpdate(array $input, array $expected)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $CFG_GLPI['plugi:carbon']['lca_datasources'] = [
            Client::class
        ];

        $result = (new Config())->configUpdate($input);
        $this->assertEquals($expected, $result);
    }

    public function testGetPluginConfigurationValue()
    {
        // Test an overridable configuration value, not overriden
        GlpiConfig::setConfigurationValues('plugin:carbon', [
            'boaviztapi_base_url' => 'bar'
        ]);
        putenv(Config::ENV_BOAVIZTAPI_BASE_URL); // Env var unset
        $result = Config::getConfigurationValue('boaviztapi_base_url');
        $this->assertEquals('bar', $result);

        // Test an overridable configuration value, overriden by an env var
        GlpiConfig::setConfigurationValues('plugin:carbon', [
            'boaviztapi_base_url' => 'baz'
        ]);
        putenv(Config::ENV_BOAVIZTAPI_BASE_URL . '=bar');
        $result = Config::getConfigurationValue('boaviztapi_base_url');
        $this->assertEquals('bar', $result);
    }
}
