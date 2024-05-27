#!/bin/php
<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
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
 * ---------------------------------------------------------------------
 */

if (PHP_SAPI != 'cli') {
    echo "This script must be run from command line";
    exit();
}

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
function findTables(string $plugin = ''): array {
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

function findRelations(&$schema_tables) {
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

function completeMissingData(&$schema_tables) {
    foreach ($schema_tables as $table_data) {
        foreach ($table_data['links'] as $link) {
            if (hasTable($schema_tables, $link['table'])) {
                continue;
            }
            $schema_tables[] = [
                'name'   => $link['table'],
                'links'  => [],
                'fields' => [[
                    'int',
                    'id',
                ]],
            ];
        }
    }
}

function hasTable($schema_tables, $search): bool {
    foreach ($schema_tables as $table_data) {
        if ($table_data['name'] == $search) {
            return true;
        }
    }

    return false;
}

function convertType($type): string {
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

function getRelationType(&$schema_tables, $table, $field_name): ?array {
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
function isRelationTableWithProperties($table): bool {
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

function showEntityRelations($schema_tables) {

    $generator = new PlantUml();
    $generator->generate($schema_tables);
}

class Mermaid
{
    const CARDINALITY = [
        '0,1' => ['|o', 'o|'],
        '1'   => ['||', '||'],
        '0,n' => ['}o', 'o{'],
        '1,n' => ['|o', 'o|'],
    ];

    public function generate(array $schema_tables) {
        echo "---" . PHP_EOL;
        echo "title: GLPI Database Schema" . PHP_EOL;
        echo "---" . PHP_EOL;
        echo "erDiagram" . PHP_EOL;

        foreach ($schema_tables as $table_data) {
            echo PHP_EOL;
            $table_name = $table_data['name'];
            // $itemtype = $db_utils->getItemTypeForTable($table_name);
            // $itemtype_name = $itemtype::getTypeName(1);
            echo "    $table_name {".PHP_EOL;
            foreach ($table_data['fields'] as $field) {
                $field_type = $field[0];
                $field_name = $field[1];
                echo "        $field_type $field_name" . PHP_EOL;
            }
            echo "    }" . PHP_EOL;
            if (count($table_data['links']) === 0) {
                continue;
            }
            echo PHP_EOL;
            foreach ($table_data['links'] as $link) {
                $local_cardinality = self::CARDINALITY[$link['local']][0];
                $foreign_cardinality = self::CARDINALITY[$link['foreign']][1];
                $foreign_table = $link['table'];
                $relation_label = $link['label'];
                echo "    $table_name $local_cardinality--$foreign_cardinality $foreign_table : $relation_label" . PHP_EOL;
            }
        }
    }
}

class GraphViz
{
    const CARDINALITY = [
        '0,1' => 'otee',
        '1'   => 'teetee',
        '0,n' => 'ocrow',
        '1,n' => 'teecrow',
    ];

    const NAME_BACK_COLOR = '#ECECFF';
    const COLOR = 'black';
    const ODD_FIELD_BACK_COLOR = '#E3E3E3';
    const EVEN_FIELD_BACK_COLOR = 'white';

    public function generate(array $schema_tables) {
        $db_utils = new DbUtils;

        echo "digraph G {" . PHP_EOL;
        echo "    node [shape=plaintext]" . PHP_EOL;

        foreach ($schema_tables as $table_data) {
            echo PHP_EOL;
            $table_name = $table_data['name'];
            echo "    $table_name [label=<" . PHP_EOL;
            echo '        <table border="1" cellborder="1" cellspacing="0">' . PHP_EOL;
            echo '            <tr><td bgcolor="' . self::NAME_BACK_COLOR . '" colspan="2">' . $table_name . '</td></tr>' . PHP_EOL;
            $even = true;
            foreach ($table_data['fields'] as $field) {
                $even = !$even;
                $bgcolor = $even ? self::EVEN_FIELD_BACK_COLOR : self::ODD_FIELD_BACK_COLOR;
                $field_type = $field[0];
                $field_name = $field[1];
                $port = '';
                if ($field_name == 'id') {
                    $port = 'port="id"';
                } else if (($foreign_table = $db_utils->getTableNameForForeignKeyField($field_name)) !== '') {
                    $port = 'port="' . $field_name . '"';
                }
                echo "            <tr>" . PHP_EOL;
                echo "                <td bgcolor=\"$bgcolor\" $port>" . PHP_EOL;
                echo "                    $field_type" . PHP_EOL;
                echo "                </td>" . PHP_EOL;
                echo "                <td bgcolor=\"$bgcolor\">" . PHP_EOL;
                echo "                    $field_name" . PHP_EOL;
                echo "                </td>" . PHP_EOL;
                echo "            </tr>" . PHP_EOL;
            }
            echo "     </table>>];" . PHP_EOL;

            echo PHP_EOL;
            foreach ($table_data['links'] as $link) {
                $local_cardinality = self::CARDINALITY[$link['local']];
                $foreign_table = $link['table'];
                $foreign_key = $db_utils->getForeignKeyFieldForTable($foreign_table);
                $local_field = $link['local_field'] ?? $foreign_key;
                $foreign_cardinality = self::CARDINALITY[$link['foreign']];
                $relation_label = $link['label'];
                $arrow = [
                    "$table_name:$local_field",
                    "->",
                    "$foreign_table:id",
                    "[arrowhead=$local_cardinality, arrowtail=$foreign_cardinality, dir=both]",
                ];
                // echo "    $table_name:$foreign_key -> $foreign_table:id [arrowhead=vee, arrowtail=dot, dir=both];" . PHP_EOL;
                echo implode(' ', $arrow) . PHP_EOL;
            }

        }

        echo "}";
    }
}

class PlantUml
{
    const CARDINALITY = [
        '0,1' => ['|o', 'o|'],
        '1'   => ['||', '||'],
        '0,n' => ['}o', 'o{'],
        '1,n' => ['|o', 'o|'],
    ];

    public function generate(array $schema_tables) {
        echo "@startuml" . PHP_EOL;
        echo "' avoid problems with angled crows feet";
        echo "skinparam linetype ortho";

        foreach ($schema_tables as $table_data) {
            echo PHP_EOL;
            $table_name = $table_data['name'];
            // $itemtype = $db_utils->getItemTypeForTable($table_name);
            // $itemtype_name = $itemtype::getTypeName(1);
            echo "entity \"$table_name\" as $table_name {".PHP_EOL;
            foreach ($table_data['fields'] as $field) {
                $field_type = $field[0];
                $field_name = $field[1];
                echo "    $field_name : $field_type" . PHP_EOL;
            }
            echo "}" . PHP_EOL;
            if (count($table_data['links']) === 0) {
                continue;
            }
            echo PHP_EOL;
        }

        foreach ($schema_tables as $table_data) {
            $table_name = $table_data['name'];
            foreach ($table_data['links'] as $link) {
                $local_cardinality = self::CARDINALITY[$link['local']][0];
                $foreign_cardinality = self::CARDINALITY[$link['foreign']][1];
                $foreign_table = $link['table'];
                $relation_label = $link['label'];
                echo "$table_name $local_cardinality--$foreign_cardinality $foreign_table" . PHP_EOL;
            }
        }

        echo "@enduml" . PHP_EOL;
    }
}