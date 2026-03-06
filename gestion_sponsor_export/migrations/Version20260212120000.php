<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine.DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260212120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user, sponsor, document and contract tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE `user` (
          `id` INT AUTO_INCREMENT NOT NULL,
          `username` VARCHAR(255) NOT NULL,
          `email` VARCHAR(255) NOT NULL,
          `password` VARCHAR(255) NOT NULL,
          `bio` LONGTEXT DEFAULT NULL,
          `created_at` DATETIME NOT NULL,
          `telephone` VARCHAR(20) DEFAULT NULL,
          `role` VARCHAR(20) NOT NULL,
          `nom` VARCHAR(255) DEFAULT NULL,
          `prenom` VARCHAR(255) DEFAULT NULL,
          `datenaissance` DATE DEFAULT NULL,
          `image` VARCHAR(255) DEFAULT NULL,
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE `sponsor` (
          `id` INT AUTO_INCREMENT NOT NULL,
          `nom_societe` VARCHAR(255) NOT NULL,
          `description` LONGTEXT NOT NULL,
          `amount` DOUBLE NOT NULL,
          `target_type` VARCHAR(50) NOT NULL,
          `dossier` VARCHAR(255) DEFAULT NULL,
          `sponsor_id` INT NOT NULL,
          `created_at` DATETIME NOT NULL,
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE `document` (
          `id` INT AUTO_INCREMENT NOT NULL,
          `client_id` INT NOT NULL,
          `offer_id` INT NOT NULL,
          `message` LONGTEXT NOT NULL,
          `motivation` LONGTEXT NOT NULL,
          `status` VARCHAR(20) NOT NULL DEFAULT "en attente",
          `created_at` DATETIME NOT NULL,
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE `contract` (
          `id` INT AUTO_INCREMENT NOT NULL,
          `request_id` INT NOT NULL,
          `sponsor_id` INT NOT NULL,
          `client_id` INT NOT NULL,
          `created_at` DATETIME NOT NULL,
          `content` LONGTEXT DEFAULT NULL,
          PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE `sponsor` ADD CONSTRAINT `FK_SPONSOR_USER` FOREIGN KEY (`sponsor_id`) REFERENCES `user`(`id`)');
        $this->addSql('ALTER TABLE `document` ADD CONSTRAINT `FK_DOCUMENT_CLIENT` FOREIGN KEY (`client_id`) REFERENCES `user`(`id`)');
        $this->addSql('ALTER TABLE `document` ADD CONSTRAINT `FK_DOCUMENT_OFFER` FOREIGN KEY (`offer_id`) REFERENCES `sponsor`(`id`)');
        $this->addSql('ALTER TABLE `contract` ADD CONSTRAINT `FK_CONTRACT_REQUEST` FOREIGN KEY (`request_id`) REFERENCES `document`(`id`)');
        $this->addSql('ALTER TABLE `contract` ADD CONSTRAINT `FK_CONTRACT_SPONSOR` FOREIGN KEY (`sponsor_id`) REFERENCES `user`(`id`)');
        $this->addSql('ALTER TABLE `contract` ADD CONSTRAINT `FK_CONTRACT_CLIENT` FOREIGN KEY (`client_id`) REFERENCES `user`(`id`)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `contract` DROP FOREIGN KEY `FK_CONTRACT_CLIENT`');
        $this->addSql('ALTER TABLE `contract` DROP FOREIGN KEY `FK_CONTRACT_SPONSOR`');
        $this->addSql('ALTER TABLE `contract` DROP FOREIGN KEY `FK_CONTRACT_REQUEST`');
        $this->addSql('ALTER TABLE `document` DROP FOREIGN KEY `FK_DOCUMENT_OFFER`');
        $this->addSql('ALTER TABLE `document` DROP FOREIGN KEY `FK_DOCUMENT_CLIENT`');
        $this->addSql('ALTER TABLE `sponsor` DROP FOREIGN KEY `FK_SPONSOR_USER`');

        $this->addSql('DROP TABLE `contract`');
        $this->addSql('DROP TABLE `document`');
        $this->addSql('DROP TABLE `sponsor`');
        $this->addSql('DROP TABLE `user`');
    }
}
