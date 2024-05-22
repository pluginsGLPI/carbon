<?php

namespace GlpiPlugin\Carbon;

use Migration;

class Upgrade
{
    private Migration $migration;

    public function __construct(Migration $migration)
    {
        $this->migration = $migration;
    }

    /**
     * Run an upgrade of the plugin
     *
     * @return bool
     */
    protected function upgrade(): bool
    {
        return true;
    }
}