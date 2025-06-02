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
