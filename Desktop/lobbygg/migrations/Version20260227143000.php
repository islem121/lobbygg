<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260227143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create leaderboard, waiting list, and tournament notifications tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE leaderboard_entry (id INT AUTO_INCREMENT NOT NULL, tournament_id INT NOT NULL, user_id INT NOT NULL, wins INT NOT NULL DEFAULT 0, losses INT NOT NULL DEFAULT 0, points INT NOT NULL DEFAULT 0, rank_position INT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7B89A06749AC3D66 (tournament_id), INDEX IDX_7B89A067A76ED395 (user_id), UNIQUE INDEX uniq_leaderboard_tournament_user (tournament_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE leaderboard_entry ADD CONSTRAINT FK_7B89A06749AC3D66 FOREIGN KEY (tournament_id) REFERENCES tournament (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE leaderboard_entry ADD CONSTRAINT FK_7B89A067A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE waiting_list_entry (id INT AUTO_INCREMENT NOT NULL, tournament_id INT NOT NULL, user_id INT NOT NULL, position INT NOT NULL DEFAULT 1, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_6A6DFAE349AC3D66 (tournament_id), INDEX IDX_6A6DFAE3A76ED395 (user_id), UNIQUE INDEX uniq_waiting_tournament_user (tournament_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE waiting_list_entry ADD CONSTRAINT FK_6A6DFAE349AC3D66 FOREIGN KEY (tournament_id) REFERENCES tournament (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE waiting_list_entry ADD CONSTRAINT FK_6A6DFAE3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE tournament_notification (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, tournament_id INT NOT NULL, message LONGTEXT NOT NULL, is_read TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_910C6138A76ED395 (user_id), INDEX IDX_910C613849AC3D66 (tournament_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tournament_notification ADD CONSTRAINT FK_910C6138A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_notification ADD CONSTRAINT FK_910C613849AC3D66 FOREIGN KEY (tournament_id) REFERENCES tournament (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE leaderboard_entry DROP FOREIGN KEY FK_7B89A06749AC3D66');
        $this->addSql('ALTER TABLE leaderboard_entry DROP FOREIGN KEY FK_7B89A067A76ED395');
        $this->addSql('DROP TABLE leaderboard_entry');

        $this->addSql('ALTER TABLE waiting_list_entry DROP FOREIGN KEY FK_6A6DFAE349AC3D66');
        $this->addSql('ALTER TABLE waiting_list_entry DROP FOREIGN KEY FK_6A6DFAE3A76ED395');
        $this->addSql('DROP TABLE waiting_list_entry');

        $this->addSql('ALTER TABLE tournament_notification DROP FOREIGN KEY FK_910C6138A76ED395');
        $this->addSql('ALTER TABLE tournament_notification DROP FOREIGN KEY FK_910C613849AC3D66');
        $this->addSql('DROP TABLE tournament_notification');
    }
}

