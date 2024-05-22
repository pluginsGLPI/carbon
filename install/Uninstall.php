<?php

namespace GlpiPlugin\Carbon;

use Config;
use DBUtils;
use DisplayPreference;
use Migration;
use ProfileRight;

class Uninstall
{
    private Migration $migration;

    public function __construct(Migration $migration)
    {
        $this->migration = $migration;
    }

    public function uninstall()
    {
        global $DB;

        $itemtypesWihTable = [
            CarbonEmission::class,
            ComputerPower::class,
            ComputerType::class,
            ComputerUsageProfile::class,
            EnvironnementalImpact::class,
        ];
        $DbUtils = new DBUtils();
        foreach ($itemtypesWihTable as $itemtype) {
            $DB->dropTable($DbUtils->getTableForItemType($itemtype));
        }

        $this->deleteConfig();
        $this->deleteRights();

        return true;
    }

    private function deleteConfig()
    {
        $config = new Config();
        if (!$config->deleteByCriteria(['context' => 'plugin:carbon'])) {
            throw new \RuntimeException('Error while deleting config');
        }
    }

    private function deleteRights()
    {
        $profile_right = new ProfileRight();
        if (!$profile_right->deleteByCriteria([
            'name' => ['LIKE', 'carbon:%'],
        ])) {
            throw new \RuntimeException('Error while deleting rights');
        }
    }
}