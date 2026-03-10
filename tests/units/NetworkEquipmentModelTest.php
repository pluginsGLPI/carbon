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

use GlpiPlugin\Carbon\NetworkEquipmentModel;
use NetworkEquipmentModel as GlpiNetworkEquipmentModel;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DomCrawler\Crawler;

#[CoversClass(NetworkEquipmentModel::class)]
class NetworkEquipmentModelTest extends DbTestCase
{
    /**
     * #CoversMethod GlpiPlugin\Carbon\AbstractType::showForItemType
     */
    public function testShowForItemType()
    {
        $glpi_networkequipment_model = $this->createItem(GlpiNetworkEquipmentModel::class);
        $networkequipment_model = $this->createItem(NetworkEquipmentModel::class, [
            'networkequipmentmodels_id' => $glpi_networkequipment_model->getID(),
        ]);
        $this->login('glpi', 'glpi');
        ob_start();
        $networkequipment_model->showForItemType($glpi_networkequipment_model);
        $output = ob_get_clean();
        $crawler = new Crawler($output);
        $gwp = $crawler->filter('input[name="gwp"]');
        $this->assertEquals(1, $gwp->count());
        $gwp->each(function (Crawler $node) {
            $this->assertEquals(0, $node->attr('value'));
            $this->assertEquals('number', $node->attr('type'));
        });

        $gwp_source = $crawler->filter('input[name="gwp_source"]');
        $gwp_source->each(function (Crawler $node) {
            $this->assertEquals('', $node->attr('value'));
        });

        $gwp_quality = $crawler->filter('select[name="gwp_quality"]');
    }
}
