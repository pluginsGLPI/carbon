--
-- -------------------------------------------------------------------------
-- Carbon plugin for GLPI
--
-- @copyright Copyright (C) 2024-2025 Teclib' and contributors.
-- @copyright Copyright (C) 2024 by the carbon plugin team.
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

-- Table glpi_configs

-- INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES ("plugin:carbon", "electricitymap_api_key", "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX");
INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES ("plugin:carbon", "dbversion", "1.0.0");
INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES ("plugin:carbon", "RTE_zone_setup_complete", "0");
INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES ("plugin:carbon", "ElectricityMap_zone_setup_complete", "0");
INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES ("plugin:carbon", "demo", "0");
INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES ("plugin:carbon", "geocoding_enabled", "0");
INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES ("plugin:carbon", "boaviztapi_base_url", "");
INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES ("plugin:carbon", "impact_engine", "Boavizta");

-- Table glpi_dashboards_dashboards

INSERT INTO `glpi_dashboards_dashboards` (`id`, `key`, `name`, `context`, `users_id`) VALUES (99, "plugin_carbon_board", "Environmental impact", "mini_core", 0);

-- Table glpi_dashboards_items

INSERT INTO `glpi_dashboards_items` (`dashboards_dashboards_id`, `gridstack_id`, `card_id`, `x`, `y`, `width`, `height`, `card_options`) VALUES (99, "plugin_carbon_report_usage_carbon_emission_ytd_a0d1ed7e-ef50-4850-9aaa-a4ccc8e410ce", "plugin_carbon_report_usage_carbon_emission_ytd" ,0, 0, 5, 3, "{\"color\":\"#BBDA50\",\"widgettype\":\"usage_carbon_emission_ytd\",\"use_gradient\":\"0\",\"limit\":\"7\",\"point_labels\":\"0\"}");
INSERT INTO `glpi_dashboards_items` (`dashboards_dashboards_id`, `gridstack_id`, `card_id`, `x`, `y`, `width`, `height`, `card_options`) VALUES (99, "plugin_carbon_report_usage_carbon_emission_two_last_months_f9c2c765-a223-4630-b44f-07989da19529", "plugin_carbon_report_usage_carbon_emission_two_last_months" ,5, 0, 5, 3, "{\"color\":\"#145161\",\"widgettype\":\"total_usage_carbon_emission_two_last_months\",\"use_gradient\":\"0\",\"limit\":\"7\",\"point_labels\":\"0\"}");
INSERT INTO `glpi_dashboards_items` (`dashboards_dashboards_id`, `gridstack_id`, `card_id`, `x`, `y`, `width`, `height`, `card_options`) VALUES (99, "plugin_carbon_report_information_video_2eae617a-b1bf-4fdf-bc3a-b1f5da2d64b6", "plugin_carbon_report_information_video" ,15, 3, 5, 3, "{\"color\":\"#f3f6f4\",\"widgettype\":\"information_video\",\"use_gradient\":\"0\",\"limit\":\"7\",\"point_labels\":\"0\"}");
INSERT INTO `glpi_dashboards_items` (`dashboards_dashboards_id`, `gridstack_id`, `card_id`, `x`, `y`, `width`, `height`, `card_options`) VALUES (99, "plugin_carbon_report_methodology_information_fe1b153f-b1b0-49eb-bee7-6a71ac8a1fd2", "plugin_carbon_report_methodology_information" ,0, 12, 10, 5, "{\"color\":\"#f3f6f4\",\"widgettype\":\"methodology_information\",\"use_gradient\":\"0\",\"limit\":\"7\",\"point_labels\":\"0\"}");
INSERT INTO `glpi_dashboards_items` (`dashboards_dashboards_id`, `gridstack_id`, `card_id`, `x`, `y`, `width`, `height`, `card_options`) VALUES (99, "plugin_carbon_report_biggest_gwp_per_model_63bacb39-a2fc-4634-bc74-5bd34397967e", "plugin_carbon_report_biggest_gwp_per_model" ,0, 6, 10, 6, "{\"color\":\"#f3f6f4\",\"widgettype\":\"most_gwp_impacting_computer_models\",\"use_gradient\":\"0\",\"limit\":\"7\",\"point_labels\":\"0\"}");
INSERT INTO `glpi_dashboards_items` (`dashboards_dashboards_id`, `gridstack_id`, `card_id`, `x`, `y`, `width`, `height`, `card_options`) VALUES (99, "plugin_carbon_report_usage_carbon_emissions_graph_8b210b75-1854-47a6-9c44-129317423327", "plugin_carbon_report_usage_carbon_emissions_graph" ,10, 6, 10, 6, "{\"color\":\"#f3f6f4\",\"widgettype\":\"usage_gwp_monthly\",\"use_gradient\":\"0\",\"limit\":\"9999\",\"point_labels\":\"0\"}");
INSERT INTO `glpi_dashboards_items` (`dashboards_dashboards_id`, `gridstack_id`, `card_id`, `x`, `y`, `width`, `height`, `card_options`) VALUES (99, "plugin_carbon_report_embodied_abiotic_depletion_2b674c1a-8ee2-486a-ba24-3af873cfda5b", "plugin_carbon_report_embodied_abiotic_depletion" ,5, 3, 5, 3, "{\"color\":\"#ffd966\",\"widgettype\":\"embodied_abiotic_depletion\",\"use_gradient\":\"0\",\"point_labels\":\"0\",\"limit\":\"7\"}");
INSERT INTO `glpi_dashboards_items` (`dashboards_dashboards_id`, `gridstack_id`, `card_id`, `x`, `y`, `width`, `height`, `card_options`) VALUES (99, "plugin_carbon_report_embodied_global_warming_36d83b8d-eea9-4118-8648-5ef4c4175a0f", "plugin_carbon_report_embodied_global_warming" ,10, 0, 5, 3, "{\"color\":\"#7a941e\",\"widgettype\":\"embodied_global_warming\",\"use_gradient\":\"0\",\"point_labels\":\"0\",\"limit\":\"7\"}");
INSERT INTO `glpi_dashboards_items` (`dashboards_dashboards_id`, `gridstack_id`, `card_id`, `x`, `y`, `width`, `height`, `card_options`) VALUES (99, "plugin_carbon_report_embodied_pe_impact_33472437-e58e-4e40-b777-773dde69c8c6", "plugin_carbon_report_embodied_pe_impact" ,0, 3, 5, 3, "{\"color\":\"#326319\",\"widgettype\":\"embodied_primary_energy\",\"use_gradient\":\"0\",\"point_labels\":\"0\",\"limit\":\"7\"}");
INSERT INTO `glpi_dashboards_items` (`dashboards_dashboards_id`, `gridstack_id`, `card_id`, `x`, `y`, `width`, `height`, `card_options`) VALUES (99, "plugin_carbon_report_usage_abiotic_depletion_9ea6bc45-75e1-4641-a797-650d06175805", "plugin_carbon_report_usage_abiotic_depletion" ,10, 3, 5, 3, "{\"color\":\"#fccd3e\",\"widgettype\":\"usage_abiotic_depletion\",\"use_gradient\":\"0\",\"point_labels\":\"0\",\"limit\":\"7\"}");
INSERT INTO `glpi_dashboards_items` (`dashboards_dashboards_id`, `gridstack_id`, `card_id`, `x`, `y`, `width`, `height`, `card_options`) VALUES (99, "plugin_carbon_assets_completeness_7add0c40-0023-4d7a-8b3d-990459a2f7b2", "plugin_carbon_assets_completeness" ,15, 0, 5, 3, "{\"color\":\"#d9ead3\",\"widgettype\":\"stackedbars\",\"use_gradient\":\"0\",\"point_labels\":\"0\",\"limit\":\"7\"}");

