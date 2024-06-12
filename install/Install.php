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

use Config;
use CronTask;
use Migration;
use ProfileRight;
use Profile;

class Install
{
    private Migration $migration;

    public function __construct(Migration $migration)
    {
        $this->migration = $migration;
    }

    /**
     * Determine if the plugin is already installed then run a fresh install
     * or an upgrade
     *
     * @return void
     */
    public function install(): bool
    {
        // TODO: check if plugin is installed
        return $this->freshInstall();
    }

    public function freshInstall(): bool
    {
        global $DB;

        $config = new Config();
        $config->setConfigurationValues('plugin:carbon', ['configuration' => false]);

        $this->migration = new Migration(PLUGIN_CARBON_VERSION);

        $dbFile = plugin_carbon_getSchemaPath();
        if ($dbFile === null || !$DB->runFile($dbFile)) {
            $this->migration->displayWarning("Error creating tables : " . $DB->error(), true);
            die('Giving up');
         }

        $this->createConfig();
        $this->createAutomaticActions();
        $this->createRights();
        $this->createDisplayPrefs();

        return true;
    }

    private function createConfig()
    {
        $current_config = Config::getConfigurationValues('plugin:carbon');
        $config_entries = [
            'electricitymap_api_key'              => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
            'electricitymap_base_url'             => 'https://api.electricitymap.org/ZZZZZZZZZZZZZZv4/',
            'co2signal_api_key'                   => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
        ];
        foreach ($config_entries as $key => $value) {
            if (!isset($current_config[$key])) {
                Config::setConfigurationValues('plugin:carbon', [$key => $value]);
            }
        }

    }

    /**
     * Create and grant new rights for the plugin
     *
     * @return void
     */
    private function createRights()
    {
        global $DB;

        $profiles = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => Profile::getTable(),
         ]);
         foreach ($profiles as $profile) {
            $rights = ProfileRight::getProfileRights(
                $profile['id'],
                [
                    Config::$rightname,
                    Report::$rightname,
                ]
            );
            if (($rights[Config::$rightname] & (READ + UPDATE)) != READ + UPDATE) {
               continue;
            }
            $right = READ;
            ProfileRight::updateProfileRights($profile['id'], [
               Report::$rightname => $right,
            ]);
        }
    }

    private function createAutomaticActions()
    {
        $name = 'ComputePowersTask';
        $success = CronTask::Register(
            ComputerPower::class,
            $name,
            DAY_TIMESTAMP,
            [
                'mode' => CronTask::MODE_INTERNAL,
                'allowmode' => CronTask::MODE_INTERNAL + CronTask::MODE_EXTERNAL,
                'logs_lifetime' => 30,
                'comment' => __('Computes power consumption of computers', 'carbon'),
            ]
        );
        if (!$success) {
            throw new \RuntimeException('Error while creating automatic action: ' . $name);
        }

        $name = 'ComputeCarbonEmissionsTask';
        CronTask::Register(
            CarbonEmission::class,
            $name,
            DAY_TIMESTAMP,
            [
                'mode' => CronTask::MODE_INTERNAL,
                'allowmode' => CronTask::MODE_INTERNAL + CronTask::MODE_EXTERNAL,
                'logs_lifetime' => 30,
                'comment' => __('Computes carbon emissions of computers', 'carbon'),
            ]
        );
        if (!$success) {
            throw new \RuntimeException('Error while creating automatic action: ' . $name);
        }
    }

    private function createDisplayPrefs()
    {
        $this->migration->updateDisplayPrefs([
            CarbonIntensity::class => [
                2, 3, 4, 5, 6
            ]
            ],
            [],
            true
        );
    }
}