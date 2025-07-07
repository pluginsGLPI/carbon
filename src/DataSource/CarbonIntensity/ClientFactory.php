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

namespace GlpiPlugin\Carbon\DataSource\CarbonIntensity;

use GlpiPlugin\Carbon\DataSource\RestApiClient;

class ClientFactory
{
    /**
     * Get available client types
     *
     * @return array
     */
    public static function getClientTypes(): array
    {
        $client_types = [];
        foreach (glob(__DIR__ . '/*Client.php') as $file) {
            $short_name = basename($file, '.php');
            $class_name = 'GlpiPlugin\\Carbon\\DataSource\\CarbonIntensity\\' . $short_name;
            if (!class_exists($class_name)) {
                continue;
            }
            if (!is_subclass_of($class_name, AbstractClient::class)) {
                continue;
            }
            $client_types[$short_name] = $class_name;
        }

        return $client_types;
    }

    /**
     * Get name of clients
     *
     * @return array
     */
    public static function getClientNames(): array
    {
        $names = [];
        $types = self::getClientTypes();
        $api_client = new RestApiClient();
        foreach ($types as $type) {
            $data_source_client = new $type($api_client);
            $names[$type] = $data_source_client->getSourceName();
        }

        return $names;
    }

    /**
     * Create an instance of a client
     *
     * @param string $type type of the client
     * @return AbstractClient instantiated client
     */
    public static function create(string $type): AbstractClient
    {
        $type .= 'Client';
        $client_classes = self::getClientTypes();
        if (!isset($client_classes[$type])) {
            throw new \InvalidArgumentException("Unknown client type: $type");
        }

        $class_name = $client_classes[$type];
        $rest_api_client = new RestApiClient([]);
        return new $class_name($rest_api_client);
    }

    /**
     * Create an instance of a client by using the name of the data source
     *
     * @param string $name
     * @return AbstractClient
     */
    public static function createByName(string $name): AbstractClient
    {
        $names = self::getClientNames();
        if (!in_array($name, $names)) {
            throw new \InvalidArgumentException("Unknown client type name: $name");
        }

        $class_name = array_search($name, $names);
        $rest_api_client = new RestApiClient([]);
        return new $class_name($rest_api_client);
    }
}
