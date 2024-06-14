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


class PlantUml
{
    const CARDINALITY = [
        '0,1' => ['|o', 'o|'],
        '1'   => ['||', '||'],
        '0,n' => ['}o', 'o{'],
        '1,n' => ['|o', 'o|'],
    ];

    public function generate(array $schema_tables)
    {
        echo "@startuml" . PHP_EOL;
        echo "' avoid problems with angled crows feet";
        echo "skinparam linetype ortho";

        foreach ($schema_tables as $table_data) {
            echo PHP_EOL;
            $table_name = $table_data['name'];
            // $itemtype = $db_utils->getItemTypeForTable($table_name);
            // $itemtype_name = $itemtype::getTypeName(1);
            echo "entity \"$table_name\" as $table_name {" . PHP_EOL;
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
