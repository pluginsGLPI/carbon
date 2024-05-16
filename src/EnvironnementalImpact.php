<?php

namespace GlpiPlugin\Carbon;

use Computer;
use CommonDBChild;
use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;

class EnvironnementalImpact extends CommonDBChild
{
    public static $itemtype = Computer::class;
    public static $items_id = 'computers_id';

    // Use core computer right
    public static $rightname = 'computer';

    public static function getTypeName($nb = 0)
    {
        return __('Environnemental impact', 'Carbon');
    }

    public static function getIcon()
    {
        return 'fa-solid fa-solar-panel';
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        $tabNames = [];
        if (!$withtemplate) {
           if ($item->getType() == Computer::class) {
              $tabNames[1] = self::getTypeName();
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
        /** @var Computer $item */
        if ($item->getType() == Computer::class) {
            $environnementalImpact = new self();
            $environnementalImpact->getFromDBByCrit([$item->getForeignKeyField() => $item->getID()]);
            if ($environnementalImpact->isNewItem()) {
                $environnementalImpact->add([
                    $item->getForeignKeyField() => $item->getID()
                ],
                [],
                false);
            }
            $environnementalImpact->showForComputer($environnementalImpact->getID());
        }
    }

    public function post_updateItem($history = true)
    {
        parent::post_updateItem($history);

        if (!$history) {
            return;
        }

        // foreach ($this->updates as $field) {
        //     Event::log(
        //         $_POST['id'],
        //         'computers',
        //         4,
        //         $field,
        //         //TRANS: %s is the user login
        //         sprintf(__('%s updates an item'), $_SESSION['glpiname'])
        //     );
        // }
    }

    public function showForComputer($ID, $withtemplate = '') {
        // TODO: Design a rights system for the whole plugin
        $canedit = self::canUpdate();

        $options = [
            'candel'   => false,
            'can_edit' => $canedit,
        ];
        $this->initForm($this->getID(), $options);
        TemplateRenderer::getInstance()->display('@carbon/environnementalimpact.html.twig', [
            'params'   => $options,
            'item'     => $this,
        ]);
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();
        $my_table = self::getTable();

        $tab[] = [
            'id'                 => '5',
            'table'              => $my_table,
            'field'              => Computer::getForeignKeyField(),
            'name'               => Computer::getTypeName(1),
            'datatype'           => 'linkfield',
            'nodisplay'          => true,
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $my_table,
            'field'              => ComputerUsageProfile::getForeignKeyField(),
            'name'               => ComputerUsageProfile::getTypeName(1),
            'datatype'           => 'linkfield',
            'nodisplay'          => true,
        ];

        return $tab;
    }
}