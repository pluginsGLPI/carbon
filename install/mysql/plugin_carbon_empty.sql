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
-- @license   MIT https://opensource.org/licenses/mit-license.php
-- @link      https://github.com/pluginsGLPI/carbon
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
  `energy_per_day`   float        DEFAULT '0',
  `emission_per_day` float        DEFAULT '0',
  `date`             timestamp    NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_carbonintensities` (
  `id`                                      int unsigned NOT NULL AUTO_INCREMENT,
  `emission_date`                           timestamp    NULL DEFAULT NULL,
  `plugin_carbon_carbonintensitysources_id` int unsigned NOT NULL DEFAULT '0',
  `plugin_carbon_carbonintensityzones_id`   int unsigned NOT NULL DEFAULT '0',
  `intensity`                               float        DEFAULT '0'   COMMENT 'CO2eq/KWh',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`emission_date`, `plugin_carbon_carbonintensitysources_id`, `plugin_carbon_carbonintensityzones_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_carbonintensityzones` (
  `id`               int unsigned NOT NULL AUTO_INCREMENT,
  `name`             varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_carbonintensitysources` (
  `id`               int unsigned NOT NULL AUTO_INCREMENT,
  `name`             varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
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
  `average_load` int          NOT NULL DEFAULT '0',
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
