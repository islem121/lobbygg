<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260227120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique voucher constraint on (user_id, tournament_id) for one voucher per user per tournament';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $indexes = $schemaManager->listTableIndexes('voucher');
        if (isset($indexes['uniq_voucher_user_tournament'])) {
            return;
        }

        $duplicateCount = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM (
                SELECT user_id, tournament_id, COUNT(*) c
                FROM voucher
                GROUP BY user_id, tournament_id
                HAVING c > 1
            ) AS duplicate_vouchers'
        );

        $this->abortIf(
            $duplicateCount > 0,
            'Cannot add uniq_voucher_user_tournament: duplicate voucher rows exist. Clean duplicates first.'
        );

        $this->addSql('CREATE UNIQUE INDEX uniq_voucher_user_tournament ON voucher (user_id, tournament_id)');
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $indexes = $schemaManager->listTableIndexes('voucher');
        if (!isset($indexes['uniq_voucher_user_tournament'])) {
            return;
        }

        $this->addSql('DROP INDEX uniq_voucher_user_tournament ON voucher');
    }
}

