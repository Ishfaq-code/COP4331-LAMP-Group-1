-- ============================================================
-- COP4331 Contact Manager Database
-- ============================================================

-- Create and select the project database
CREATE DATABASE IF NOT EXISTS `ContactManagerDB`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `ContactManagerDB`;

-- Create Users table
CREATE TABLE IF NOT EXISTS `Users` (
    `ID` INT NOT NULL AUTO_INCREMENT,
    `FirstName` VARCHAR(50) NOT NULL,
    `LastName` VARCHAR(50) NOT NULL,
    `Login` VARCHAR(100) NOT NULL,
    `Password` VARCHAR(255) NOT NULL,
    `DateCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `DateUpdated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`ID`),
    UNIQUE INDEX `idx_users_login` (`Login`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Create Contacts table
CREATE TABLE IF NOT EXISTS `Contacts` (
    `ID` INT NOT NULL AUTO_INCREMENT,
    `FirstName` VARCHAR(50) NOT NULL,
    `LastName` VARCHAR(50) NOT NULL,
    `EmailAddress` VARCHAR(100),
    `PhoneNumber` VARCHAR(20),
    `DateCreated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `DateUpdated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    `UserID` INT NOT NULL,

    PRIMARY KEY (`ID`),
    INDEX `idx_contacts_userid` (`UserID`),

    CONSTRAINT `fk_contacts_users`
        FOREIGN KEY (`UserID`)
        REFERENCES `Users` (`ID`)
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
