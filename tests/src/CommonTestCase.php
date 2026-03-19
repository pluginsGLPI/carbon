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

use Auth;
use CommonDBTM;
use Computer as GlpiComputer;
use ComputerModel as GlpiComputerModel;
use ComputerType as GlpiComputerType;
use DateInterval;
use DateTime;
use DateTimeInterface;
use Entity;
use Glpi\Asset\Asset_PeripheralAsset;
use Glpi\Inventory\Conf;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\CarbonIntensity;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\DataTracking\AbstractTracked;
use GlpiPlugin\Carbon\Location;
use GlpiPlugin\Carbon\Source;
use GlpiPlugin\Carbon\Source_Zone;
use GlpiPlugin\Carbon\UsageInfo;
use GlpiPlugin\Carbon\Zone;
use Location as GlpiLocation;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Session;
use Toolbox;

class CommonTestCase extends TestCase
{
    /** @var int $debugMode save state of GLPI debug mode */
    private $debugMode = null;

    protected $str = null;

    protected function disableDebug()
    {
        $this->debugMode = Session::DEBUG_MODE;
        if (isset($_SESSION['glpi_use_mode'])) {
            $this->debugMode = $_SESSION['glpi_use_mode'];
        }
        Toolbox::setDebugMode(Session::NORMAL_MODE);
    }

    protected function restoreDebug()
    {
        Toolbox::setDebugMode($this->debugMode);
    }

    protected function setUp(): void
    {
        $this->resetGLPILogs();
    }

    protected function tearDown(): void
    {
        $logs = ['php-errors.log', 'sql-errors.log'];
        foreach ($logs as $log) {
            if (!file_exists(GLPI_LOG_DIR . '/' . $log)) {
                // continue;
            }

            $log_content = file_get_contents(GLPI_LOG_DIR . "/$log");
            $this->assertEquals('', $log_content, "log not empty");
        }
    }

    protected function resetGLPILogs()
    {
        // Reset error logs
        file_put_contents(GLPI_LOG_DIR . "/sql-errors.log", '');
        file_put_contents(GLPI_LOG_DIR . "/php-errors.log", '');
    }

    /**
     * @deprecated not replaced
     *
     * @return void
     */
    protected function setupGLPIFramework(): void
    {
        return;
    }

    protected function login($name, $password, $noauto = false)
    {
        Session::start();
        $auth = new Auth();
        $this->disableDebug();
        $result = $auth->login($name, $password, $noauto);
        $this->restoreDebug();
        $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];

