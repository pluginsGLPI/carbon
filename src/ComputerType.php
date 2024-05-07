<?php

namespace GlpiPlugin\Carbon;

use CommonDBChild;
use CommonGLPI;
use CommonDBTM;
use ComputerType as GlpiComputerType;
use Session;
use Glpi\Application\View\TemplateRenderer;

class ComputerType extends CommonDBChild
{
    public static $itemtype = GlpiComputerType::class;
    public static $items_id = 'computertypes_id';

    public static function getTypeName($nb = 0)
    {
        return _n("Power", "Powers", $nb, 'carbon');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        $tabNames = [];
        if (!$withtemplate) {
           if ($item->getType() == GlpiComputerType::class) {
              $tabNames[1] = __('Carbon');
           }
        }
        return $tabNames;
    }

    /**
     * Undocumented function
     *
     * @param CommonGLPI $item
     * @param integer $tabnum
     * @param integer $withtemplate
     * @return void
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        /** @var GlpiComputerType $item */
        if ($item->getType() == GlpiComputerType::class) {
            $typePower = new self();
            $typePower->getFromDBByCrit([$item->getForeignKeyField() => $item->getID()]);
            if ($typePower->isNewItem()) {
                $typePower->add([
                    $item->getForeignKeyField() => $item->getID()
                ]);
            }
            $typePower->showForComputerType($typePower->getID());
        }
    }

    public function showForComputerType($ID, $withtemplate = '') {
        // TODO: Design a rights system for the whole plugin
        $canedit = Session::haveRight(Config::$rightname, UPDATE);

        $options = [
            'candel'   => false,
            'can_edit' => $canedit,
        ];
        $this->initForm($this->getID(), $options);
        TemplateRenderer::getInstance()->display('@carbon/computertype.html.twig', [
            'params'   => $options,
            'item'     => $this,
        ]);
    }
}