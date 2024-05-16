<?php

namespace GlpiPlugin\Carbon;

use CommonDropdown;
use CommonGLPI;
use Entity;
use Glpi\Application\View\TemplateRenderer;

/**
 * Usage profile of a computer
 */
class ComputerUsageProfile extends CommonDropdown
{
    public static function getTypeName($nb = 0)
    {
        return _n("Computer usage profile", "Computer usage profiles", $nb, 'power');
    }

    public static function canView()
    {
        return Entity::canView();
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        $env       = new self;
        $found_env = $env->find([static::getForeignKeyField() => $item->getID()]);
        $nb        = $_SESSION['glpishow_count_on_tabs'] ? count($found_env) : 0;
        return self::createTabEntry(self::getTypeName($nb), $nb);
     }

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        $new_item = static::isNewID($ID);
        $in_modal = (bool) ($_GET['_in_modal'] ?? false);
        TemplateRenderer::getInstance()->display('@carbon/computerusageprofile.html.twig', [
            'item'   => $this,
            'params' => $options,
            'no_header' => !$new_item && !$in_modal
        ]);
        return true;
    }

    public function getAdditionalFields() {
        return [
           [
              'name'      => 'time_start',
              'type'      => 'dropdownValue',
              'label'     => __('Knowbase category', 'formcreator'),
              'list'      => false
           ],
           [
              'name'      => 'time_stop',
              'type'      => 'parent',
              'label'     => __('As child of'),
              'list'      => false
           ]
        ];
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();
        $my_table = self::getTable();

        $tab[] = [
            'id'                 => '5',
            'table'              => $my_table,
            'field'              => 'average_load',
            'name'               => __('Average load', 'carbon'),
            'datatype'           => 'integer',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $my_table,
            'field'              => 'time_start',
            'name'               => __('Start time', 'carbon'),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $my_table,
            'field'              => 'time_stop',
            'name'               => __('Stop time', 'carbon'),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '11',
            'table'              => $my_table,
            'field'              => 'day_1',
            'name'               => __('Monday'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => $my_table,
            'field'              => 'day_2',
            'name'               => __('Tuesday'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '13',
            'table'              => $my_table,
            'field'              => 'day_3',
            'name'               => __('Wednesday'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '14',
            'table'              => $my_table,
            'field'              => 'day_4',
            'name'               => __('Thursday'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '15',
            'table'              => $my_table,
            'field'              => 'day_5',
            'name'               => __('Friday'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => $my_table,
            'field'              => 'day_6',
            'name'               => __('Saturday'),
            'datatype'           => 'bool',
        ];

        $tab[] = [
            'id'                 => '17',
            'table'              => $my_table,
            'field'              => 'day_7',
            'name'               => __('Sunday'),
            'datatype'           => 'bool',
        ];

        return $tab;
    }
}