        return $result;
    }

    protected function logout()
    {
        Session::destroy();
        Session::start();
    }

    /**
     * Get a unique random string
     */
    protected function getUniqueString()
    {
        if (is_null($this->str)) {
            return $this->str = uniqid('str');
        }
        return $this->str .= 'x';
    }

    /**
     * @template T of CommonDBTM
     * @param class-string<T> $itemtype itemtype to create
     * @param array $crit
     * @return T
     */
    protected function getItem(string $itemtype, array $crit = []): CommonDBTM
    {
        $item = new $itemtype();
        $this->assertTrue($item->getFromDBByRequest($crit));
        return $item;
    }


    /**
     * Create an item of the given itemtype
     *
     * @template T of CommonDBTM
     * @param class-string<T> $itemtype itemtype to create
     * @param array $input
     * @return T
     */
    protected function createItem(string $itemtype, array $input = []): CommonDBTM
    {
        global $DB;

        $this->handleDeprecations($itemtype, $input);

        /** @var CommonDBTM */
        $item = new $itemtype();

        // set random name if not already set
        if (!isset($item->fields['name']) && $DB->fieldExists($item->getTable(), 'name')) {
            if (!isset($input['name'])) {
                $input['name'] = $this->getUniqueString();
            }
        }

        // assign entity if not already set
        if ($item->isEntityAssign()) {
            $entity = 0;
            if (Session::getLoginUserID(true)) {
                $entity = Session::getActiveEntity();
            }
            if (!isset($input[Entity::getForeignKeyField()])) {
                $input[Entity::getForeignKeyField()] = $entity;
            }
        }

        // assign recursiviy if not already set
        if ($item->maybeRecursive()) {
            if (!isset($input['is_recursive'])) {
                $input['is_recursive'] = 0;
                // if (Session::getLoginUserID(true)) {
                //     $input['is_recursive'] = Session::haveRecursiveAccessToEntity($entity) ? 1 : 0;
                // }
            }
        }

        $item->add($input);
        $this->assertFalse($item->isNewItem(), $this->getSessionMessage());

        // Reload the item to ensure that all fields are set
        $this->assertTrue($item->getFromDB($item->getID()));

        return $item;
    }

    /**
     * @deprecated use createItems insteaad
     *
     * @param array $batch
     * @return array
     */
    public function getItems(array $batch): array
    {
        return $this->createItems($batch);
    }

    public function createItems(array $batch): array
    {
        $output = [];

        foreach ($batch as $itemtype => $items) {
            foreach ($items as $data) {
                $item = $this->createItem($itemtype, $data);
                $output[$itemtype][$item->getID()] = $item;
            }
        }

        return $output;
    }

    public function updateItem(CommonDBTM $item, array $input): CommonDBTM
    {
        $success = $item->update(['id' => $item->fields['id']] + $input);
        $this->assertTrue($success);
        return $item;
    }

    public function deleteItem(CommonDBTM $item, bool $force = false): bool
    {
        $success = $item->delete($item->fields, $force);
        $this->assertTrue($success);
        return $success;
    }

    protected function createComputerUsageProfile(array $usage_profile_params): GlpiComputer
    {
        $usage_profile = $this->createItem(ComputerUsageProfile::class, $usage_profile_params);
        $glpi_computer = $this->createItem(GlpiComputer::class);
        $impact = $this->createItem(UsageInfo::class, [
            'itemtype' => GlpiComputer::class,
            'items_id' => $glpi_computer->getId(),
            ComputerUsageProfile::getForeignKeyField() => $usage_profile->getID(),
        ]);

        return $glpi_computer;
    }

    protected function createComputerUsageProfilePower(array $usage_profile_params, int $type_power): GlpiComputer
    {
        $glpi_computer = $this->createComputerUsageProfile($usage_profile_params);
        $glpiComputerType = $this->createItem(GlpiComputerType::class);
        $carbonComputerType = $this->createItem(ComputerType::class, [
            GlpiComputerType::getForeignKeyField() => $glpiComputerType->getID(),
            'power_consumption'                    => $type_power,
        ]);
        $glpi_computer->update([
            'id'                                   => $glpi_computer->getID(),
            GlpiComputerType::getForeignKeyField() => $glpiComputerType->getID(),
        ]);

        return $glpi_computer;
    }

    protected function createComputerUsageProfilePowerLocation(array $usage_profile_params, int $type_power, Source_Zone $source_zone): GlpiComputer
    {
        $glpi_computer = $this->createComputerUsageProfilePower($usage_profile_params, $type_power);

        $glpi_location = $this->createItem(GlpiLocation::class);
        $location = $this->createItem(Location::class, [
            'locations_id' => $glpi_location->getID(),
            $source_zone::getForeignKeyField() => $source_zone->getID(),
        ]);
        $glpi_computer->update([
            'id'                               => $glpi_computer->getID(),
            GlpiLocation::getForeignKeyField() => $glpi_location->getID(),
        ]);

        return $glpi_computer;
    }

    protected function createCarbonIntensityData(Source_Zone $source_zone, DateTimeInterface $begin_date, float $intensity, string $length = 'P2D')
    {

        $current_date = clone $begin_date;
        $current_date->sub(new DateInterval('P1D'));
        $end_date = clone $current_date;
        $end_date->add(new DateInterval($length));
        $one_hour = new DateInterval('PT1H');
        while ($current_date < $end_date) {
            $crit = [
                Source::getForeignKeyField()  => $source_zone->fields[Source::getForeignKeyField()],
                Zone::getForeignKeyField() => $source_zone->fields[Zone::getForeignKeyField()],
                'date' => $current_date->format('Y-m-d H:00:00'),
                'intensity' => $intensity,
            ];
            $item = $this->createItem(
                CarbonIntensity::class,
                $crit + [
                    'data_quality' => AbstractTracked::DATA_QUALITY_MANUAL,
                ],
            );
            $current_date->add($one_hour);
        }
    }

    protected function createCarbonEmissionData(CommonDBTM $item, DateTime $start, DateInterval $length, float $energy, float $emission)
    {
        $required_fields = [
            'computertypes_id',
            'computermodels_id',
            'locations_id',
        ];
        if (count(array_intersect_key(array_flip($required_fields), $item->fields)) < count($required_fields)) {
            // Abort test
            throw new \LogicException('Not all required fields are set');
        }

        $date_current = clone $start;
        $date_stop = $start->add($length);
        while ($date_current < $date_stop) {
            $itemtype = $item->getType();
            $items_id = $item->getID();
            $this->createItem(CarbonEmission::class, [
                'itemtype'         => $itemtype,
                'items_id'         => $items_id,
                'entities_id'      => Session::getActiveEntity(),
                'types_id'         => $item->fields['computertypes_id'],
                'models_id'        => $item->fields['computermodels_id'],
                'locations_id'     => $item->fields['locations_id'],
                'energy_per_day'   => $energy,
                'emission_per_day' => $emission,
                'date'             => $date_current->format('Y-m-d H:i:s'),
            ]);
            $date_current = $date_current->add(new DateInterval('P1D'));
        }
    }

    /**
     * Build a computer and all its data to make it historiable
     * except the data provided in argument
     *
     * @param array $skip
     * @return GlpiComputer
     */
    protected function createHistorizableComputer(array $skip = []): GlpiComputer
    {
        if (!in_array(GlpiComputerType::class, $skip)) {
            $glpi_computer_type = $this->createItem(GlpiComputerType::class);
        }
        if (!in_array(ComputerType::class, $skip)) {
            $computer_type = $this->createItem(ComputerType::class, [
                'computertypes_id' => isset($glpi_computer_type) ? $glpi_computer_type->getID() : 0,
                'power_consumption' => !in_array(ComputerType::class . '_power', $skip) ? 90 : 0,
            ]);
        }
        if (!in_array(GlpiComputerModel::class, $skip)) {
            $glpi_computer_model = $this->createItem(GlpiComputerModel::class, [
                'power_consumption' => !in_array(GlpiComputerModel::class . '_power', $skip) ? 150 : 0,
            ]);
        }
        if (!in_array(Zone::class, $skip)) {
            $zone = $this->createItem(Zone::class);
        }
        if (!in_array(Source::class, $skip)) {
            $source = $this->createItem(Source::class, [
                'is_carbon_intensity_source' => 1,
                'fallback_level' => 0,
            ]);
        }
        if (!in_array(Source_Zone::class, $skip)) {
            $source_zone = $this->createItem(Source_Zone::class, [
                'plugin_carbon_sources_id' => isset($source) ? $source->getID() : 0,
                'plugin_carbon_zones_id'   => isset($zone) ? $zone->getID() : 0,
            ]);
        }
        if (!in_array(CarbonIntensity::class, $skip)) {
            $carbon_intensity = $this->createItem(CarbonIntensity::class, [
                'plugin_carbon_sources_id' => isset($source) ? $source->getID() : 0,
                'plugin_carbon_zones_id'   => isset($zone) ? $zone->getID() : 0,
            ]);
        }
        if (!in_array('fallback_' . Source::class, $skip)) {
            $fallback_source = $this->createItem(Source::class, [
                'is_carbon_intensity_source' => 1,
                'fallback_level' => 1,
            ]);
        }
        if (!in_array('fallback_' . Source_Zone::class, $skip)) {
            $fallback_source_zone = $this->createItem(Source_Zone::class, [
                'plugin_carbon_sources_id' => isset($fallback_source) ? $fallback_source->getID() : 0,
                'plugin_carbon_zones_id'   => isset($zone) ? $zone->getID() : 0,
            ]);
        }
        if (!in_array('fallback_' . CarbonIntensity::class, $skip)) {
            $fallback_carbon_intensity = $this->createItem(CarbonIntensity::class, [
                'plugin_carbon_sources_id' => isset($fallback_source) ? $fallback_source->getID() : 0,
                'plugin_carbon_zones_id'   => isset($zone) ? $zone->getID() : 0,
            ]);
        }
        if (!in_array('2nd_fallback_' . Source::class, $skip)) {
            $fallback_source = $this->createItem(Source::class, [
                'is_carbon_intensity_source' => 1,
                'fallback_level' => 2,
            ]);
        }
        if (!in_array('2nd_fallback_' . Source_Zone::class, $skip)) {
            $fallback_source_zone = $this->createItem(Source_Zone::class, [
                'plugin_carbon_sources_id' => isset($fallback_source) ? $fallback_source->getID() : 0,
                'plugin_carbon_zones_id'   => isset($zone) ? $zone->getID() : 0,
            ]);
        }
        if (!in_array('2nd_fallback_' . CarbonIntensity::class, $skip)) {
            $fallback_carbon_intensity = $this->createItem(CarbonIntensity::class, [
                'plugin_carbon_sources_id' => isset($fallback_source) ? $fallback_source->getID() : 0,
                'plugin_carbon_zones_id'   => isset($zone) ? $zone->getID() : 0,
            ]);
        }
        if (!in_array(GlpiLocation::class, $skip)) {
            $glpi_location = $this->createItem(GlpiLocation::class);
        }
        if (!in_array(Location::class, $skip)) {
            $location = $this->createItem(Location::class, [
                'locations_id' => isset($glpi_location) ? $glpi_location->getID() : 0,
                Source_Zone::getForeignKeyField() => isset($source_zone) ? $source_zone->getID() : 0,
            ]);
        }
        $glpi_computer = $this->createItem(GlpiComputer::class, [
            GlpiLocation::getForeignKeyField()      => isset($glpi_location) ? $glpi_location->getID() : 0,
            GlpiComputerType::getForeignKeyField()  => isset($glpi_computer_type) ? $glpi_computer_type->getID() : 0,
            GlpiComputerModel::getForeignKeyField() => isset($glpi_computer_model) ? $glpi_computer_model->getID() : 0,
        ]);
        if (!in_array(ComputerUsageProfile::class, $skip)) {
            $usage_profile = $this->createItem(ComputerUsageProfile::class);
        }
        if (!in_array(UsageInfo::class, $skip)) {
            $impact = $this->createItem(UsageInfo::class, [
                'itemtype' => $glpi_computer::getType(),
                'items_id' => $glpi_computer->getID(),
                'plugin_carbon_computerusageprofiles_id' => isset($usage_profile) ? $usage_profile->getID() : 0,
            ]);
        }
        return $glpi_computer;
    }

    protected function getSessionMessage(): string
    {
        if (
            isset($_SESSION['MESSAGE_AFTER_REDIRECT'][INFO])
            || isset($_SESSION['MESSAGE_AFTER_REDIRECT'][WARNING])
            || isset($_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR])
        ) {
            return '';
        }

        $messages = '';
        if (isset($_SESSION['MESSAGE_AFTER_REDIRECT'][INFO])) {
            $messages .= implode(' ', $_SESSION['MESSAGE_AFTER_REDIRECT'][INFO]);
        }
        if (isset($_SESSION['MESSAGE_AFTER_REDIRECT'][WARNING])) {
            $messages .= ' ' . implode(' ', $_SESSION['MESSAGE_AFTER_REDIRECT'][WARNING]);
        }
        if (isset($_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR])) {
            $messages .= ' ' . implode(' ', $_SESSION['MESSAGE_AFTER_REDIRECT'][ERROR]);
        }
        return $messages;
    }

    /**
     * Handle deprecations in GLPI
     * Helps to make unit tests without deprecations warnings, accross 2 version of GLPI
     */
    private function handleDeprecations(&$itemtype, &$input): void
    {
        if (version_compare(GLPI_VERSION, '11.0.0-beta') >= 0) {
            if ($itemtype === \Computer_Item::class) {
                $itemtype = Asset_PeripheralAsset::class;
                $input['itemtype_asset'] = GlpiComputer::class;
                $input['items_id_asset'] = $input['computers_id'];
                $input['itemtype_peripheral'] = $input['itemtype'];
                $input['items_id_peripheral'] = $input['items_id'];
                unset($input['computers_id']);
                unset($input['itemtype']);
                unset($input['items_id']);
            }
        }
    }

    protected function importInventory(array $files)
    {
        $inventory = new Conf();
        return $inventory->importFiles($files);
    }

    /**
     * Create an entity and switch to it
     *
     * @return int
     */
    protected function isolateInEntity(): int
    {
        $entity      = new Entity();
        $rand        = mt_rand();
        $entities_id = $entity->add([
            'name'        => "test sub entity $rand",
            'entities_id' => 0,
        ]);

        $success = Session::changeActiveEntities($entities_id);
        $this->assertTrue($success, 'Failed to change active entity');

        return $entities_id;
    }

    /**
     * Call a private method, and get its return value.
     *
     * @param mixed     $instance   Class instance
     * @param string    $methodName Method to call
     * @param mixed     ...$arg     Method arguments
     *
     * @return mixed
     */
    protected function callPrivateMethod($instance, string $methodName, ...$args)
    {
        $method = new ReflectionMethod($instance, $methodName);
        if (version_compare(PHP_VERSION, '8.1.0') < 0) {
            $method->setAccessible(true);
        }

        return $method->invoke($instance, ...$args);
    }
}
