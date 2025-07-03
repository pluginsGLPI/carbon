--
-- -------------------------------------------------------------------------
-- Carbon plugin for GLPI
--
-- @copyright Copyright (C) 2024-2025 Teclib' and contributors.
-- @license   https://www.gnu.org/licenses/gpl-3.0.txt GPLv3+
-- @license   MIT https://opensource.org/licenses/mit-license.php
-- @link      https://github.com/pluginsGLPI/carbon
--
-- -------------------------------------------------------------------------
--
-- LICENSE
--
-- This file is part of Carbon plugin for GLPI.
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <https://www.gnu.org/licenses/>.
--
-- -------------------------------------------------------------------------
--

INSERT INTO `glpi_plugin_carbon_carbonintensitysources` (`id`, `name`, `is_fallback`) VALUES
    (1, 'Ember - Energy Institute', 1),
    (2, 'RTE', 0),
    (3, 'ElectricityMap', 0),
    (4, 'Hydro Quebec', 1);

INSERT INTO `glpi_plugin_carbon_zones` (`id`, `name`, `plugin_carbon_carbonintensitysources_id_historical`) VALUES
    (1, 'World', 1),
    (2, 'France', 2),
    (3, 'Quebec', 4);
