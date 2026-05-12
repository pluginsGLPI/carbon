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

class Mermaid
{
    public const CARDINALITY = [
        '0,1' => ['|o', 'o|'],
        '1'   => ['||', '||'],
        '0,n' => ['}o', 'o{'],
        '1,n' => ['|o', 'o|'],
    ];

    public function generate(array $schema_tables): string
    {
        $source = $this->getMermaidSource($schema_tables);
        return $source;
    }

    protected function getMermaidSource(array $schema_tables): string
    {
        $source = '';
        $source .= "---" . PHP_EOL;
        $source .= "title: GLPI Database Schema" . PHP_EOL;
        $source .= "---" . PHP_EOL;
        $source .= "erDiagram" . PHP_EOL;

        foreach ($schema_tables as $table_data) {
            echo PHP_EOL;
            $table_name = $table_data['name'];
            // $itemtype = $db_utils->getItemTypeForTable($table_name);
            // $itemtype_name = $itemtype::getTypeName(1);
            $source .= "    $table_name {" . PHP_EOL;
            foreach ($table_data['fields'] as $field) {
                $field_type = $field[0];
                $field_name = $field[1];
                $source .= "        $field_type $field_name" . PHP_EOL;
            }
            $source .= "    }" . PHP_EOL;
            if (count($table_data['links']) === 0) {
                continue;
            }
            $source .= PHP_EOL;
            foreach ($table_data['links'] as $link) {
                $local_cardinality = self::CARDINALITY[$link['local']][0];
                $foreign_cardinality = self::CARDINALITY[$link['foreign']][1];
                $foreign_table = $link['table'];
                $relation_label = $link['label'];
                $source .= "    $table_name $local_cardinality--$foreign_cardinality $foreign_table : $relation_label" . PHP_EOL;
            }
        }
        return $source;
    }
}
