#!/bin/php
<?php

/**
 * -------------------------------------------------------------------------
 * Carbon plugin for GLPI
 *
 * @copyright Copyright (C) 2024-2025 Teclib' and contributors.
 * @copyright 2015-2023 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
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

use Glpi\Application\Environment;
use Glpi\Application\ResourcesChecker;
use Glpi\Kernel\Kernel;

if (PHP_SAPI != 'cli') {
    echo "This script must be run from command line";
    exit();
}

// Check the resources state before trying to be sure that the tests are executed with up-to-date dependencies.
require_once dirname(__DIR__, 3) . '/src/Glpi/Application/ResourcesChecker.php';
(new ResourcesChecker(dirname(__DIR__, 3)))->checkResources();

global $GLPI_CACHE;

require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

$kernel = new Kernel(Environment::TESTING->value);
$kernel->boot();

if (!file_exists(GLPI_CONFIG_DIR . '/config_db.php')) {
    echo("\nConfiguration file for tests not found\n\nrun: php bin/console database:install --env=testing ...\n\n");
    exit(1);
}
if (Update::isUpdateMandatory()) {
    echo 'The GLPI codebase has been updated. The update of the GLPI database is necessary.' . PHP_EOL;
    exit(1);
}

require_once(__DIR__ . '/GraphViz.php');
require_once(__DIR__ . '/PlantUml.php');
require_once(__DIR__ . '/Mermaid.php');

$CFG_GLPI['root_doc']            = '/glpi';

const CARDINALITY_ZERO_ONE = '0,1';
const CARDINALITY_ONE      = '1'  ;
const CARDINALITY_ZERO_N   = '0,n';
const CARDINALITY_ONE_N    = '1,n';

/** @var array processed tables for relations generation */
$schema_tables = findTables($argv[1] ?? '');
findRelations($schema_tables);
completeMissingData($schema_tables);
echo showEntityRelations($schema_tables);

/**
 * Get all tables to analyze
 *
 * @param string $plugin
 * @return array
 */
function findTables(string $plugin = ''): array
{
    global $DB;

    if ($plugin == '') {
        $tables_restriction = [
            'table_name' => [
                'NOT LIKE', 'glpi_plugin_%',
            ],
        ];
    } else {
        $tables_restriction = [
            'table_name' => [
                'LIKE', 'glpi_plugin_' . $plugin . '_%',
            ],
        ];
    }

    $tables = [];
    $rows = $DB->listTables('glpi\_%', [$tables_restriction]);
    foreach ($rows as $row) {
        $tables[] = [
            'name'   => $row['TABLE_NAME'],
            'links'  => [],
            'fields' => [],
        ];
    }
    return $tables;
}

function findRelations(&$schema_tables)
{
    global $DB;

    foreach ($schema_tables as &$table_data) {
        $table_name = $table_data['name'];
        $fields = $DB->listFields($table_name, true);
        foreach ($fields as $field_row) {
            $field_name = $field_row['Field'];
            $field_type = convertType($field_row['Type']);
            $table_data['fields'][] = [$field_type, $field_name];
            $relation_type = getRelationType($schema_tables, $table_name, $field_name);
            if (is_array($relation_type)) {
                $table_data['links'][] = $relation_type;
            }
        }
    }
}

function completeMissingData(&$schema_tables)
{
    global $DB;

    foreach ($schema_tables as &$table_data) {
        foreach ($table_data['links'] as $linkId => $link) {
            if (hasTable($schema_tables, $link['table'])) {
                continue;
            }
            if (!$DB->tableExists($link['table'])) {
                // Do not add a table which does not exists in the database
                unset($table_data['links'][$linkId]);
                continue;
            }
            $schema_tables[] = [
                'name'   => $link['table'],
                'links'  => [],
                'fields' => [
                    [
                        'int',
                        'id',
                    ],
                ],
            ];
        }
    }
}

function hasTable($schema_tables, $search): bool
{
    foreach ($schema_tables as $table_data) {
        if ($table_data['name'] == $search) {
            return true;
        }
    }

    return false;
}

function convertType($type): string
{
    $type = strtolower($type);
    $type = explode(' ', $type)[0];
    if (strpos($type, '(') !== false) {
        $type = substr($type, 0, strpos($type, '('));
    }

    switch ($type) {
        case 'tinyint':
            return 'bool';

        case 'int':
        case 'bigint':
        case 'mediumint':
        case 'smallint':
            return 'int';

        case 'decimal':
        case 'float':
            return 'float';

        case 'varchar':
        case 'char':
        case 'text':
        case 'mediumtext':
        case 'longtext':
            return 'string';

        case 'date':
            return 'date';

        case 'datetime':
        case 'timestamp':
            return 'datetime';

        case 'time':
            return 'time';
    }

    return 'unknown';
}

