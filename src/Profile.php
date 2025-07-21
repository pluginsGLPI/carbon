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

        return true;
    }
}
