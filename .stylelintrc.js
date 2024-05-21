/**
 * -------------------------------------------------------------------------
 * localeoverride plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of localeoverride.
 *
 * localeoverride is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * localeoverride is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with localeoverride. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2020-2022 by Teclib'.
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://services.glpi-network.com
 * -------------------------------------------------------------------------
 */

module.exports = {
    "extends": "stylelint-config-standard-scss",
    "ignoreFiles": [
        "node_modules/**/*",
        "vendor/**/*",
        "dist/**/*"
    ],
    "rules": {
        "selector-class-pattern": null, // DISABLE: Expected class selector to be kebab-case
        "font-family-no-missing-generic-family-keyword": [
            true,
            {
                "ignoreFontFamilies": [
                    "Font Awesome 6 Free",
                ],
            }
        ],
    },
};
