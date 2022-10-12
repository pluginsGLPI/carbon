CREATE TABLE `PowerModelCategory` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255)
);

CREATE TABLE `Power` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `computers_id` int,
  `power` float
);

CREATE TABLE `PowerModel` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(255),
  `power` float,
  `powermodelcategories_id` int
);

CREATE TABLE `PowerModel_ComputerModel` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `computermodels_id` int,
  `powermodels_id` int
);

CREATE TABLE `ComputerModel` (
  `id` int PRIMARY KEY AUTO_INCREMENT
);

CREATE TABLE `Computer` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `computermodels_id` int
);

ALTER TABLE `Power` ADD FOREIGN KEY (`computers_id`) REFERENCES `Computer` (`id`);

ALTER TABLE `PowerModel` ADD FOREIGN KEY (`powermodelcategories_id`) REFERENCES `PowerModelCategory` (`id`);

ALTER TABLE `PowerModel_ComputerModel` ADD FOREIGN KEY (`computermodels_id`) REFERENCES `ComputerModel` (`id`);

ALTER TABLE `PowerModel_ComputerModel` ADD FOREIGN KEY (`powermodels_id`) REFERENCES `PowerModel` (`id`);

ALTER TABLE `Computer` ADD FOREIGN KEY (`computermodels_id`) REFERENCES `ComputerModel` (`id`);
