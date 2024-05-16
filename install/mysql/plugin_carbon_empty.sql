CREATE TABLE `glpi_plugin_carbon_carbonemissions` (
  `id`               int unsigned NOT NULL AUTO_INCREMENT,
  `computers_id`     int unsigned NOT NULL DEFAULT '0',
  `emission_per_day` float        DEFAULT '0',
  `emission_date`    timestamp    DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `glpi_plugin_carbon_powermodelcategories` (
  `id`   int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `glpi_plugin_carbon_powermodels` (
  `id`    int unsigned NOT NULL AUTO_INCREMENT,
  `name`  varchar(255) DEFAULT NULL,
  `power` float        DEFAULT '0',
  `plugin_carbon_powermodelcategories_id` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `glpi_plugin_carbon_powermodels_computermodels` (
  `id`                           int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_carbon_powermodels_id` int unsigned NOT NULL DEFAULT '0',
  `computermodels_id`            int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_carbon_powermodels_id`,`computermodels_id`)
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
