-- Izveido veikala datubāzi, ja tā vēl nepastāv.
CREATE DATABASE IF NOT EXISTS `ShocoStore`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `ShocoStore`;

-- Preču katalogs: cena un atlikums tiek glabāti katrai precei.
CREATE TABLE IF NOT EXISTS `Prece` (
  `Prece_ID` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unikāls preces identifikators',
  `Nosaukums` VARCHAR(100) NOT NULL COMMENT 'Preces nosaukums',
  `Cena` DECIMAL(10,2) NOT NULL COMMENT 'Preces cena',
  `Atlikums` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Preču atlikums noliktavā',
  `Apraksts` VARCHAR(1000) NULL COMMENT 'Preces apraksts',
  `Tonis` VARCHAR(25) NOT NULL DEFAULT 'mango' COMMENT 'Preces attēla krāsu variants',
  `Birka` VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'Preces birka katalogā',
  PRIMARY KEY (`Prece_ID`)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci
  COMMENT='Veikala preču katalogs';