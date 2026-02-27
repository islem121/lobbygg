<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260227093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure registration-related user columns exist for admin listing (genre, origin, face_descriptor).';
    }

    public function up(Schema $schema): void
    {
        // Keep migration idempotent: only add columns if they are missing.
        $this->addSql('ALTER TABLE user
            ADD COLUMN IF NOT EXISTS genre VARCHAR(20) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS origin VARCHAR(100) DEFAULT NULL,
            ADD COLUMN IF NOT EXISTS face_descriptor LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user
            DROP COLUMN IF EXISTS genre,
            DROP COLUMN IF EXISTS origin,
            DROP COLUMN IF EXISTS face_descriptor');
    }
}

