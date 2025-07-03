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

namespace GlpiPlugin\Carbon;

class Documentation
{
    private const BASE_URL = 'https://glpi-plugins.readthedocs.io/%s/latest/carbon';
    /**
     * Get external URL to a detailed description of the given path
     *
     * @param string $object_descriptor
     * @return string
     */
    public static function getInfoLink(string $object_descriptor): string
    {
        // $lang = substr($_SESSION['glpilanguage'], 0, 2);
        $lang = 'en';
        $base_url = sprintf(
            self::BASE_URL,
            $lang
        );
        switch ($object_descriptor) {
            case 'abiotic_depletion_impact':
                return "$base_url/types_of_impact.html#antimony-equivalent";
            case 'primary_energy_impact':
                return "$base_url/carbon/types_of_impact.html#primary-energy";
            case 'carbon_emission':
                return "$base_url/carbon/types_of_impact.html#carbon-dioxyde-equivalent";
        }

        return '';
    }
}
