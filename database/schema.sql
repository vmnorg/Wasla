-- WASLA Database Schema
-- Agri-Logistics Platform for Souss-Massa

-- Enable strict modes and character sets
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- Table `communes`
-- Holds the 6 predefined Souss-Massa communes of interest
-- -----------------------------------------------------
DROP TABLE IF EXISTS `communes`;
CREATE TABLE `communes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Primary key',
    `nom` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Name of the commune in Souss-Massa',
    `latitude` DECIMAL(10, 8) NOT NULL COMMENT 'Centroid latitude of the commune',
    `longitude` DECIMAL(11, 8) NOT NULL COMMENT 'Centroid longitude of the commune',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation timestamp',
    INDEX `idx_commune_coords` (`latitude`, `longitude`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Predefined communes in Souss-Massa region';

-- -----------------------------------------------------
-- Table `offers`
-- Holds specific crop offers submitted by farmers
-- -----------------------------------------------------
DROP TABLE IF EXISTS `offers`;
CREATE TABLE `offers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Primary key',
    `phone` VARCHAR(20) NOT NULL COMMENT 'Farmer contact phone number',
    `produit` VARCHAR(100) NOT NULL COMMENT 'Type of agricultural product (e.g., Tomato, Argan, Carrot)',
    `quantite_kg` DECIMAL(10, 2) NOT NULL COMMENT 'Quantity in kilograms',
    `qualite` VARCHAR(50) DEFAULT NULL COMMENT 'Quality grade extracted from voice/text (e.g., Premium, Medium)',
    `commune_id` INT NOT NULL COMMENT 'Linked reference commune',
    `latitude` DECIMAL(10, 8)  NOT NULL COMMENT 'Geographic latitude of the specific offer source',
    `longitude` DECIMAL(11, 8) NOT NULL COMMENT 'Geographic longitude of the specific offer source',
    `transcription_darija` TEXT DEFAULT NULL COMMENT 'Raw Darija voice/text transcription from farmer',
    `status` ENUM('pending', 'assigned', 'completed') NOT NULL DEFAULT 'pending' COMMENT 'Lifecycle status of this individual offer',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation timestamp',
    CONSTRAINT `fk_offers_commune` FOREIGN KEY (`commune_id`)
        REFERENCES `communes` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    INDEX `idx_offer_status` (`status`),
    INDEX `idx_offer_coords` (`latitude`, `longitude`),
    INDEX `idx_offer_commune` (`commune_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Individual farm offers submitted by farmers';

-- -----------------------------------------------------
-- Table `lots`
-- Represents aggregated transport groups (batches of offers)
-- -----------------------------------------------------
DROP TABLE IF EXISTS `lots`;
CREATE TABLE `lots` (
    `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Primary key',
    `tonnage_total_kg` DECIMAL(12, 2) NOT NULL DEFAULT 0.00 COMMENT 'Total consolidated weight of the lot in kg',
    `centroid_lat` DECIMAL(10, 8) NOT NULL COMMENT 'Geographical center latitude of all aggregated offers',
    `centroid_lon` DECIMAL(11, 8) NOT NULL COMMENT 'Geographical center longitude of all aggregated offers',
    `co2_evite_kg` DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Estimated carbon dioxide avoided in kilograms by pooling',
    `status` ENUM('open', 'in_transit', 'delivered') NOT NULL DEFAULT 'open' COMMENT 'Logistical status of the overall batch',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation timestamp',
    INDEX `idx_lot_status` (`status`),
    INDEX `idx_lot_centroid` (`centroid_lat`, `centroid_lon`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Aggregated logistic lots representing combined crop offers';

-- -----------------------------------------------------
-- Table `lot_offers`
-- Pivot table linking offers to their aggregated lots
-- -----------------------------------------------------
DROP TABLE IF EXISTS `lot_offers`;
CREATE TABLE `lot_offers` (
    `lot_id` INT NOT NULL COMMENT 'Foreign key to lots table',
    `offer_id` INT NOT NULL UNIQUE COMMENT 'Foreign key to offers table (Unique because an offer belongs to at most one lot)',
    PRIMARY KEY (`lot_id`, `offer_id`),
    CONSTRAINT `fk_pivot_lot` FOREIGN KEY (`lot_id`)
        REFERENCES `lots` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_pivot_offer` FOREIGN KEY (`offer_id`)
        REFERENCES `offers` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Many-to-one mapping linking active offers to aggregated logistic lots';

SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------
-- Seed Data for Communes
-- Core 6 communes of interest in Souss-Massa region
-- -----------------------------------------------------
INSERT INTO `communes` (`nom`, `latitude`, `longitude`) VALUES
('Tiznit', 29.69750000, -9.73170000),
('Inezgane', 30.36580000, -9.53810000),
('Taroudant', 30.47030000, -8.87700000),
('Oulad Teima', 30.39580000, -9.20840000),
('Belfaa', 30.04140000, -9.56390000),
('Massa', 30.00780000, -9.63810000);
