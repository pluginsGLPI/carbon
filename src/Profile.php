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

use CommonDBTM;
use CommonGLPI;
use Profile as GlpiProfile;
use Html;
use Session;

class Profile extends GlpiProfile
{
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        return self::createTabEntry(__('Environnemental impact', 'carbon'), 0);
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        /** @var CommonDBTM $item */
        $profile = new self();
        $profile->showForm($item->getID());
        return true;
    }

    public function showForm($ID, $options = [])
    {
        if (!self::canView()) {
            return false;
        }

        $canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]);

        $profile = new GlpiProfile();
        $profile->getFromDB($ID);
        echo "<div class='spaced'>";
        if ($canedit) {
            echo "<form method='post' action='" . GlpiProfile::getFormURL() . "' data-track-changes='true'>";
        }
        $rights = [
            [
                'itemtype' => Report::getType(),
                'label'    => Report::getTypeName(Session::getPluralNumber()),
                'field'    => Report::$rightname,
            ],
        ];
        $matrix_options = [
            'title'   => Report::getTypeName(Session::getPluralNumber()),
            'canedit' => $canedit,
        ];
        $profile->displayRightsChoiceMatrix($rights, $matrix_options);

        if ($canedit) {
            echo "<div class='center'>";
            echo Html::hidden('id', ['value' => $ID]);
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary mt-2']);
            echo "</div>";
            Html::closeForm();
        }
        echo "</div>";
    }
}
