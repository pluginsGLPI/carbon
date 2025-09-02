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

use PHPUnit\Framework\TestCase;
use Glpi\Application\Environment;
use Glpi\Kernel\Kernel;
use Auth;
use CommonDBTM;
use Computer;
use ComputerType as GlpiComputerType;
use DateTime;
use DateInterval;
use DateTimeInterface;
use Glpi\Inventory\Conf;
use GlpiPlugin\Carbon\UsageInfo;
use GlpiPlugin\Carbon\ComputerUsageProfile;
use GlpiPlugin\Carbon\ComputerType;
use GlpiPlugin\Carbon\CarbonIntensitySource;
use GlpiPlugin\Carbon\Zone;
use GlpiPlugin\Carbon\CarbonIntensity;
use Entity;
use GlpiPlugin\Carbon\CarbonEmission;
use GlpiPlugin\Carbon\DataTracking\AbstractTracked;
use Location;
use ReflectionMethod;
use Session;
use Ticket;
use Toolbox;
use User;

class CommonTestCase extends TestCase
{
    /** @var integer $debugMode save state of GLPI debug mode */
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
    protected function setupGLPIFramework():void
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
     * @deprecated use createItem instead
     */
    protected function getItem(string $itemtype, array $input = []): CommonDBTM
    {
        return $this->createItem($itemtype, $input);
    }

    /**
     * Create an item of the given itemtype
     *
     * @param string $itemtype itemtype to create
     * @param array $input
     * @return CommonDBTM
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
                $item = $this->getItem($itemtype, $data);
                $output[$itemtype][$item->getID()] = $item;
            }
        }

        return $output;
    }

    public function updateItem(CommonDBTM $item, array $input): CommonDBTM
    {
        $sucess = $item->update(['id' => $item->fields['id']] + $input);
        $this->assertTrue($sucess);
        return $item;
    }

    protected function createComputerUsageProfile(array $usage_profile_params): Computer
    {
        $usage_profile = $this->getItem(ComputerUsageProfile::class, $usage_profile_params);
        $glpi_computer = $this->getItem(Computer::class);
        $impact = $this->getItem(UsageInfo::class, [
            'itemtype' => Computer::class,
            'items_id' => $glpi_computer->getId(),
            ComputerUsageProfile::getForeignKeyField() => $usage_profile->getID(),
        ]);

        return $glpi_computer;
    }

    protected function createComputerUsageProfilePower(array $usage_profile_params, int $type_power): Computer
    {
        $glpi_computer = $this->createComputerUsageProfile($usage_profile_params);
        $glpiComputerType = $this->getItem(GlpiComputerType::class);
        $carbonComputerType = $this->getItem(ComputerType::class, [
            GlpiComputerType::getForeignKeyField() => $glpiComputerType->getID(),
            'power_consumption'                    => $type_power,
        ]);
        $glpi_computer->update([
            'id'                                   => $glpi_computer->getID(),
            GlpiComputerType::getForeignKeyField() => $glpiComputerType->getID(),
        ]);

        return $glpi_computer;
    }

    protected function createComputerUsageProfilePowerLocation(array $usage_profile_params, int $type_power, string $country): Computer
    {
        $glpi_computer = $this->createComputerUsageProfilePower($usage_profile_params, $type_power);

        $location = $this->getItem(
            Location::class,
            [
                'country' => $country,
            ]
        );
        $glpi_computer->update([
            'id'                                => $glpi_computer->getID(),
            Location::getForeignKeyField()      => $location->getID(),
        ]);

        return $glpi_computer;
    }

    protected function createCarbonIntensityData(string $country, string $source_name, DateTimeInterface $begin_date, float $intensity, string $length = 'P2D')
    {
        $source = new CarbonIntensitySource();
        $source->getFromDBByCrit(['name' => $source_name]);
        if ($source->isNewItem()) {
            $source = $this->getItem(CarbonIntensitySource::class, [
                'name' => $source_name
            ]);
        }

        $zone = new Zone();
        $zone->getFromDBByCrit(['name' => $country]);
        if ($zone->isNewItem()) {
            $zone = $this->getItem(Zone::class, [
                'name' => $country,
                'plugin_carbon_carbonintensitysources_id_historical' => $source->getID()
            ]);
        }

        $current_date = clone $begin_date;
        $current_date->sub(new DateInterval('P1D'));
        $end_date = clone $current_date;
        $end_date->add(new DateInterval($length));
        $one_hour = new DateInterval('PT1H');
        while ($current_date < $end_date) {
            $crit = [
                CarbonIntensitySource::getForeignKeyField()  => $source->getID(),
                Zone::getForeignKeyField() => $zone->getID(),
                'date' => $current_date->format('Y-m-d H:00:00'),
                'intensity' => $intensity,
            ];
            $item = $this->getItem(
                CarbonIntensity::class,
                $crit + [
                    'data_quality' => AbstractTracked::DATA_QUALITY_MANUAL
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
            $this->getItem(CarbonEmission::class, [
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
                $itemtype = \Glpi\Asset\Asset_PeripheralAsset::class;
                $input['itemtype_asset'] = Computer::class;
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
            'entities_id' => 0
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
        $method->setAccessible(true);

        return $method->invoke($instance, ...$args);
    }
}