-- Table glpi_dashboards_rights

INSERT INTO `glpi_dashboards_rights` (`dashboards_dashboards_id`, `itemtype`, `items_id`) VALUES (99, "Profile", 4);
INSERT INTO `glpi_dashboards_rights` (`dashboards_dashboards_id`, `itemtype`, `items_id`) VALUES (99, "Profile", 8);

-- Table glpi_displaypreferences

INSERT INTO `glpi_displaypreferences` (`itemtype`, `num`, `rank`, `users_id`) VALUES ("GlpiPlugin\\Carbon\\CarbonIntensity", 2, 1, 0);
INSERT INTO `glpi_displaypreferences` (`itemtype`, `num`, `rank`, `users_id`) VALUES ("GlpiPlugin\\Carbon\\CarbonIntensity", 3, 2, 0);
INSERT INTO `glpi_displaypreferences` (`itemtype`, `num`, `rank`, `users_id`) VALUES ("GlpiPlugin\\Carbon\\CarbonIntensity", 10401, 3, 0);
INSERT INTO `glpi_displaypreferences` (`itemtype`, `num`, `rank`, `users_id`) VALUES ("GlpiPlugin\\Carbon\\CarbonIntensity", 10402, 4, 0);
INSERT INTO `glpi_displaypreferences` (`itemtype`, `num`, `rank`, `users_id`) VALUES ("GlpiPlugin\\Carbon\\CarbonIntensity", 10403, 5, 0);

