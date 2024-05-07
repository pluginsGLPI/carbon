<?php

namespace GlpiPlugin\Carbon;

use CommonDBChild;
use CommonGLPI;
use MonitorType as GlpiMonitorType;
use Session;
use Glpi\Application\View\TemplateRenderer;

class MonitorType extends CommonDBChild
{
    public static $itemtype = GlpiMonitorType::class;
    public static $items_id = 'monitortypes_id';

    public static function getTypeName($nb = 0)
    {
        return _n("Power", "Powers", $nb, 'carbon');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        $tabNames = [];
        if (!$withtemplate) {
           if ($item->getType() == GlpiMonitorType::class) {
              $tabNames[1] = __('Carbon');
           }
        }
        return $tabNames;
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        /** @var GlpiComputerType $item */
        if ($item->getType() == GlpiMonitorType::class) {
            $typePower = new self();
            $typePower->getFromDBByCrit([$item->getForeignKeyField() => $item->getID()]);
            if ($typePower->isNewItem()) {
                $typePower->add([
                    $item->getForeignKeyField() => $item->getID()
                ]);
            }
            $typePower->showForComputerType($item);
        }
    }

    public function showForComputerType() {
        // TODO: Design a rights system for the whole plugin
        $canedit = Session::haveRight(Config::$rightname, UPDATE);

        $options = [
            'candel'   => false,
            'can_edit' => $canedit,
        ];
        $this->initForm($this->getID(), $options);
        TemplateRenderer::getInstance()->display('@carbon/monitortype.html.twig', [
            'params'   => $options,
            'item'     => $this,
        ]);
    }
}