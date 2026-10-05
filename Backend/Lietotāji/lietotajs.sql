-- Izmanto kopīgo veikala datubāzi.
CREATE DATABASE IF NOT EXISTS `ShocoStore`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `ShocoStore`;

-- Lietotāju konti. Parolē glabā paroles jaucējvērtību, nevis atklātu paroli.
CREATE TABLE IF NOT EXISTS `Lietotajs` (
  `Lietotajs_ID` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unikāls lietotāja identifikators',
  `Vards` VARCHAR(100) NOT NULL COMMENT 'Lietotāja vārds',
  `Epasts` VARCHAR(255) NOT NULL COMMENT 'Lietotāja e-pasta adrese',
  `Parole` VARCHAR(255) NOT NULL COMMENT 'Paroles jaucējvērtība',
  PRIMARY KEY (`Lietotajs_ID`),
  UNIQUE KEY `UQ_Lietotajs_Epasts` (`Epasts`)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci
  COMMENT='Veikala lietotāju konti';