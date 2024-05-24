CREATE TABLE `glpi_plugin_carbon_carbonemissions` (
  `id`               int unsigned NOT NULL AUTO_INCREMENT,
  `computers_id`     int unsigned NOT NULL DEFAULT '0',
  `emission_per_day` float        DEFAULT '0',
  `emission_date`    timestamp    NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `glpi_plugin_carbon_carbonintensities` (
  `id`                                      int unsigned NOT NULL AUTO_INCREMENT,
  `emission_date`                           timestamp    NULL DEFAULT NULL,
  `plugin_carbon_carbonintensitysources_id` int unsigned NOT NULL DEFAULT '0',
  `plugin_carbon_carbonintensityzones_id`   int unsigned NOT NULL DEFAULT '0',
  `intensity`                               float        DEFAULT '0'   COMMENT 'CO2eq/KWh',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`emission_date`, `plugin_carbon_carbonintensitysources_id`, `plugin_carbon_carbonintensityzones_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `glpi_plugin_carbon_carbonintensityzones` (
  `id`               int unsigned NOT NULL AUTO_INCREMENT,
  `name`             varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `glpi_plugin_carbon_carbonintensitysources` (
  `id`               int unsigned NOT NULL AUTO_INCREMENT,
  `name`             varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `glpi_plugin_carbon_computertypes` (
  `id`                int unsigned NOT NULL AUTO_INCREMENT,
  `computertypes_id`  int unsigned NOT NULL DEFAULT '0',
  `power_consumption` int          DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`computertypes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `glpi_plugin_carbon_computerusageprofiles` (
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

CREATE TABLE `glpi_plugin_carbon_environnementalimpacts` (
  `id`                                     int unsigned NOT NULL AUTO_INCREMENT,
  `computers_id`                           int unsigned NOT NULL DEFAULT '0',
  `plugin_carbon_computerusageprofiles_id` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`computers_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
