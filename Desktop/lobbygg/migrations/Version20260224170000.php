<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260224170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tournament mode/entry fee/AI flag and create voucher table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE tournament ADD mode VARCHAR(20) NOT NULL DEFAULT 'solo', ADD entry_fee NUMERIC(10, 2) NOT NULL DEFAULT 0.00, ADD is_ai_generated TINYINT(1) NOT NULL DEFAULT 0");
        $this->addSql('CREATE TABLE voucher (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, tournament_id INT NOT NULL, code VARCHAR(64) NOT NULL, amount NUMERIC(10, 2) NOT NULL, purchased_at DATETIME NOT NULL, used_at DATETIME DEFAULT NULL, INDEX IDX_VOUCHER_USER (user_id), INDEX IDX_VOUCHER_TOURNAMENT (tournament_id), UNIQUE INDEX uniq_voucher_code (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE voucher ADD CONSTRAINT FK_VOUCHER_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE voucher ADD CONSTRAINT FK_VOUCHER_TOURNAMENT FOREIGN KEY (tournament_id) REFERENCES tournament (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE voucher DROP FOREIGN KEY FK_VOUCHER_USER');
        $this->addSql('ALTER TABLE voucher DROP FOREIGN KEY FK_VOUCHER_TOURNAMENT');
        $this->addSql('DROP TABLE voucher');
        $this->addSql('ALTER TABLE tournament DROP mode, DROP entry_fee, DROP is_ai_generated');
    }
}
