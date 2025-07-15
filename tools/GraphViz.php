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

if (PHP_SAPI != 'cli') {
    echo "This script must be run from command line";
    exit();
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

    public function generate(array $schema_tables)
    {
        $db_utils = new DbUtils();

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
