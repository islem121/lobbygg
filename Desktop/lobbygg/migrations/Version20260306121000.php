<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260306121000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add external tournament interests table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE external_tournament_interest (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, title VARCHAR(255) NOT NULL, source VARCHAR(120) NOT NULL, url VARCHAR(1024) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_A93D7149A76ED395 (user_id), UNIQUE INDEX uniq_external_interest_user_url (user_id, url), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE external_tournament_interest ADD CONSTRAINT FK_A93D7149A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE external_tournament_interest DROP FOREIGN KEY FK_A93D7149A76ED395');
        $this->addSql('DROP TABLE external_tournament_interest');
    }
}
