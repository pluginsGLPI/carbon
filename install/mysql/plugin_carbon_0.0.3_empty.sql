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

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_carbonemissions` (
  `id`               int unsigned NOT NULL AUTO_INCREMENT,
  `itemtype`         varchar(255) DEFAULT NULL,
  `items_id`         int unsigned NOT NULL DEFAULT '0',
  `entities_id`      int unsigned NOT NULL DEFAULT '0',
  `types_id`         int unsigned NOT NULL DEFAULT '0',
  `models_id`        int unsigned NOT NULL DEFAULT '0',
  `locations_id`     int unsigned NOT NULL DEFAULT '0',
  `energy_per_day`   float        DEFAULT '0'         COMMENT 'KWh',
  `emission_per_day` float        DEFAULT '0'         COMMENT 'gCO2eq',
  `date`             timestamp    NULL DEFAULT NULL,
  `energy_quality`   int unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `emission_quality` int unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`itemtype`, `items_id`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_carbonintensities` (
  `id`                                      int unsigned NOT NULL AUTO_INCREMENT,
  `date`                                    timestamp    NULL DEFAULT NULL,
  `plugin_carbon_carbonintensitysources_id` int unsigned NOT NULL DEFAULT '0',
  `plugin_carbon_carbonintensityzones_id`   int unsigned NOT NULL DEFAULT '0',
  `intensity`                               float        DEFAULT '0'   COMMENT 'gCO2eq/KWh',
  `data_quality`                            int unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`date`, `plugin_carbon_carbonintensitysources_id`, `plugin_carbon_carbonintensityzones_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_carbonintensityzones` (
  `id`                                                 int unsigned NOT NULL AUTO_INCREMENT,
  `name`                                               varchar(255) DEFAULT NULL,
  `plugin_carbon_carbonintensitysources_id_historical` int unsigned NOT NULL DEFAULT '0' COMMENT 'Source to be used for historical calculation',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_carbonintensitysources` (
  `id`               int unsigned NOT NULL AUTO_INCREMENT,
  `name`             varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_carbonintensitysources_carbonintensityzones` (
  `id`               int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_carbon_carbonintensitysources_id` int unsigned NOT NULL DEFAULT '0',
  `plugin_carbon_carbonintensityzones_id`   int unsigned NOT NULL DEFAULT '0',
  `code`                                    varchar(255) DEFAULT NULL         COMMENT 'Zone identifier in the API of the source',
  `is_download_enabled`                     tinyint(1)   NOT NULL DEFAULT '0' COMMENT 'Download enabled from the source for this zone',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_carbon_carbonintensitysources_id`, `plugin_carbon_carbonintensityzones_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_computertypes` (
  `id`                int unsigned NOT NULL AUTO_INCREMENT,
  `computertypes_id`  int unsigned NOT NULL DEFAULT '0',
  `power_consumption` int          DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`computertypes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_computerusageprofiles` (
  `id`           int unsigned NOT NULL AUTO_INCREMENT,
  `name`         varchar(255) DEFAULT NULL,
  `time_start`   varchar(255) DEFAULT NULL,
  `time_stop`    varchar(255) DEFAULT NULL,
  `day_1`        tinyint(1)   NOT NULL DEFAULT '0',
  `day_2`        tinyint(1)   NOT NULL DEFAULT '0',
  `day_3`        tinyint(1)   NOT NULL DEFAULT '0',
  `day_4`        tinyint(1)   NOT NULL DEFAULT '0',
  `day_5`        tinyint(1)   NOT NULL DEFAULT '0',
  `day_6`        tinyint(1)   NOT NULL DEFAULT '0',
  `day_7`        tinyint(1)   NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_environnementalimpacts` (
  `id`                                     int unsigned NOT NULL AUTO_INCREMENT,
  `computers_id`                           int unsigned NOT NULL DEFAULT '0',
  `plugin_carbon_computerusageprofiles_id` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_monitortypes` (
  `id`                int unsigned NOT NULL AUTO_INCREMENT,
  `monitortypes_id`   int unsigned NOT NULL DEFAULT '0',
  `power_consumption` int          DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`monitortypes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_networkequipmenttypes` (
  `id`                       int unsigned NOT NULL AUTO_INCREMENT,
  `networkequipmenttypes_id` int unsigned NOT NULL DEFAULT '0',
  `power_consumption`        int          DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`networkequipmenttypes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
