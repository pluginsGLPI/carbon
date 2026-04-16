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
  `engine`           varchar(255) DEFAULT NULL,
  `engine_version`   varchar(255) DEFAULT NULL,
  `date_mod`         timestamp    NULL DEFAULT NULL,
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
  `id`                       int unsigned NOT NULL AUTO_INCREMENT,
  `date`                     timestamp    NULL DEFAULT NULL,
  `plugin_carbon_sources_id` int unsigned NOT NULL DEFAULT '0',
  `plugin_carbon_zones_id`   int unsigned NOT NULL DEFAULT '0',
  `intensity`                float        DEFAULT '0'   COMMENT 'gCO2eq/KWh',
  `data_quality`             int unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`date`, `plugin_carbon_sources_id`, `plugin_carbon_zones_id`),
  INDEX `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_zones` (
  `id`                                   int unsigned NOT NULL AUTO_INCREMENT,
  `name`                                 varchar(255) DEFAULT NULL,
  `entities_id`                          int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`name`),
  INDEX `entities_id` (`entities_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_sources` (
  `id`                         int unsigned NOT NULL AUTO_INCREMENT,
  `name`                       varchar(255) DEFAULT NULL,
  `fallback_level`             int          NOT NULL DEFAULT '0' COMMENT 'Fallback source for carbon intensity',
  `is_carbon_intensity_source` tinyint      NOT NULL DEFAULT '0' COMMENT 'provides carbon intensity',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_sources_zones` (
  `id`               int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_carbon_sources_id` int unsigned NOT NULL DEFAULT '0',
  `plugin_carbon_zones_id`   int unsigned NOT NULL DEFAULT '0',
  `code`                     varchar(255) DEFAULT NULL         COMMENT 'Zone identifier in the API of the source',
  `is_download_enabled`      tinyint      NOT NULL DEFAULT '0' COMMENT 'Download enabled from the source for this zone',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_carbon_sources_id`, `plugin_carbon_zones_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_computermodels` (
  `id`                int unsigned NOT NULL AUTO_INCREMENT,
  `computermodels_id` int unsigned NOT NULL DEFAULT '0',
  `gwp`               int          DEFAULT '0'  COMMENT '(unit gCO2eq) Global warming potential',
  `gwp_source`        mediumtext   DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `gwp_quality`       int          DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `adp`               int          DEFAULT '0'  COMMENT '(unit g Sb eq) Abiotic depletion potential',
  `adp_source`        mediumtext   DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `adp_quality`       int          DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `pe`                int          DEFAULT '0'  COMMENT '(unit J) Primary energy consumption',
  `pe_source`         mediumtext   DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `pe_quality`        int          DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `gwppb`             float        DEFAULT '0'  COMMENT '(unit g CO2 eq) Biogenic climate change potential',
  `gwppb_source`      mediumtext   DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `gwppb_quality`     int          DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `gwppf`             float        DEFAULT '0'  COMMENT '(unit g CO2 eq) Fossil climate change potential',
  `gwppf_source`      mediumtext   DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `gwppf_quality`     int          DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `gwpplu`            float        DEFAULT '0'  COMMENT '(unit g CO2 eq) Land use change climate potential',
  `gwpplu_source`     mediumtext   DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `gwpplu_quality`    int          DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `ir`                float        DEFAULT '0'  COMMENT '(unit g U235 eq) Ionizing radiation potential',
  `ir_source`         mediumtext   DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `ir_quality`        int          DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `lu`                float        DEFAULT '0'  COMMENT '(unit m²a) Land use',
  `lu_source`         mediumtext   DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `lu_quality`        int          DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `odp`               float        DEFAULT '0'  COMMENT '(unit g CFC-11 eq) Ozone depletion potential',
  `odp_source`        mediumtext   DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `odp_quality`       int          DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `pm`                float        DEFAULT '0'  COMMENT '(unit cases) Fine particulate matter potential',
  `pm_source`         mediumtext   DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `pm_quality`        int          DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `pocp`              float        DEFAULT '0'  COMMENT '(unit g NMVOC eq) Photochemical ozone creation potential',
  `pocp_source`       mediumtext   DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `pocp_quality`      int          DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `wu`                float        DEFAULT '0'  COMMENT '(unit m³) Water use',
  `wu_source`         mediumtext   DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `wu_quality`        int          DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `mips`              float        DEFAULT '0'  COMMENT '(unit g) Material input per service unit',
  `mips_source`       mediumtext   DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `mips_quality`      int          DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `adpe`              float        DEFAULT '0'  COMMENT '(unit g Sb eq) Abiotic depletion potential (elements)',
  `adpe_source`       mediumtext   DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `adpe_quality`      int          DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `adpf`              float        DEFAULT '0'  COMMENT '(unit J) Abiotic depletion potential (fossil fuels)',
  `adpf_source`       mediumtext   DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `adpf_quality`      int          DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `ap`                float        DEFAULT '0'  COMMENT '(unit mol H+ eq) Acidification potential',
  `ap_source`         mediumtext   DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `ap_quality`        int          DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `ctue`              float        DEFAULT '0'  COMMENT '(unit CTUe) Freshwater ecotoxicity potential',
  `ctue_source`       mediumtext   DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `ctue_quality`      int          DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `epf`               float        DEFAULT '0'  COMMENT '(unit g P eq) Freshwater eutrophication potential',
  `epf_source`        mediumtext   DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `epf_quality`       int          DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `epm`               float        DEFAULT '0'  COMMENT '(unit g N eq) Marine eutrophication potential',
  `epm_source`        mediumtext   DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `epm_quality`       int          DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `ept`               float        DEFAULT '0'  COMMENT '(unit mol N eq) Terrestrial eutrophication potential',
  `ept_source`        mediumtext   DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `ept_quality`       int          DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`computermodels_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_computertypes` (
  `id`                int unsigned NOT NULL AUTO_INCREMENT,
  `computertypes_id`  int unsigned NOT NULL DEFAULT '0',
  `power_consumption` int          DEFAULT '0',
  `category`          int          NOT NULL DEFAULT '0' COMMENT 'ComputerType::CATEGORY_* constants',
  `is_ignore`         tinyint      NOT NULL DEFAULT '0' COMMENT 'Ignored from calculations',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`computertypes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_computerusageprofiles` (
  `id`           int unsigned NOT NULL AUTO_INCREMENT,
  `name`         varchar(255) DEFAULT NULL,
  `time_start`   varchar(255) DEFAULT NULL,
  `time_stop`    varchar(255) DEFAULT NULL,
  `day_1`        tinyint      NOT NULL DEFAULT '0',
  `day_2`        tinyint      NOT NULL DEFAULT '0',
  `day_3`        tinyint      NOT NULL DEFAULT '0',
  `day_4`        tinyint      NOT NULL DEFAULT '0',
  `day_5`        tinyint      NOT NULL DEFAULT '0',
  `day_6`        tinyint      NOT NULL DEFAULT '0',
  `day_7`        tinyint      NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_embodiedimpacts` (
  `id`             int          unsigned NOT NULL AUTO_INCREMENT,
  `itemtype`       varchar(255)                   DEFAULT NULL,
  `items_id`       int          unsigned NOT NULL DEFAULT '0',
  `engine`         varchar(255)                   DEFAULT NULL,
  `engine_version` varchar(255)                   DEFAULT NULL,
  `date_mod`       timestamp             NULL     DEFAULT NULL,
  `recalculate`    tinyint               NOT NULL DEFAULT '0',
  `gwp`            float                          DEFAULT '0' COMMENT '(unit g CO2 eq) Global warming potential',
  `gwp_quality`    int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `adp`            float                          DEFAULT '0' COMMENT '(unit g Sb eq) Abiotic depletion potential',
  `adp_quality`    int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `pe`             float                          DEFAULT '0' COMMENT '(unit J) Primary energy',
  `pe_quality`     int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `gwppb`          float                          DEFAULT '0' COMMENT '(unit g CO2 eq) Climate change - Contribution of biogenic emissions',
  `gwppb_quality`  int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `gwppf`          float                          DEFAULT '0' COMMENT '(unit g CO2 eq) Climate change - Contribution of fossil fuel emissions',
  `gwppf_quality`  int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `gwpplu`         float                          DEFAULT '0' COMMENT '(unit g CO2 eq) Climate change - Contribution of emissions from land use change',
  `gwpplu_quality` int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `ir`             float                          DEFAULT '0' COMMENT '(unit g U235 eq) Emissions of radionizing substances',
  `ir_quality`     int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `lu`             float                          DEFAULT '0' COMMENT '(unit none) Land use',
  `lu_quality`     int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `odp`            float                          DEFAULT '0' COMMENT '(unit g CFC-11 eq) Depletion of the ozone layer',
  `odp_quality`    int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `pm`             float                          DEFAULT '0' COMMENT '(unit Disease occurrence) Fine particle emissions',
  `pm_quality`     int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `pocp`           float                          DEFAULT '0' COMMENT '(unit g NMVOC eq) Photochemical ozone formation',
  `pocp_quality`   int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `wu`             float                          DEFAULT '0' COMMENT '(unit M^3) Use of water resources',
  `wu_quality`     int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `mips`           float                          DEFAULT '0' COMMENT '(unit g) Material input per unit of service',
  `mips_quality`   int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `adpe`           float                          DEFAULT '0' COMMENT '(unit g SB eq) Use of mineral and metal resources',
  `adpe_quality`   int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `adpf`           float                          DEFAULT '0' COMMENT '(unit J) Use of fossil resources (including nuclear)',
  `adpf_quality`   int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `ap`             float                          DEFAULT '0' COMMENT '(unit mol H+ eq) Acidification',
  `ap_quality`     int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `ctue`           float                          DEFAULT '0' COMMENT '(unit CTUe) Freshwater ecotoxicity',
  `ctue_quality`   int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `epf`            float                          DEFAULT '0' COMMENT '(unit g P eq) Eutrophication of freshwater',
  `epf_quality`    int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `epm`            float                          DEFAULT '0' COMMENT '(unit g N eq) Eutrophication of marine waters',
  `epm_quality`    int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `ept`            float                          DEFAULT '0' COMMENT '(unit mol N eq) Terrestrial eutrophication',
  `ept_quality`    int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`itemtype`, `items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_environmentalimpacts` (
  `id`                                     int unsigned NOT NULL AUTO_INCREMENT,
  `computers_id`                           int unsigned NOT NULL DEFAULT '0',
  `plugin_carbon_computerusageprofiles_id` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_monitormodels` (
  `id`               int unsigned NOT NULL AUTO_INCREMENT,
  `monitormodels_id` int unsigned NOT NULL DEFAULT '0',
  `gwp`              int                   DEFAULT '0'  COMMENT '(unit gCO2eq) Global warming potential',
  `gwp_source`       mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `gwp_quality`      int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `adp`              int                   DEFAULT '0'  COMMENT '(unit g Sb eq) Abiotic depletion potential',
  `adp_source`       mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `adp_quality`      int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `pe`               int                   DEFAULT '0'  COMMENT '(unit J) Primary energy consumption',
  `pe_source`        mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `pe_quality`       int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `gwppb`            float                 DEFAULT '0'  COMMENT '(unit g CO2 eq) Biogenic climate change potential',
  `gwppb_source`     mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `gwppb_quality`    int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `gwppf`            float                 DEFAULT '0'  COMMENT '(unit g CO2 eq) Fossil climate change potential',
  `gwppf_source`     mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `gwppf_quality`    int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `gwpplu`           float                 DEFAULT '0'  COMMENT '(unit g CO2 eq) Land use change climate potential',
  `gwpplu_source`    mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `gwpplu_quality`   int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `ir`               float                 DEFAULT '0'  COMMENT '(unit g U235 eq) Ionizing radiation potential',
  `ir_source`        mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `ir_quality`       int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `lu`               float                 DEFAULT '0'  COMMENT '(unit m²a) Land use',
  `lu_source`        mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `lu_quality`       int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `odp`              float                 DEFAULT '0'  COMMENT '(unit g CFC-11 eq) Ozone depletion potential',
  `odp_source`       mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `odp_quality`      int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `pm`               float                 DEFAULT '0'  COMMENT '(unit cases) Fine particulate matter potential',
  `pm_source`        mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `pm_quality`       int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `pocp`             float                 DEFAULT '0'  COMMENT '(unit g NMVOC eq) Photochemical ozone creation potential',
  `pocp_source`      mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `pocp_quality`     int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `wu`               float                 DEFAULT '0'  COMMENT '(unit m³) Water use',
  `wu_source`        mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `wu_quality`       int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `mips`             float                 DEFAULT '0'  COMMENT '(unit g) Material input per service unit',
  `mips_source`      mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `mips_quality`     int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `adpe`             float                 DEFAULT '0'  COMMENT '(unit g Sb eq) Abiotic depletion potential (elements)',
  `adpe_source`      mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `adpe_quality`     int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `adpf`             float                 DEFAULT '0'  COMMENT '(unit J) Abiotic depletion potential (fossil fuels)',
  `adpf_source`      mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `adpf_quality`     int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `ap`               float                 DEFAULT '0'  COMMENT '(unit mol H+ eq) Acidification potential',
  `ap_source`        mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `ap_quality`       int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `ctue`             float                 DEFAULT '0'  COMMENT '(unit CTUe) Freshwater ecotoxicity potential',
  `ctue_source`      mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `ctue_quality`     int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `epf`              float                 DEFAULT '0'  COMMENT '(unit g P eq) Freshwater eutrophication potential',
  `epf_source`       mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `epf_quality`      int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `epm`              float                 DEFAULT '0'  COMMENT '(unit g N eq) Marine eutrophication potential',
  `epm_source`       mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `epm_quality`      int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `ept`              float                 DEFAULT '0'  COMMENT '(unit mol N eq) Terrestrial eutrophication potential',
  `ept_source`       mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `ept_quality`      int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`monitormodels_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_monitortypes` (
  `id`                int unsigned NOT NULL AUTO_INCREMENT,
  `monitortypes_id`   int unsigned NOT NULL DEFAULT '0',
  `power_consumption` int          DEFAULT '0',
  `is_ignore`         tinyint      NOT NULL DEFAULT '0' COMMENT 'Ignored from calculations',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`monitortypes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_networkequipmentmodels` (
  `id`                        int unsigned NOT NULL AUTO_INCREMENT,
  `networkequipmentmodels_id` int unsigned NOT NULL DEFAULT '0',
  `gwp`                       int                   DEFAULT '0'  COMMENT '(unit gCO2eq) Global warming potential',
  `gwp_source`                mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `gwp_quality`               int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `adp`                       int                   DEFAULT '0'  COMMENT '(unit g Sb eq) Abiotic depletion potential',
  `adp_source`                mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `adp_quality`               int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `pe`                        int                   DEFAULT '0'  COMMENT '(unit J) Primary energy consumption',
  `pe_source`                 mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `pe_quality`                int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `gwppb`                     float                 DEFAULT '0'  COMMENT '(unit g CO2 eq) Biogenic climate change potential',
  `gwppb_source`              mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `gwppb_quality`             int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `gwppf`                     float                 DEFAULT '0'  COMMENT '(unit g CO2 eq) Fossil climate change potential',
  `gwppf_source`              mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `gwppf_quality`             int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `gwpplu`                    float                 DEFAULT '0'  COMMENT '(unit g CO2 eq) Land use change climate potential',
  `gwpplu_source`             mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `gwpplu_quality`            int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `ir`                        float                 DEFAULT '0'  COMMENT '(unit g U235 eq) Ionizing radiation potential',
  `ir_source`                 mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `ir_quality`                int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `lu`                        float                 DEFAULT '0'  COMMENT '(unit m²a) Land use',
  `lu_source`                 mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `lu_quality`                int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `odp`                       float                 DEFAULT '0'  COMMENT '(unit g CFC-11 eq) Ozone depletion potential',
  `odp_source`                mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `odp_quality`               int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `pm`                        float                 DEFAULT '0'  COMMENT '(unit cases) Fine particulate matter potential',
  `pm_source`                 mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `pm_quality`                int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `pocp`                      float                 DEFAULT '0'  COMMENT '(unit g NMVOC eq) Photochemical ozone creation potential',
  `pocp_source`               mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `pocp_quality`              int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `wu`                        float                 DEFAULT '0'  COMMENT '(unit m³) Water use',
  `wu_source`                 mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `wu_quality`                int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `mips`                      float                 DEFAULT '0'  COMMENT '(unit g) Material input per service unit',
  `mips_source`               mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `mips_quality`              int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `adpe`                      float                 DEFAULT '0'  COMMENT '(unit g Sb eq) Abiotic depletion potential (elements)',
  `adpe_source`               mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `adpe_quality`              int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `adpf`                      float                 DEFAULT '0'  COMMENT '(unit J) Abiotic depletion potential (fossil fuels)',
  `adpf_source`               mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `adpf_quality`              int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `ap`                        float                 DEFAULT '0'  COMMENT '(unit mol H+ eq) Acidification potential',
  `ap_source`                 mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `ap_quality`                int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `ctue`                      float                 DEFAULT '0'  COMMENT '(unit CTUe) Freshwater ecotoxicity potential',
  `ctue_source`               mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `ctue_quality`              int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `epf`                       float                 DEFAULT '0'  COMMENT '(unit g P eq) Freshwater eutrophication potential',
  `epf_source`                mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `epf_quality`               int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `epm`                       float                 DEFAULT '0'  COMMENT '(unit g N eq) Marine eutrophication potential',
  `epm_source`                mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `epm_quality`               int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `ept`                       float                 DEFAULT '0'  COMMENT '(unit mol N eq) Terrestrial eutrophication potential',
  `ept_source`                mediumtext            DEFAULT NULL COMMENT 'any information to describe the source, URL preferred',
  `ept_quality`               int                   DEFAULT '0'  COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`networkequipmentmodels_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_networkequipmenttypes` (
  `id`                       int unsigned NOT NULL AUTO_INCREMENT,
  `networkequipmenttypes_id` int unsigned NOT NULL DEFAULT '0',
  `power_consumption`        int          DEFAULT '0',
  `is_ignore`                tinyint      NOT NULL DEFAULT '0' COMMENT 'Ignored from calculations',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`networkequipmenttypes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_locations` (
  `id`                             int unsigned NOT NULL AUTO_INCREMENT,
  `locations_id`                   int unsigned NOT NULL DEFAULT '0',
  `boavizta_zone`                  varchar(255) DEFAULT NULL,
  `plugin_carbon_sources_zones_id` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`locations_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_usageinfos` (
  `id`                                     int unsigned NOT NULL AUTO_INCREMENT,
  `itemtype`                               varchar(255) DEFAULT NULL,
  `items_id`                               int unsigned NOT NULL DEFAULT '0',
  `plugin_carbon_computerusageprofiles_id` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`itemtype`, `items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_carbon_usageimpacts` (
  `id`             int          unsigned NOT NULL AUTO_INCREMENT,
  `itemtype`       varchar(255)          DEFAULT NULL,
  `items_id`       int          unsigned NOT NULL DEFAULT '0',
  `engine`         varchar(255)          DEFAULT NULL,
  `engine_version` varchar(255)          DEFAULT NULL,
  `date_mod`       timestamp             NULL DEFAULT NULL,
  `recalculate`    tinyint               NOT NULL DEFAULT '0',
  `gwp`            float                 DEFAULT '0'          COMMENT '(unit g CO2 eq) Global warming potential',
  `gwp_quality`    int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `adp`            float                 DEFAULT '0'          COMMENT '(unit g Sb eq) Abiotic depletion potential',
  `adp_quality`    int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `pe`             float                 DEFAULT '0'          COMMENT '(unit J) Primary energy',
  `pe_quality`     int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `gwppb`          float                 DEFAULT '0'          COMMENT '(unit g CO2 eq) Climate change - Contribution of biogenic emissions',
  `gwppb_quality`  int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `gwppf`          float                 DEFAULT '0'          COMMENT '(unit g CO2 eq) Climate change - Contribution of fossil fuel emissions',
  `gwppf_quality`  int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `gwpplu`         float                 DEFAULT '0'          COMMENT '(unit g CO2 eq) Climate change - Contribution of emissions from land use change',
  `gwpplu_quality` int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `ir`             float                 DEFAULT '0'          COMMENT '(unit g U235 eq) Emissions of radionizing substances',
  `ir_quality`     int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `lu`             float                 DEFAULT '0'          COMMENT '(unit none) Land use',
  `lu_quality`     int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `odp`            float                 DEFAULT '0'          COMMENT '(unit g CFC-11 eq) Depletion of the ozone layer',
  `odp_quality`    int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `pm`             float                 DEFAULT '0'          COMMENT '(unit Disease occurrence) Fine particle emissions',
  `pm_quality`     int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `pocp`           float                 DEFAULT '0'          COMMENT '(unit g NMVOC eq) Photochemical ozone formation',
  `pocp_quality`   int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `wu`             float                 DEFAULT '0'          COMMENT '(unit M^3) Use of water resources',
  `wu_quality`     int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `mips`           float                 DEFAULT '0'          COMMENT '(unit g) Material input per unit of service',
  `mips_quality`   int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `adpe`           float                 DEFAULT '0'          COMMENT '(unit g SB eq) Use of mineral and metal resources',
  `adpe_quality`   int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `adpf`           float                 DEFAULT '0'          COMMENT '(unit J) Use of fossil resources (including nuclear)',
  `adpf_quality`   int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `ap`             float                 DEFAULT '0'          COMMENT '(unit mol H+ eq) Acidification',
  `ap_quality`     int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `ctue`           float                 DEFAULT '0'          COMMENT '(unit CTUe) Freshwater ecotoxicity',
  `ctue_quality`   int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `epf`            float                 DEFAULT '0'          COMMENT '(unit g P eq) Eutrophication of freshwater',
  `epf_quality`    int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `epm`            float                 DEFAULT '0'          COMMENT '(unit g N eq) Eutrophication of marine waters',
  `epm_quality`    int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  `ept`            float                 DEFAULT '0'          COMMENT '(unit mol N eq) Terrestrial eutrophication',
  `ept_quality`    int          unsigned NOT NULL DEFAULT '0' COMMENT 'DataTtacking\\AbstractTracked::DATA_QUALITY_* constants',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`itemtype`, `items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
