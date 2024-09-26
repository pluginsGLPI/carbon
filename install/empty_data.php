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
 * @license   MIT https://opensource.org/licenses/mit-license.php
 * @link      https://github.com/pluginsGLPI/carbon
 * -------------------------------------------------------------------------
 */

use CronTask as GlpiCronTask;

$empty_data_builder = new class
{
    public function getEmptyData(): array
    {
        $tables = [];

        $tables['glpi_plugin_carbon_computerusageprofiles'] = [
            [
                'name'       => __('Always on', 'carbon'),
                'time_start' => '00:00:00',
                'time_stop'  => '23:59:00',
                'day_1'      => '1',
                'day_2'      => '1',
                'day_3'      => '1',
                'day_4'      => '1',
                'day_5'      => '1',
                'day_6'      => '1',
                'day_7'      => '1',
            ], [
                'name'       => __('Office hours', 'carbon'),
                'time_start' => '09:00:00',
                'time_stop'  => '18:00:00',
                'day_1'      => '1',
                'day_2'      => '1',
                'day_3'      => '1',
                'day_4'      => '1',
                'day_5'      => '1',
                'day_6'      => '0',
                'day_7'      => '0',
            ],
        ];

        $tables['glpi_plugin_carbon_carbonintensitysources'] = [
            [
                'name' => 'RTE'
            ], [
                'name' => 'ElectricityMap'
            ]
        ];

        return $tables;
    }
};

return $empty_data_builder->getEmptyData();