function getRelationType(&$schema_tables, $table, $field_name): ?array
{
    $db_utils = new DbUtils();

    $itemtype = $db_utils->getItemTypeForTable($table);
    if (($foreign_table = $db_utils->getTableNameForForeignKeyField($field_name)) !== '') {
        if (is_subclass_of($itemtype, CommonDBChild::class)) {
            if ($itemtype::$itemtype == 'itemtype' && $field_name == 'items_id') {
                return null; // don't know how to handle this yet
            }
            if (preg_match('/^itemtype_/', $itemtype::$itemtype)) {
                $suffix = substr($field_name, strlen('itemtype_'));
                if ($field_name == 'items_id_' . $suffix) {
                    return null; // don't know how to handle this yet
                }
                return null;  // don't know how to handle this yet (but shall be handled)
            }
            return [
                'local_field' => $field_name,
                'local'       => CARDINALITY_ZERO_ONE,
                'foreign'     => CARDINALITY_ZERO_N,
                'table'       => $foreign_table,
                'label'       => 'contains',
            ];
        }
        if (is_subclass_of($itemtype, CommonDBRelation::class)) {
            $itemtype_1 = $itemtype::$itemtype_1;
            $itemtype_2 = $itemtype::$itemtype_2;
            if ($itemtype_1 == 'itemtype' && $field_name == 'items_id') {
                return null; // don't know how to handle this yet
            }
            if ($itemtype_2 == 'itemtype'  && $field_name == 'items_id') {
                return null; // don't know how to handle this yet
            }
            if (preg_match('/^itemtype_/', $itemtype_1)) {
                $suffix = substr($field_name, strlen('itemtype_'));
                if ($field_name == 'items_id_' . $suffix) {
                    return null; // don't know how to handle this yet
                }
                return null;  // don't know how to handle this yet (but shall be handled)
            }
            if (preg_match('/^itemtype_/', $itemtype_2)) {
                $suffix = substr($field_name, strlen('itemtype_'));
                if ($field_name == 'items_id_' . $suffix) {
                    return null; // don't know how to handle this yet
                }
                return null;  // don't know how to handle this yet (but shall be handled)
            }
            if (isRelationTableWithProperties($table)) {
                // an ID, two foreign keys, and some properties,
                // then this is not a simple relation
                return [
                    'local_field' => $field_name,
                    'local'       => CARDINALITY_ZERO_ONE,
                    'foreign'     => CARDINALITY_ZERO_N,
                    'table'       => $foreign_table,
                    'label'       => 'contains',
                ];
            }
        }

        $foreign_itemtype = $db_utils->getItemTypeForTable($foreign_table);
        if ($foreign_itemtype === 'UNKNOWN') {
            return null;
        }
        return [
            'local_field' => $field_name,
            'local'       => CARDINALITY_ZERO_ONE,
            'foreign'     => CARDINALITY_ZERO_N,
            'table'       => $foreign_table,
            'label'       => 'relation',
        ];
    }

    return null;
}

/**
 * is the table a relation table with additional properties ?
 *
 * @param string $table
 * @return bool
 */
function isRelationTableWithProperties($table): bool
{
    global $DB;

    $itemtype = getItemTypeForTable($table);
    if (!($itemtype instanceof CommonDBRelation)) {
        return false;
    }

    $minimal_fields_count = 3;

    $itemtype_1 = $itemtype::$itemtype_1;
    $itemtype_2 = $itemtype::$itemtype_2;
    if ($itemtype_1 == 'itemtype') {
        $minimal_fields_count++;
    }
    if ($itemtype_2 == 'itemtype') {
        $minimal_fields_count++;
    }

    $fields = $DB->listFields($table, true);
    if (count($fields) > $minimal_fields_count) {
        return true;
    }

    return false;
}

function showEntityRelations($schema_tables): string
{
    $generator = new PlantUml();
    $pluginVersion = getPluginVersion();
    return $generator->generate($schema_tables, $pluginVersion);
}

function getPluginVersion(): string
{
    $setupFile = dirname(__DIR__) . '/setup.php';
    if (!file_exists($setupFile)) {
        return '';
    }

    require_once $setupFile;

    $pluginName = basename(dirname(__DIR__));
    $versionFunction = 'plugin_version_' . $pluginName;
    if (!is_callable($versionFunction)) {
        return '';
    }

    $pluginInfo = $versionFunction();
    if (!is_array($pluginInfo) || empty($pluginInfo['version'])) {
        return '';
    }

    return (string) $pluginInfo['version'];
}
