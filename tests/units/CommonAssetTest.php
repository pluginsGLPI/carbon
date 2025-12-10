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

namespace GlpiPlugin\Carbon\Tests;

use Computer as GlpiComputer;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\CommonAsset;
use GlpiPlugin\Carbon\EmbodiedImpact;
use GlpiPlugin\Carbon\UsageImpact;
use GlpiPlugin\Carbon\Tests\DbTestCase;
use MassiveAction;
use Symfony\Component\DomCrawler\Crawler;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CommonAsset::class)]
class CommonAssetTest extends DbTestCase
{
    public function testShowMassiveActionsSubForm()
    {
        $massive_action = new MassiveAction([
            'action'    => 'GlpiPlugin\Carbon\CommonAsset:MassDeleteAllImpacts',
            'container' => 'massformMonitor910839446',
            'is_deleted' => 0,
            'action_filter' => [
                'GlpiPlugin\Carbon\CommonAsset:MassDeleteAllImpacts' => [
                    0 => GlpiComputer::class
                ]
            ],
            'actions' => [
                'GlpiPlugin\Carbon\CommonAsset:MassDeleteAllImpacts' => 'Delete all calculated environmental impacts'
            ],
            'items' => [
                GlpiComputer::class => [
                    0  => 0 // Un real scenario, a map of ID => ID
                ]
            ],
            'initial_items' => [
                GlpiComputer::class => [
                    0 => 0,
                ]
            ]
        ], [
        ], 'specialize');
        ob_start();
        CommonAsset::showMassiveActionsSubForm($massive_action);
        $output = ob_get_clean();
        $crawler = new Crawler($output);
        $submit_button = $crawler->filter('button[type="submit"]');
        $this->assertEquals(1, $submit_button->count());
    }

    public function testProcessMassiveActionsForOneItemtype()
    {
        $this->login('glpi', 'glpi');
        $glpi_computer_1 = $this->createItem(GlpiComputer::class);
        $glpi_computer_2 = $this->createItem(GlpiComputer::class);
        $this->createItems([
            EmbodiedImpact::class => [
                [
                    'itemtype' => GlpiComputer::class,
                    'items_id' => $glpi_computer_1->getID(),
                ],
                [
                    'itemtype' => GlpiComputer::class,
                    'items_id' => $glpi_computer_2->getID(),
                ]
            ]
        ]);
        $this->createItems([
            UsageImpact::class => [
                [
                    'itemtype' => GlpiComputer::class,
                    'items_id' => $glpi_computer_1->getID(),
                ],
                [
                    'itemtype' => GlpiComputer::class,
                    'items_id' => $glpi_computer_2->getID(),
                ]
            ]
        ]);
        $this->createItems([
            CarbonEmission::class => [
                [
                    'itemtype' => GlpiComputer::class,
                    'items_id' => $glpi_computer_1->getID(),
                ],
                [
                    'itemtype' => GlpiComputer::class,
                    'items_id' => $glpi_computer_2->getID(),
                ]
            ]
        ]);
        $glpi_computer = new GlpiComputer();
        $ids = [
            $glpi_computer_1->getID(),
            $glpi_computer_2->getID(),
        ];
        $massive_action = $this->getMockBuilder(MassiveAction::class)
            ->setConstructorArgs([
                [
                    'massiveaction' => 'Send',
                    'action' => 'MassDeleteAllImpacts',
                    'processor' => 'GlpiPlugin\Carbon\CommonAsset',
                    'is_deleted' => 0,
                    'initial_items' => [
                        GlpiComputer::class => $ids
                    ],
                    'items' => [
                        GlpiComputer::class => $ids
                    ],
                    'action_name' => 'Delete all calculated environmental impacts'
                ],
                [],
                'process'
            ])
            ->getMock();
        $massive_action->method('getAction')->willReturn('MassDeleteAllImpacts');
        $massive_action->expects($this->exactly(2))->method('itemDone');
        CommonAsset::processMassiveActionsForOneItemtype($massive_action, $glpi_computer, $ids);
        $embodied = new EmbodiedImpact();
        $rows = $embodied->find([
            'itemtype' => GlpiComputer::class,
            'items_id' => $ids
        ]);
        $this->assertSame(0, count($rows));
        $usage = new UsageImpact();
        $rows = $usage->find([
            'itemtype' => GlpiComputer::class,
            'items_id' => $ids
        ]);
        $this->assertSame(0, count($rows));
        $emissions = new CarbonEmission();
        $rows = $emissions->find([
            'itemtype' => GlpiComputer::class,
            'items_id' => $ids
        ]);
        $this->assertSame(0, count($rows));
    }
}
