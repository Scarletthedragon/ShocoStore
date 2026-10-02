-- Izmanto kopīgo veikala datubāzi.
CREATE DATABASE IF NOT EXISTS `ShocoStore`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `ShocoStore`;

-- Pasūtījumi sasaista lietotāju ar preci un saglabā pirkuma kopsummu.
-- Pirms šī skripta izpildes izveido tabulas `Lietotajs` un `Prece`.

CREATE TABLE IF NOT EXISTS `Pasutijums` (
  `Pasutijums_ID` INT NOT NULL AUTO_INCREMENT COMMENT 'Unikāls pasūtījuma identifikators',
  `Lietotajs_ID` INT NOT NULL COMMENT 'Pasūtījuma veicēja identifikators',
  `Prece_ID` INT NOT NULL COMMENT 'Pasūtītās preces identifikators',
  `Daudzums` INT NOT NULL COMMENT 'Pasūtītais preču daudzums',
  `Kopeja_cena` DECIMAL(10,2) NOT NULL COMMENT 'Pasūtījuma kopsumma pirkuma brīdī',
  `Statuss` VARCHAR(20) NOT NULL DEFAULT 'gaida' COMMENT 'Pasūtījuma statuss',
  `Izveidots` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Pasūtījuma izveides datums un laiks',
  PRIMARY KEY (`Pasutijums_ID`),
  KEY `IX_Pasutijums_Lietotajs_ID` (`Lietotajs_ID`),
  KEY `IX_Pasutijums_Prece_ID` (`Prece_ID`),
  CONSTRAINT `FK_Pasutijums_Lietotajs`
    FOREIGN KEY (`Lietotajs_ID`) REFERENCES `Lietotajs` (`Lietotajs_ID`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT `FK_Pasutijums_Prece`
    FOREIGN KEY (`Prece_ID`) REFERENCES `Prece` (`Prece_ID`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci
  COMMENT='Lietotāju veiktie preču pasūtījumi';