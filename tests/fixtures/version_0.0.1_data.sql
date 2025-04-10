--
-- -------------------------------------------------------------------------
-- carbon plugin for GLPI
-- -------------------------------------------------------------------------
--
-- MIT License
--
-- Permission is hereby granted, free of charge, to any person obtaining a copy
-- of this software and associated documentation files (the "Software"), to deal
-- in the Software without restriction, including without limitation the rights
-- to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
-- copies of the Software, and to permit persons to whom the Software is
-- furnished to do so, subject to the following conditions:
--
-- The above copyright notice and this permission notice shall be included in all
-- copies or substantial portions of the Software.
--
-- THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
-- IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
-- FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
-- AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
-- LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
-- OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
-- SOFTWARE.
-- -------------------------------------------------------------------------
-- @copyright Copyright (C) 2024 Teclib' and contributors.
-- @copyright Copyright (C) 2024 by the carbon plugin team.
-- @license   MIT https://opensource.org/licenses/mit-license.php
-- @link      https://github.com/pluginsGLPI/carbon
-- -------------------------------------------------------------------------
--

-- Table glpi_configs

-- INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES ("plugin:carbon", "electricitymap_api_key", "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX");
INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES ("plugin:carbon", "dbversion", "0.0.1");
INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES ("plugin:carbon", "RTE_zone_setup_complete", "0");
INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES ("plugin:carbon", "ElectricityMap_zone_setup_complete", "0");

-- Table glpi_displaypreferences

INSERT INTO `glpi_displaypreferences` (`itemtype`, `num`, `rank`, `users_id`) VALUES ("GlpiPlugin\\Carbon\\CarbonIntensity", 2, 1, 0);
INSERT INTO `glpi_displaypreferences` (`itemtype`, `num`, `rank`, `users_id`) VALUES ("GlpiPlugin\\Carbon\\CarbonIntensity", 3, 2, 0);
INSERT INTO `glpi_displaypreferences` (`itemtype`, `num`, `rank`, `users_id`) VALUES ("GlpiPlugin\\Carbon\\CarbonIntensity", 10401, 3, 0);
INSERT INTO `glpi_displaypreferences` (`itemtype`, `num`, `rank`, `users_id`) VALUES ("GlpiPlugin\\Carbon\\CarbonIntensity", 10402, 4, 0);
INSERT INTO `glpi_displaypreferences` (`itemtype`, `num`, `rank`, `users_id`) VALUES ("GlpiPlugin\\Carbon\\CarbonIntensity", 10403, 5, 0);

-- Table glpi_crontasks

INSERT INTO `glpi_crontasks` (`itemtype`, `name`, `frequency`, `param`, `state`, `mode`, `allowmode`, `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`, `date_mod`, `date_creation`) VALUES ("GlpiPlugin\\Carbon\\CronTask", "Historize", 86400, 10000, 1, 2, 3, 0, 24, 30, "2024-10-10 15:25:00", NULL, "Compute carbon emissions of computers", "2024-09-27 19:01:25", "2024-09-24 13:06:26");
INSERT INTO `glpi_crontasks` (`itemtype`, `name`, `frequency`, `param`, `state`, `mode`, `allowmode`, `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`, `date_mod`, `date_creation`) VALUES ("GlpiPlugin\\Carbon\\CronTask", "DownloadRte", 86400, 10000, 1, 2, 3, 0, 24, 30, "2024-10-18 08:11:00", NULL, "Collect carbon intensities from RTE", "2024-10-17 19:38:40", "2024-09-24 13:06:26");
INSERT INTO `glpi_crontasks` (`itemtype`, `name`, `frequency`, `param`, `state`, `mode`, `allowmode`, `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`, `date_mod`, `date_creation`) VALUES ("GlpiPlugin\\Carbon\\CronTask", "DownloadElectricityMap", 43200, 10000, 1, 2, 3, 0, 24, 30, "2024-09-25 14:43:00", NULL, "Collect carbon intensities from ElectricityMap", "2024-09-24 13:06:26", "2024-09-24 13:06:26");

-- Table glpi_profilerights

INSERT INTO `glpi_profilerights` (`profiles_id`, `name`, `rights`) VALUES (4, "carbon:report", 19);

-- glpi_plugin_carbon_carbonintensitysources

INSERT INTO `glpi_plugin_carbon_carbonintensitysources` (`id`, `name`) VALUES (2, "ElectricityMap");
INSERT INTO `glpi_plugin_carbon_carbonintensitysources` (`id`, `name`) VALUES (1, "RTE");

-- glpi_plugin_carbon_computerusageprofiles

INSERT INTO `glpi_plugin_carbon_computerusageprofiles` (`id`, `name`, `time_start`, `time_stop`, `day_1`, `day_2`, `day_3`, `day_4`, `day_5`, `day_6`, `day_7`) VALUES (1, "Always on","00:00","23:59", 1, 1, 1, 1, 1, 1, 1);
INSERT INTO `glpi_plugin_carbon_computerusageprofiles` (`id`, `name`, `time_start`, `time_stop`, `day_1`, `day_2`, `day_3`, `day_4`, `day_5`, `day_6`, `day_7`) VALUES (2, "Heures de bureau","09:00:00","18:00:00", 1, 1, 1, 1, 1, 0, 0);