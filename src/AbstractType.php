<?php

/**
 * -------------------------------------------------------------------------
 * carbon plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2024 Teclib' and contributors.
 * @license   MIT https://opensource.org/licenses/mit-license.php
 * @link      https://github.com/pluginsGLPI/carbon
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Carbon;

use CommonDBChild;
use CommonDBTM;
use CommonGLPI;
use Session;
use Glpi\Application\View\TemplateRenderer;

abstract class AbstractType extends CommonDBChild
{
    public static $rightname = 'dropdown';

    /**
     * @todo fix type name
     */
    public static function getTypeName($nb = 0)
    {
        return _n("Power", "Powers", $nb, 'carbon');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $tabName = '';
        if (!$withtemplate) {
            if ($item->getType() == static::$itemtype) {
                $tabName = __('Carbon');
            }
        }
        return $tabName;
    }

    /**
     * Undocumented function
     *
     * @param CommonGLPI $item
     * @param integer $tabnum
     * @param integer $withtemplate
     * @return void
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        /** @var CommonDBTM $item */
        if ($item->getType() == static::$itemtype) {
            $typePower = new static();
            $typePower->getFromDBByCrit([$item->getForeignKeyField() => $item->getID()]);
            if ($typePower->isNewItem()) {
                $typePower->add([
                    $item->getForeignKeyField() => $item->getID()
                ]);
            }
            $typePower->showForItemType($typePower->getID());
        }
    }

    public function showForItemType($ID, $withtemplate = '')
    {
        // TODO: Design a rights system for the whole plugin
        $canedit = Session::haveRight(Config::$rightname, UPDATE);

        $options = [
            'candel'   => false,
            'can_edit' => $canedit,
        ];
        $this->initForm($this->getID(), $options);
        $template = strtolower(basename(str_replace('\\', '/', static::class))) . '.html.twig';
        TemplateRenderer::getInstance()->display('@carbon/' . $template, [
            'params'   => $options,
            'item'     => $this,
        ]);
    }
}
