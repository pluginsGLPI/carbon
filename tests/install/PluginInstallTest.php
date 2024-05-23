<?php

namespace GlpiPlugin\Carbon\Tests;

use Session;
use Config;
use CronTask;
use DisplayPreference;
use Plugin;
use Profile;
use ProfileRight;
use Glpi\System\Diagnostic\DatabaseSchemaIntegrityChecker;
use GlpiPlugin\Carbon\ComputerPower;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\Report;

class PluginInstallTest extends CommonTestCase
{

    public function setUp(): void
    {
        //   parent::setUp();
        //   self::setupGLPIFramework();
        self::login('glpi', 'glpi', true);
    }

    public function testInstallPlugin()
    {
        global $DB;

        $pluginName = TEST_PLUGIN_NAME;

        $this->setupGLPIFramework();
        $this->assertTrue($DB->connected);

        //Drop plugin configuration if exists
        $config = new Config;
        $config->deleteByCriteria(['context' => $pluginName]);

        // Drop tables of the plugin if they exist
        $result = $DB->listTables('glpi_plugin_' . $pluginName . '_%');
        foreach ($result as $data) {
            $DB->dropTable($data['TABLE_NAME']);
        }

        // Reset logs
        $this->resetGLPILogs();

        $plugin = new Plugin();
        // Since GLPI 9.4 plugins list is cached
        $plugin->checkStates(true);
        $plugin->getFromDBbyDir($pluginName);
        $this->assertFalse($plugin->isNewItem());

        // Install the plugin
        ob_start(function ($in) {
            return $in;
        });
        $plugin->install($plugin->fields['id']);
        $installOutput = ob_get_contents();
        ob_end_clean();
        $this->assertTrue($plugin->isInstalled($pluginName), $installOutput);

        // Enable the plugin
        $plugin->activate($plugin->fields['id']);
        $plugin->init();
        $messages = $_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR] ?? [];
        $messages = implode(PHP_EOL, $messages);
        $this->assertTrue($plugin->isActivated($pluginName), 'Cannot enable the plugin: ' . $messages);

        $this->checkSchema(PLUGIN_CARBON_VERSION);

        $this->checkConfig();
        // $this->checkRequestType();
        $this->checkAutomaticAction();
        // $this->checkDashboard();
        $this->checkRights();

        $this->checkDisplayPrefs();
    }

    public function testConfigurationExists()
    {
        $config = Config::getConfigurationValues(TEST_PLUGIN_NAME);
        $expected = [];
        $diff = array_diff_key(array_flip($expected), $config);
        $this->assertEquals(0, count($diff));

        return $config;
    }

    private function checkSchema(
        string $version,
        bool $strict = true,
        bool $ignore_innodb_migration = false,
        bool $ignore_timestamps_migration = false,
        bool $ignore_utf8mb4_migration = false,
        bool $ignore_dynamic_row_format_migration = false,
        bool $ignore_unsigned_keys_migration = false
    ): bool {
        global $DB;

        $schemaFile = plugin_carbon_getSchemaPath($version);

        $checker = new DatabaseSchemaIntegrityChecker(
            $DB,
            $strict,
            $ignore_innodb_migration,
            $ignore_timestamps_migration,
            $ignore_utf8mb4_migration,
            $ignore_dynamic_row_format_migration,
            $ignore_unsigned_keys_migration
        );

        try {
            $differences = $checker->checkCompleteSchema($schemaFile, true, 'plugin:carbon');
        } catch (\Throwable $e) {
            $message = __('Failed to check the sanity of the tables!', 'carbon');
            if (isCommandLine()) {
                echo $message . PHP_EOL;
            } else {
                Session::addMessageAfterRedirect($message, false, ERROR);
            }
            return false;
        }

        if (count($differences) > 0) {
            foreach ($differences as $table_name => $difference) {
                $message = null;
                switch ($difference['type']) {
                    case DatabaseSchemaIntegrityChecker::RESULT_TYPE_ALTERED_TABLE:
                        $message = sprintf(__('Table schema differs for table "%s".'), $table_name);
                        break;
                    case DatabaseSchemaIntegrityChecker::RESULT_TYPE_MISSING_TABLE:
                        $message = sprintf(__('Table "%s" is missing.'), $table_name);
                        break;
                    case DatabaseSchemaIntegrityChecker::RESULT_TYPE_UNKNOWN_TABLE:
                        $message = sprintf(__('Unknown table "%s" has been found in database.'), $table_name);
                        break;
                }
                echo $message . PHP_EOL;
                echo $difference['diff'] . PHP_EOL;
            }
            return false;
        }

        return true;
    }

    private function checkAutomaticAction()
    {
        $cronTask = new CronTask();
        $cronTask->getFromDBByCrit([
            'itemtype' => ComputerPower::class,
            'name'     => 'ComputePowersTask',
        ]);
        $this->assertFalse($cronTask->isNewItem());

        $cronTask = new CronTask();
        $cronTask->getFromDBByCrit([
            'itemtype' => CarbonEmission::class,
            'name'     => 'ComputeCarbonEmissionsTask',
        ]);
        $this->assertFalse($cronTask->isNewItem());
    }

    private function checkConfig()
    {
        $config = Config::getConfigurationValues('plugin:' . TEST_PLUGIN_NAME);
        $this->assertCount(4, $config);

        $expected = [
            'configuration'           => false,
            'electricitymap_api_key'  => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
            'electricitymap_base_url' => 'https://api.electricitymap.org/ZZZZZZZZZZZZZZv4/',
            'co2signal_api_key'       => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
        ];
        $this->assertEqualsCanonicalizing($expected, $config);
    }

    private function checkRights() {
        // Key is ID of the profile, value is the name of the profile
        $expected_profiles = [
            4 =>  READ, // 'Super-Admin'
        ];
        $this->checkRight(Report::$rightname, $expected_profiles);
    }

    private function checkRight(string $rightname, array $profiles) {
        global $DB;

        $profile_table = Profile::getTable();
        $profile_fk = Profile::getForeignKeyField();
        $profileright_table = ProfileRight::getTable();
        $request = [
            'SELECT' => [
                ProfileRight::getTableField('id'),
                Profile::getTableField('id'),
            ],
            'FROM' => $profile_table,
            'LEFT JOIN' => [
                $profileright_table => [
                    'FKEY' => [
                        $profile_table => 'id',
                        $profileright_table => $profile_fk,
                    ],
                ],
            ],
            'WHERE' => [
                ProfileRight::getTableField('name') => $rightname,
            ]
        ];

        foreach ($DB->request($request) as $profile_right) {
            if (!isset($profiles[$profile_right['id']])) {
                $this->assertEquals(0, $profiles[$profile_right['id']]);
            } else {
                $this->assertEquals(0, $profile_right['rights']);
            }
        }
    }

    private function checkDisplayPrefs()
    {
        $displayPreference = new DisplayPreference();
        $preferences = $displayPreference->find(['itemtype' => CarbonIntensity::class, 'users_id' => 0]);

        $this->assertEquals(5, count($preferences));
    }
}
