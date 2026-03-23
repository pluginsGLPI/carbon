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

use GlpiPlugin\Carbon\AbstractModel;
use GlpiPlugin\Carbon\AbstractType;
use PHPUnit\Framework\Attributes\CoversClass;
use Session;
use Symfony\Component\DomCrawler\Crawler;

#[CoversClass(AbstractType::class)]
abstract class AbstractModelTest extends DbTestCase
{
    protected static string $glpi_model_itemtype;

    /**
     * @template T of AbstractType
     * @var class-string<T>
     */
    protected static string $model_itemtype;

    public function testGetTypeName()
    {
        $this->assertEquals('Environmental impact', static::$model_itemtype::getTypeName(1));
        $this->assertEquals('Environmental impact', static::$model_itemtype::getTypeName(Session::getPluralNumber()));
    }

    public function testShowForItemType()
    {
        $glpi_model = $this->createItem(static::$glpi_model_itemtype);
        $glpi_model_fk = $glpi_model::getForeignKeyField();
        /** @var T $model */
        $model = $this->createItem(static::$model_itemtype, [
            $glpi_model_fk => $glpi_model->getID(),
        ]);
        $this->login('glpi', 'glpi');
        ob_start();
        /** @var AbstractModel $model */
        $model->showForItemType($glpi_model);
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

    public function test_displayTabContentForItemtype_creates_type_extra_data_when_they_dont_exist()
    {
        $glpi_type = $this->createItem(static::$glpi_model_itemtype);
        // Check that there is no type extra data object
        $glpi_type_fk = getForeignKeyFieldForItemType(get_class($glpi_type));
        $type = new static::$model_itemtype();
        $type->getFromDBByCrit([
            $glpi_type_fk => $glpi_type->getID(),
        ]);
        $this->assertTrue($type->isNewItem());
        // Test the method
        $this->login('glpi', 'glpi');
        ob_start();
        $type->displayTabContentForItem($glpi_type);
        ob_end_clean();
        // Now, $type is expected to be populated
        $type->getFromDBByCrit([
            $glpi_type_fk => $glpi_type->getID(),
        ]);
        $this->assertFalse($type->isNewItem());
        $this->assertSame($type->fields[$glpi_type_fk], $glpi_type->getID());
    }
}
