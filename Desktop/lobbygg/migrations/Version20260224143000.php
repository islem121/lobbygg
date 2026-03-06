<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260224143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing relation columns to tournament_participation (user_id, tournament_id) with indexes and FKs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tournament_participation ADD user_id INT DEFAULT NULL, ADD tournament_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_TP_USER_ID ON tournament_participation (user_id)');
        $this->addSql('CREATE INDEX IDX_TP_TOURNAMENT_ID ON tournament_participation (tournament_id)');
        $this->addSql('ALTER TABLE tournament_participation ADD CONSTRAINT FK_TP_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournament_participation ADD CONSTRAINT FK_TP_TOURNAMENT FOREIGN KEY (tournament_id) REFERENCES tournament (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX uniq_tournament_user ON tournament_participation (tournament_id, user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_tournament_user ON tournament_participation');
        $this->addSql('ALTER TABLE tournament_participation DROP FOREIGN KEY FK_TP_USER');
        $this->addSql('ALTER TABLE tournament_participation DROP FOREIGN KEY FK_TP_TOURNAMENT');
        $this->addSql('DROP INDEX IDX_TP_USER_ID ON tournament_participation');
        $this->addSql('DROP INDEX IDX_TP_TOURNAMENT_ID ON tournament_participation');
        $this->addSql('ALTER TABLE tournament_participation DROP user_id, DROP tournament_id');
    }
}
