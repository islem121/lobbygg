-- Schema SQL généré à partir des entités du projet
-- Utilisez MySQL / MariaDB (InnoDB, utf8mb4)

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `contract`;
DROP TABLE IF EXISTS `document`;
DROP TABLE IF EXISTS `sponsor`;
DROP TABLE IF EXISTS `user`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `user` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `username` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `bio` TEXT NULL,
  `created_at` DATETIME NOT NULL,
  `telephone` VARCHAR(20) NULL,
  `role` VARCHAR(20) NOT NULL,
  `nom` VARCHAR(255) NULL,
  `prenom` VARCHAR(255) NULL,
  `datenaissance` DATE NULL,
  `image` VARCHAR(255) NULL,
  PRIMARY KEY(`id`),
  UNIQUE KEY `UNIQ_user_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sponsor` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `nom_societe` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `amount` DOUBLE NOT NULL,
  `target_type` VARCHAR(50) NOT NULL,
  `dossier` VARCHAR(255) NULL,
  `sponsor_id` INT NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY(`id`),
  KEY `IDX_SPONSOR_USER` (`sponsor_id`),
  CONSTRAINT `FK_SPONSOR_USER` FOREIGN KEY (`sponsor_id`) REFERENCES `user`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `document` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `client_id` INT NOT NULL,
  `offer_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `motivation` TEXT NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'en attente',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY(`id`),
  KEY `IDX_DOCUMENT_CLIENT` (`client_id`),
  KEY `IDX_DOCUMENT_OFFER` (`offer_id`),
  CONSTRAINT `FK_DOCUMENT_CLIENT` FOREIGN KEY (`client_id`) REFERENCES `user`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `FK_DOCUMENT_OFFER` FOREIGN KEY (`offer_id`) REFERENCES `sponsor`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `contract` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `request_id` INT NOT NULL,
  `sponsor_id` INT NOT NULL,
  `client_id` INT NOT NULL,
  `created_at` DATETIME NOT NULL,
  `content` TEXT NULL,
  PRIMARY KEY(`id`),
  UNIQUE KEY `UNIQ_CONTRACT_REQUEST` (`request_id`),
  KEY `IDX_CONTRACT_SPONSOR` (`sponsor_id`),
  KEY `IDX_CONTRACT_CLIENT` (`client_id`),
  CONSTRAINT `FK_CONTRACT_REQUEST` FOREIGN KEY (`request_id`) REFERENCES `document`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `FK_CONTRACT_SPONSOR` FOREIGN KEY (`sponsor_id`) REFERENCES `user`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `FK_CONTRACT_CLIENT` FOREIGN KEY (`client_id`) REFERENCES `user`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fin du schema
