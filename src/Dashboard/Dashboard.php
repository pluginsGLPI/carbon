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

namespace GlpiPlugin\Carbon\Dashboard;

use ComputerModel;
use GlpiPlugin\Carbon\Toolbox;

class Dashboard
{
    /**
     * Returns total carbon emission per computer type.
     *
     * @return array of:
     *  - float  'number': total carbon emission of the type
     *  - string 'url': url to redirect when clicking on the slice
     *  - string 'label': name of the computer type
     */
    public static function getTotalCarbonEmissionPerType()
    {
        return Provider::getSumEmissionsPerType();
    }

    /**
     * Returns total power per computer model.
     *
     * @return array of:
     *   - int  'number': total power of the model
     *   - string 'url': url to redirect when clicking on the slice
     *   - string 'label': name of the computer model
     */
    public static function getTotalPowerPerModel(): array
    {
        return Provider::getSumPowerPerModel([ComputerModel::getTableField('power_consumption') => ['>', '0']]);
    }

    public static function cardCarbonintensityProvider(array $params = [])
    {
        $default_params = [
            'label' => __('Carbon dioxyde intensity', 'carbon'),
            'icon'  => "fas fa-computer",
            'color' => '#ea9999',
        ];
        $params = array_merge($default_params, $params);

        $data = Provider::getCarbonIntensity($params);

        return [
            'data'  => $data,
            'label' => $params['label'],
            'icon'  => $params['icon'],
        ];
    }
}
