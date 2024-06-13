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

CREATE TABLE `ElectricityMapZone` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `latitude` float,
  `longitude` float,
  `zone` varchar(255),
  `zone_name` varchar(255),
  `country_name` varchar(255)
);

CREATE TABLE `ElectricityMapZone_Computer` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `electricitymapzone_id` int,
  `computers_id` int
);

CREATE TABLE `CarbonEmission` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `emission` float,
  `emission_date` datetime,
  `computers_id` int
);

CREATE TABLE `ComputerModel` (
  `id` int PRIMARY KEY AUTO_INCREMENT
);

CREATE TABLE `Computer` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `computermodels_id` int
);

ALTER TABLE `ComputerModel` COMMENT = 'This table is GLPI native';

ALTER TABLE `Computer` COMMENT = 'This table is GLPI native';

ALTER TABLE `Power` ADD FOREIGN KEY (`computers_id`) REFERENCES `Computer` (`id`);

ALTER TABLE `PowerModel` ADD FOREIGN KEY (`powermodelcategories_id`) REFERENCES `PowerModelCategory` (`id`);

ALTER TABLE `PowerModel_ComputerModel` ADD FOREIGN KEY (`computermodels_id`) REFERENCES `ComputerModel` (`id`);

ALTER TABLE `PowerModel_ComputerModel` ADD FOREIGN KEY (`powermodels_id`) REFERENCES `PowerModel` (`id`);

ALTER TABLE `ElectricityMapZone_Computer` ADD FOREIGN KEY (`electricitymapzone_id`) REFERENCES `ElectricityMapZone` (`id`);

ALTER TABLE `ElectricityMapZone_Computer` ADD FOREIGN KEY (`computers_id`) REFERENCES `Computer` (`id`);

ALTER TABLE `CarbonEmission` ADD FOREIGN KEY (`computers_id`) REFERENCES `Computer` (`id`);

ALTER TABLE `Computer` ADD FOREIGN KEY (`computermodels_id`) REFERENCES `ComputerModel` (`id`);
