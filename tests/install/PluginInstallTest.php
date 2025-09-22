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

namespace GlpiPlugin\Carbon\Tests;

use Session;
use Config;
use CronTask as GLPICronTask;
use DbUtils;
use Glpi\Dashboard\Dashboard;
use DisplayPreference;
use GLPIKey;
use Plugin;
use Profile;
use ProfileRight;
use Glpi\Dashboard\Item;
use Glpi\Dashboard\Right;
use Glpi\Plugin\Hooks;
use Glpi\System\Diagnostic\DatabaseSchemaIntegrityChecker;
use Glpi\Toolbox\Sanitizer;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\CarbonIntensitySource_Zone;
use GlpiPlugin\Carbon\Zone;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\CronTask;
use GlpiPlugin\Carbon\Report;

class PluginInstallTest extends CommonTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        self::login('glpi', 'glpi', true);
    }


    /**
     * Execute plugin installation in the context if tests
     *
     * @return void
     */
    protected function executeInstallation()
    {
        global $DB;

        $plugin_name = TEST_PLUGIN_NAME;

        $this->setupGLPIFramework();
        $this->assertTrue($DB->connected);

        //Drop plugin configuration if exists
        $config = new Config();
        $config->deleteByCriteria(['context' => $plugin_name]);

        // Drop tables of the plugin if they exist
        $result = $DB->listTables('glpi_plugin_' . $plugin_name . '_%');
        foreach ($result as $data) {
            $DB->dropTable($data['TABLE_NAME']);
        }

        // Reset logs
        $this->resetGLPILogs();

        $plugin = new Plugin();
        // Since GLPI 9.4 plugins list is cached
        $plugin->checkStates(true);
        $plugin->getFromDBbyDir($plugin_name);
        $this->assertFalse($plugin->isNewItem());

        // Install the plugin
        ob_start(function ($in) {
            return $in;
        });
        $plugin->install($plugin->fields['id']);
        $install_output = ob_get_contents();
        ob_end_clean();
        $this->assertTrue($plugin->isInstalled($plugin_name), $install_output);

        // Enable the plugin
        $plugin->activate($plugin->fields['id']);
        $plugin->init();
        $messages = $_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR] ?? [];
        $messages = implode(PHP_EOL, $messages);
        $this->assertTrue(Plugin::isPluginActive($plugin_name), 'Cannot enable the plugin: ' . $messages);
    }

    public function testInstallPlugin()
    {
        if (!Plugin::isPluginActive(TEST_PLUGIN_NAME)) {
            // For unit test script which expects that installation runs in the tests context
            $this->executeInstallation();
            GlobalFixture::loadDataset();
            $this->setupGLPIFramework();
        }
        $this->assertTrue(Plugin::isPluginActive(TEST_PLUGIN_NAME), 'Plugin not activated');
        $this->checkSchema(PLUGIN_CARBON_VERSION);

        $this->checkConfig();
        $this->checkAutomaticAction();
        $this->checkDashboard();
        $this->checkRights();
        $this->checkInitialDataSources();
        $this->checkInitialZones();
        $this->checkInitialCarbonIntensities();
        $this->checkDisplayPrefs();
        $this->checkPredefinedUsageProfiles();
        $this->checkBuitFiles();
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
        /** @var DBmysql $DB */
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

        $message = '';
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
                // echo $message . PHP_EOL;
                // echo $difference['diff'] . PHP_EOL;
                $message .= $message . PHP_EOL . $difference['diff'] . PHP_EOL;
            }

            $this->fail($message);
            return false;
        }

        return true;
    }

    private function checkAutomaticAction()
    {
        $cronTask = new GLPICronTask();
        $rows = $cronTask->find([
            'itemtype' => ['LIKE', '%' . 'Carbon' . '%'],
        ]);
        $this->assertEquals(5, count($rows));

        $cronTask = new GLPICronTask();
        $cronTask->getFromDBByCrit([
            'itemtype' => CronTask::class,
            'name'     => 'LocationCountryCode',
        ]);
        $this->assertFalse($cronTask->isNewItem());
        $this->assertEquals(10, $cronTask->fields['param']);

        $cronTask = new GLPICronTask();
        $cronTask->getFromDBByCrit([
            'itemtype' => CronTask::class,
            'name'     => 'UsageImpact',
        ]);
        $this->assertFalse($cronTask->isNewItem());
        $this->assertEquals(10000, $cronTask->fields['param']);

        $cronTask = new GLPICronTask();
        $cronTask->getFromDBByCrit([
            'itemtype' => CronTask::class,
            'name'     => 'DownloadRte',
        ]);
        $this->assertFalse($cronTask->isNewItem());

        $cronTask = new GLPICronTask();
        $cronTask->getFromDBByCrit([
            'itemtype' => CronTask::class,
            'name'     => 'DownloadElectricityMap',
        ]);
        $this->assertFalse($cronTask->isNewItem());

        $cronTask = new GLPICronTask();
        $cronTask->getFromDBByCrit([
            'itemtype' => CronTask::class,
            'name'     => 'EmbodiedImpact',
        ]);
        $this->assertFalse($cronTask->isNewItem());
    }

    protected function checkConfig()
    {
        $plugin_path = Plugin::getPhpDir(TEST_PLUGIN_NAME, true);
        require_once($plugin_path . '/setup.php');

        $expected = [
            'electricitymap_api_key' => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
            'impact_engine'          => 'Boavizta',
            'boaviztapi_base_url'    => '',
            'demo'                   => '1',
            'geocoding_enabled'      => '0',
            'dbversion'              => PLUGIN_CARBON_SCHEMA_VERSION,
        ];

        $config = Config::getConfigurationValues('plugin:' . TEST_PLUGIN_NAME);
        // Ignore keys that are not in the expected list
        $this->assertCount(count($expected), $config);

        $glpi_key = new GLPIKey();
        foreach ($expected as $key => $expected_value) {
            $value = $config[$key];
            if (!empty($value) && $glpi_key->isConfigSecured('plugin:carbon', $key)) {
                $value = $glpi_key->decrypt($config[$key]);
            }
            $this->assertEquals($expected_value, $value, "configuration key $key mismatch");
        }
    }

    private function checkRights()
    {
        // Key is ID of the profile, value is the name of the profile
        $expected_profiles = [
            4 =>  READ + UPDATE + PURGE, // 'Super-Admin'
        ];
        $this->checkRight(Report::$rightname, $expected_profiles);
    }

    private function checkRight(string $rightname, array $profiles)
    {
        global $DB;

        $profile_table = Profile::getTable();
        $profile_fk = Profile::getForeignKeyField();
        $profileright_table = ProfileRight::getTable();
        $request = [
            'SELECT' => [
                Profile::getTableField('id'),
                ProfileRight::getTableField('rights'),
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
                $this->assertEquals(0, $profile_right['rights']);
            } else {
                $this->assertEquals($profiles[$profile_right['id']], $profile_right['rights']);
            }
        }
    }

    private function checkDisplayPrefs()
    {
        $displayPreference = new DisplayPreference();

        $preferences = $displayPreference->find(['itemtype' => CarbonIntensity::class, 'users_id' => 0]);
        $this->assertEquals(5, count($preferences));

        $preferences = $displayPreference->find(['itemtype' => Zone::class, 'users_id' => 0]);
        $this->assertEquals(4, count($preferences));
    }

    private function checkInitialDataSources()
    {
        $sources = ['RTE', 'ElectricityMap'];
        foreach ($sources as $source_name) {
            $source = new CarbonIntensitySource();
            $source->getFromDBByCrit([
                'name' => Sanitizer::sanitize($source_name),
                'is_fallback' => 0
            ]);
            $this->assertFalse($source->isNewItem(), "Source '$source_name' not found");
        }

        $sources = ['Ember - Energy Institute', 'Hydro Quebec'];
        foreach ($sources as $source_name) {
            $source = new CarbonIntensitySource();
            $source->getFromDBByCrit([
                'name' => Sanitizer::sanitize($source_name),
                'is_fallback' => 1
            ]);
            $this->assertFalse($source->isNewItem(), "Source '$source_name' not found");
        }
    }

    private function checkInitialZones()
    {
        $total_count = 0;

        // Zones added from PHP code
        $zones = ['Quebec'];
        $total_count += count($zones);
        foreach ($zones as $zone_name) {
            $zone = new Zone();
            $zone->getFromDBByCrit([
                'name' => Sanitizer::sanitize($zone_name)
            ]);
            $this->assertFalse($zone->isNewItem(), "Zone '$zone_name' not found");
        }

        // zones added from CSV file
        $total_count += count($this->zones);
        foreach ($this->zones as $zone_name) {
            $zone = new Zone();
            $zone->getFromDBByCrit([
                'name' => Sanitizer::sanitize($zone_name)
            ]);
            $this->assertFalse($zone->isNewItem());
        }
    }

    private function checkInitialCarbonIntensities()
    {
        global $DB;

        // Find the source for Ember - Energy Institute
        $source_name = 'Ember - Energy Institute';
        $source = new CarbonIntensitySource();
        $source->getFromDBByCrit([
            'name' => Sanitizer::sanitize($source_name)
        ]);
        if ($source->isNewItem()) {
            $this->fail("$source_name not found");
        }

        $dbUtils = new DbUtils();
        $table = $dbUtils->getTableForItemType(CarbonIntensity::class);
        $count = $dbUtils->countElementsInTable($table, [
            $source::getForeignKeyField() => $source->getID(),
        ]);
        $this->assertEquals(5174, $count);

        // Find the zone
        $zone_name = 'Quebec';
        $zone = new Zone();
        $zone->getFromDBByCrit([
            'name' => $zone_name,
        ]);
        if ($zone->isNewItem()) {
            $this->fail("$zone_name zone not found");
        }

        // Find the source for Hydro Quebec
        $source_name = 'Hydro Quebec';
        $source = new CarbonIntensitySource();
        $source->getFromDBByCrit([
            'name' => $source_name
        ]);
        if ($source->isNewItem()) {
            $this->fail("$source_name not found");
        }

        $dbUtils = new DbUtils();
        $table = $dbUtils->getTableForItemType(CarbonIntensity::class);
        $count = $dbUtils->countElementsInTable($table, [
            $source::getForeignKeyField() => $source->getID(),
            $zone::getForeignKeyField() => $zone->getID(),
        ]);
        $this->assertEquals(1, $count);

        // Check all sources and zones are linked together via source_zone table
        $source_zone_table = getTableForItemType(CarbonIntensitySource_Zone::class);
        $zone_table = $dbUtils->getTableForItemType(Zone::class);
        $source_table = $dbUtils->getTableForItemType(CarbonIntensitySource::class);
        $source_fk = getForeignKeyFieldForItemType(CarbonIntensitySource::class);
        $zone_fk = getForeignKeyFieldForItemType(Zone::class);
        $iterator = $DB->request([
            'SELECT' => '*',
            'FROM' => $source_zone_table,
            'LEFT JOIN' => [
                $source_table => [
                    'FKEY' => [
                        $source_zone_table => $source_fk,
                        $source_table      => 'id',
                    ]
                ],
                $zone_table => [
                    'FKEY' => [
                        $source_zone_table => $zone_fk,
                        $zone_table        => 'id',
                    ]
                ],
            ],
            'WHERE' => [
                'OR' => [
                    $source_table . '.id' => null,
                    $zone_table   . '.id' => null,
                ]
            ]
        ]);

        $this->assertCount(0, $iterator);
    }

    private function checkPredefinedUsageProfiles()
    {
        $usage_profile = new ComputerUsageProfile();
        $rows = $usage_profile->find();
        $this->assertEquals(2, count($rows));
    }

    public function checkBuitFiles()
    {
        global $PLUGIN_HOOKS;

        $plugin_dir = dirname(__DIR__, 2);
        $this->assertTrue(file_exists($plugin_dir . '/public/lib/carbon.css'));
        $this->assertTrue(file_exists($plugin_dir . '/public/lib/carbon.js'));
        $this->assertTrue(file_exists($plugin_dir . '/public/lib/apexcharts.js'));

        $this->assertTrue(in_array('public/lib/carbon.css', $PLUGIN_HOOKS[Hooks::ADD_CSS]['carbon']));
        $this->assertTrue(in_array('public/lib/apexcharts.js', $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['carbon']));
    }

    public function checkDashboard()
    {
        // Check the dashboard exists
        $dashboard_key = 'plugin_carbon_board';
        $dashboard = new Dashboard();
        $dashboard->getFromDB($dashboard_key);
        $this->assertFalse($dashboard->isNewItem());

        // Check rights on the dashboard
        $right = new Right();
        $profile = new Profile();
        $profiles = $profile->find();
        $profile_itemtype = Profile::getType();
        foreach ($profiles as $profile) {
            $profile_right = new ProfileRight();
            $profile_right->getFromDBByCrit([
                'profiles_id' => $profile['id'],
                'name'        => 'config',
            ]);
            if ($profile_right->isNewItem()) {
                continue;
            }

            $rows = $right->find([
                'dashboards_dashboards_id' => $dashboard->fields['id'],
                'itemtype'                 => $profile_itemtype,
                'items_id'                 => $profile['id']
            ]);
            if (($profile_right->fields['rights'] && READ + UPDATE) != READ + UPDATE) {
                $this->assertCount(0, $rows);
            } else {
                $this->assertCount(1, $rows);
            }
        }

        // Check there is widgets in the dashboard
        $cards_path = Plugin::getPhpDir('carbon') . '/install/data/report_dashboard.json';
        $cards = file_get_contents($cards_path);
        $cards = json_decode($cards, true);
        $expected_cards_count = count($cards);
        $dashboardItem = new Item();
        $rows = $dashboardItem->find([
            'dashboards_dashboards_id' => $dashboard->fields['id'],
        ]);
        $this->assertCount($expected_cards_count, $rows);
    }

    private $zones = [
        'Afghanistan',
        'Albania',
        'Algeria',
        'American Samoa',
        'Angola',
        'Antigua and Barbuda',
        'Argentina',
        'Armenia',
        'Aruba',
        'Australia',
        'Austria',
        'Azerbaijan',
        'Bahamas',
        'Bahrain',
        'Bangladesh',
        'Barbados',
        'Belarus',
        'Belgium',
        'Belize',
        'Benin',
        'Bermuda',
        'Bhutan',
        'Bolivia',
        'Bosnia and Herzegovina',
        'Botswana',
        'Brazil',
        'British Virgin Islands',
        'Brunei',
        'Bulgaria',
        'Burkina Faso',
        'Burundi',
        'Cambodia',
        'Cameroon',
        'Canada',
        'Cape Verde',
        'Cayman Islands',
        'Central African Republic',
        'Chad',
        'Chile',
        'China',
        'Colombia',
        'Comoros',
        'Congo',
        'Cook Islands',
        'Costa Rica',
        'Cote d\'Ivoire',
        'Croatia',
        'Cuba',
        'Cyprus',
        'Czechia',
        'Democratic Republic of Congo',
        'Denmark',
        'Djibouti',
        'Dominica',
        'Dominican Republic',
        'Ecuador',
        'Egypt',
        'El Salvador',
        'Equatorial Guinea',
        'Eritrea',
        'Estonia',
        'Eswatini',
        'Ethiopia',
        'Falkland Islands',
        'Faroe Islands',
        'Fiji',
        'Finland',
        'France',
        'French Guiana',
        'French Polynesia',
        'Gabon',
        'Gambia',
        'Georgia',
        'Germany',
        'Ghana',
        'Gibraltar',
        'Greece',
        'Greenland',
        'Grenada',
        'Guadeloupe',
        'Guam',
        'Guatemala',
        'Guinea',
        'Guinea-Bissau',
        'Guyana',
        'Haiti',
        'Honduras',
        'Hong Kong',
        'Hungary',
        'Iceland',
        'India',
        'Indonesia',
        'Iran',
        'Iraq',
        'Ireland',
        'Israel',
        'Italy',
        'Jamaica',
        'Japan',
        'Jordan',
        'Kazakhstan',
        'Kenya',
        'Kiribati',
        'Kosovo',
        'Kuwait',
        'Kyrgyzstan',
        'Laos',
        'Latvia',
        'Lebanon',
        'Lesotho',
        'Liberia',
        'Libya',
        'Lithuania',
        'Luxembourg',
        'Macao',
        'Madagascar',
        'Malawi',
        'Malaysia',
        'Maldives',
        'Mali',
        'Malta',
        'Martinique',
        'Mauritania',
        'Mauritius',
        'Mexico',
        'Moldova',
        'Mongolia',
        'Montserrat',
        'Morocco',
        'Mozambique',
        'Myanmar',
        'Namibia',
        'Nauru',
        'Nepal',
        'Netherlands',
        'New Caledonia',
        'New Zealand',
        'Nicaragua',
        'Niger',
        'Nigeria',
        'North Korea',
        'North Macedonia',
        'Norway',
        'Oman',
        'Pakistan',
        'Palestine',
        'Panama',
        'Papua New Guinea',
        'Paraguay',
        'Peru',
        'Philippines',
        'Poland',
        'Portugal',
        'Puerto Rico',
        'Qatar',
        'Reunion',
        'Romania',
        'Russia',
        'Rwanda',
        'Saint Helena',
        'Saint Kitts and Nevis',
        'Saint Lucia',
        'Saint Pierre and Miquelon',
        'Saint Vincent and the Grenadines',
        'Samoa',
        'Sao Tome and Principe',
        'Saudi Arabia',
        'Senegal',
        'Serbia',
        'Seychelles',
        'Sierra Leone',
        'Singapore',
        'Slovakia',
        'Slovenia',
        'Solomon Islands',
        'Somalia',
        'South Africa',
        'South Korea',
        'Spain',
        'Sri Lanka',
        'Sudan',
        'Suriname',
        'Sweden',
        'Switzerland',
        'Syria',
        'Taiwan',
        'Tajikistan',
        'Tanzania',
        'Thailand',
        'Togo',
        'Tonga',
        'Trinidad and Tobago',
        'Tunisia',
        'Turkey',
        'Turkmenistan',
        'Turks and Caicos Islands',
        'Uganda',
        'Ukraine',
        'United Arab Emirates',
        'United Kingdom',
        'United States',
        'United States Virgin Islands',
        'Uruguay',
        'Uzbekistan',
        'Vanuatu',
        'Venezuela',
        'Vietnam',
        'Western Sahara',
        'World',
        'Yemen',
        'Zambia',
        'Zimbabwe',
        'East Timor',
        'Montenegro',
        'South Sudan'
    ];
}
