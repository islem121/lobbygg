<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260227160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add matchmaking tables, user skill ratings, and tournament score records';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_skill_rating (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, rating INT NOT NULL DEFAULT 1000, updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX uniq_skill_user (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_skill_rating ADD CONSTRAINT FK_2F7F78E5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE tournament_matchmaking_queue (id INT AUTO_INCREMENT NOT NULL, tournament_id INT NOT NULL, user_id INT NOT NULL, queued_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_A5B148D549AC3D66 (tournament_id), INDEX IDX_A5B148D5A76ED395 (user_id), UNIQUE INDEX uniq_tournament_queue_user (tournament_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tournament_matchmaking_queue ADD CONSTRAINT FK_A5B148D549AC3D66 FOREIGN KEY (tournament_id) REFERENCES tournament (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_matchmaking_queue ADD CONSTRAINT FK_A5B148D5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE tournament_match (id INT AUTO_INCREMENT NOT NULL, tournament_id INT NOT NULL, player_one_id INT NOT NULL, player_two_id INT NOT NULL, winner_id INT DEFAULT NULL, round_number INT NOT NULL DEFAULT 1, score_one INT NOT NULL DEFAULT 0, score_two INT NOT NULL DEFAULT 0, status VARCHAR(20) NOT NULL DEFAULT \'pending\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_4AB2D5949AC3D66 (tournament_id), INDEX IDX_4AB2D5947824E817 (player_one_id), INDEX IDX_4AB2D5949F3A866B (player_two_id), INDEX IDX_4AB2D594B8D77947 (winner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tournament_match ADD CONSTRAINT FK_4AB2D5949AC3D66 FOREIGN KEY (tournament_id) REFERENCES tournament (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_match ADD CONSTRAINT FK_4AB2D5947824E817 FOREIGN KEY (player_one_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_match ADD CONSTRAINT FK_4AB2D5949F3A866B FOREIGN KEY (player_two_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_match ADD CONSTRAINT FK_4AB2D594B8D77947 FOREIGN KEY (winner_id) REFERENCES user (id) ON DELETE SET NULL');

        $this->addSql('CREATE TABLE tournament_score_record (id INT AUTO_INCREMENT NOT NULL, tournament_id INT NOT NULL, user_id INT NOT NULL, score INT NOT NULL DEFAULT 0, source VARCHAR(20) NOT NULL DEFAULT \'random\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_95F14DBF49AC3D66 (tournament_id), INDEX IDX_95F14DBFA76ED395 (user_id), UNIQUE INDEX uniq_score_tournament_user (tournament_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tournament_score_record ADD CONSTRAINT FK_95F14DBF49AC3D66 FOREIGN KEY (tournament_id) REFERENCES tournament (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_score_record ADD CONSTRAINT FK_95F14DBFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tournament_score_record DROP FOREIGN KEY FK_95F14DBF49AC3D66');
        $this->addSql('ALTER TABLE tournament_score_record DROP FOREIGN KEY FK_95F14DBFA76ED395');
        $this->addSql('DROP TABLE tournament_score_record');

        $this->addSql('ALTER TABLE tournament_match DROP FOREIGN KEY FK_4AB2D5949AC3D66');
        $this->addSql('ALTER TABLE tournament_match DROP FOREIGN KEY FK_4AB2D5947824E817');
        $this->addSql('ALTER TABLE tournament_match DROP FOREIGN KEY FK_4AB2D5949F3A866B');
        $this->addSql('ALTER TABLE tournament_match DROP FOREIGN KEY FK_4AB2D594B8D77947');
        $this->addSql('DROP TABLE tournament_match');

        $this->addSql('ALTER TABLE tournament_matchmaking_queue DROP FOREIGN KEY FK_A5B148D549AC3D66');
        $this->addSql('ALTER TABLE tournament_matchmaking_queue DROP FOREIGN KEY FK_A5B148D5A76ED395');
        $this->addSql('DROP TABLE tournament_matchmaking_queue');

        $this->addSql('ALTER TABLE user_skill_rating DROP FOREIGN KEY FK_2F7F78E5A76ED395');
        $this->addSql('DROP TABLE user_skill_rating');
    }
}

