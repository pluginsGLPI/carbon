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

namespace GlpiPlugin\Carbon\DataSource\CarbonIntensity\ElectricityMaps;

use Config as GlpiConfig;
use Glpi\Application\View\TemplateRenderer;
use GLPIKey;
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
        $expected = ['electricitymap_api_key'];
        $this->assertSame($expected, $result);
    }

    public function testGetconfigTemplate()
    {
        $context = [
            'current_config' => [
                'electricitymap_api_key' => 'foo',
            ],
        ];
        $this->login('glpi', 'glpi');
        $instance = new Config();
        $result = $instance->getConfigTemplate();
        $renderer = TemplateRenderer::getInstance();
        if (!$renderer->getEnvironment()->hasExtension(StringLoaderExtension::class)) {
            $renderer->getEnvironment()->addExtension(new StringLoaderExtension());
        }
        $result_html = $renderer->renderFromStringTemplate($result, $context);
        $crawler = new Crawler($result_html);
        $boaviztapi_url = $crawler->filter('input[name="electricitymap_api_key"]');
        $this->assertEquals(1, $boaviztapi_url->count());
    }

    public static function configUpdateProvider()
    {
        yield [
            [
                'electricitymap_api_key' => '',
            ], [],
        ];

        yield [
            [
                'electricitymap_api_key' => 'foo',
            ], [
                'electricitymap_api_key' => 'foo',
            ],
        ];
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
            Client::class,
        ];

        $instance = new Config();
        $result = $instance->configUpdate($input);
        $this->assertEquals($expected, $result);
    }

    public function testGetPluginConfigurationValue()
    {
        // Test an overridable configuration value, not overriden
        GlpiConfig::setConfigurationValues('plugin:carbon', [
            'electricitymap_api_key' => 'bar',
        ]);
        $result = Config::getConfigurationValue('electricitymap_api_key');
        $glpi_key = new GLPIKey();
        $this->assertEquals('bar', $glpi_key->decrypt($result));
    }
}
