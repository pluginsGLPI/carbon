<?php

namespace GlpiPlugin\Carbon;

use CommonDBTM;
use CommonGLPI;
use Profile as GlpiProfile;
use Html;
use Session;

class Profile extends GlpiProfile
{
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        return self::createTabEntry(__('Environnemental impact', 'carbon'), 0);
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        /** @var CommonDBTM $item */
        $profile = new self();
        $profile->showForm($item->getID());
        return true;
    }

    function showForm($ID, $options = [])
    {
        if (!self::canView()) {
            return false;
        }

        $canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]);

        echo "<div class='spaced'>";
        if ($canedit) {
            echo "<form method='post' action='". GlpiProfile::getFormURL()."' data-track-changes='true'>";
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
        $profile = new GlpiProfile();
        $profile->displayRightsChoiceMatrix($rights, $matrix_options);

        if ($canedit) {
            echo "<div class='center'>";
            echo Html::hidden('id', ['value' => $ID]);
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
            echo "</div>";
            Html::closeForm();
        }
        echo "</div>";
    }
}