-- Table glpi_crontasks

INSERT INTO `glpi_crontasks` (`itemtype`, `name`, `frequency`, `param`, `state`, `mode`, `allowmode`, `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`, `date_mod`, `date_creation`) VALUES ("GlpiPlugin\\Carbon\\CronTask", "LocationCountryCode", 86400, 10, 1, 2, 3, 0, 24, 30, NULL, NULL, "Find the Alpha3 country code (ISO3166) of locations", "2025-09-11 14:53:35", "2025-09-11 14:53:35");
INSERT INTO `glpi_crontasks` (`itemtype`, `name`, `frequency`, `param`, `state`, `mode`, `allowmode`, `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`, `date_mod`, `date_creation`) VALUES ("GlpiPlugin\\Carbon\\CronTask", "UsageImpact", 86400, 10000, 1, 2, 3, 0, 24, 30, NULL, NULL, "Compute carbon emissions of computers", "2025-09-11 14:53:35", "2025-09-11 14:53:35");
INSERT INTO `glpi_crontasks` (`itemtype`, `name`, `frequency`, `param`, `state`, `mode`, `allowmode`, `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`, `date_mod`, `date_creation`) VALUES ("GlpiPlugin\\Carbon\\CronTask", "DownloadRte", 86400, 10000, 1, 2, 3, 0, 24, 30, NULL, NULL, "Collect carbon intensities from RTE", "2025-09-11 14:53:35", "2025-09-11 14:53:35");
INSERT INTO `glpi_crontasks` (`itemtype`, `name`, `frequency`, `param`, `state`, `mode`, `allowmode`, `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`, `date_mod`, `date_creation`) VALUES ("GlpiPlugin\\Carbon\\CronTask", "DownloadElectricityMap", 43200, 10000, 1, 2, 3, 0, 24, 30, NULL, NULL, "Collect carbon intensities from ElectricityMap", "2025-09-11 14:53:35", "2025-09-11 14:53:35");
INSERT INTO `glpi_crontasks` (`itemtype`, `name`, `frequency`, `param`, `state`, `mode`, `allowmode`, `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`, `date_mod`, `date_creation`) VALUES ("GlpiPlugin\\Carbon\\CronTask", "EmbodiedImpact", 86400, 10000, 1, 2, 3, 0, 24, 30, NULL, NULL, "Compute embodied impact of assets", "2025-09-11 14:53:35", "2025-09-11 14:53:35");

-- Table glpi_profilerights

INSERT INTO `glpi_profilerights` (`profiles_id`, `name`, `rights`) VALUES (4, "carbon:report", 19);

-- glpi_plugin_carbon_carbonintensitysources

INSERT INTO `glpi_plugin_carbon_carbonintensitysources` (`id`, `name`, `is_fallback`) VALUES (2, "ElectricityMap", 0);
INSERT INTO `glpi_plugin_carbon_carbonintensitysources` (`id`, `name`, `is_fallback`) VALUES (1, "RTE", 0);
INSERT INTO `glpi_plugin_carbon_carbonintensitysources` (`id`, `name`, `is_fallback`) VALUES (3, "Ember - Energy Institute", 1);
INSERT INTO `glpi_plugin_carbon_carbonintensitysources` (`id`, `name`, `is_fallback`) VALUES (4, "Hydro Quebec", 1);
INSERT INTO `glpi_plugin_carbon_carbonintensitysources` (`id`, `name`, `is_fallback`) VALUES (5, "Boaviztapi", 0);

-- glpi_plugin_carbon_computerusageprofiles

INSERT INTO `glpi_plugin_carbon_computerusageprofiles` (`id`, `name`, `time_start`, `time_stop`, `day_1`, `day_2`, `day_3`, `day_4`, `day_5`, `day_6`, `day_7`) VALUES (1, "Always on","00:00","23:59", 1, 1, 1, 1, 1, 1, 1);
INSERT INTO `glpi_plugin_carbon_computerusageprofiles` (`id`, `name`, `time_start`, `time_stop`, `day_1`, `day_2`, `day_3`, `day_4`, `day_5`, `day_6`, `day_7`) VALUES (2, "Heures de bureau","09:00:00","18:00:00", 1, 1, 1, 1, 1, 0, 0);

-- glpi_plugin_carbon_zones

INSERT INTO `glpi_plugin_carbon_zones` (`id`, `name`, `entities_id`, `plugin_carbon_carbonintensitysources_id_historical`) VALUES (1, "Quebec", 0, 4);
