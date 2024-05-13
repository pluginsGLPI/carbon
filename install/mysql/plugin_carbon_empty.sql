CREATE TABLE `glpi_plugin_carbon_carbonemissions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `computers_id` int unsigned NOT NULL DEFAULT '0',
  `emission_per_day` float DEFAULT '0',
  `emission_date` timestamp DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `glpi_plugin_carbon_powermodelcategories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `glpi_plugin_carbon_powermodels` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `power` float DEFAULT '0',
  `plugin_carbon_powermodelcategories_id` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `glpi_plugin_carbon_powermodels_computermodels` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_carbon_powermodels_id` int unsigned NOT NULL DEFAULT '0',
  `computermodels_id` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_carbon_powermodels_id`,`computermodels_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `glpi_plugin_carbon_powers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `computers_id` int unsigned NOT NULL DEFAULT '0',
  `power` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `glpi_plugin_carbon_computertypes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `computertypes_id` int unsigned NOT NULL DEFAULT '0',
  `power_consumption` int DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`computertypes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CREATE TABLE `glpi_plugin_carbon_monitortypes` (
--   `id` int unsigned NOT NULL AUTO_INCREMENT,
--   `monitortypes_id` int unsigned NOT NULL DEFAULT '0',
--   `power_consumption` int DEFAULT '0',
--   PRIMARY KEY (`id`),
--   UNIQUE KEY `unicity` (`monitortypes_id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
