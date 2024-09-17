#!/bin/php
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
 * @copyright 2015-2023 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 * @license   MIT https://opensource.org/licenses/mit-license.php
 * @link      https://github.com/pluginsGLPI/carbon
 * -------------------------------------------------------------------------
 */

if (PHP_SAPI != 'cli') {
    echo "This script must be run from command line";
    exit();
}

require_once(__DIR__ . '/GraphViz.php');
require_once(__DIR__ . '/PlantUml.php');
require_once(__DIR__ . '/Mermaid.php');
require realpath(__DIR__ . '/../../../inc/includes.php');

$CFG_GLPI['root_doc']            = '/glpi';

const CARDINALITY_ZERO_ONE = '0,1';
const CARDINALITY_ONE      = '1'  ;
const CARDINALITY_ZERO_N   = '0,n';
const CARDINALITY_ONE_N    = '1,n';

/** @var array processed tables for relations generation */
$schema_tables = findTables($argv[1] ?? '');
findRelations($schema_tables);
completeMissingData($schema_tables);
showEntityRelations($schema_tables);

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
                    ]
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
 * @return boolean
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

function showEntityRelations($schema_tables)
{
    $generator = new PlantUml();
    $generator->generate($schema_tables);
}
