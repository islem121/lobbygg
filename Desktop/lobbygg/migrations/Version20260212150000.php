<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260212150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add relations to order: product_id and user_id with foreign keys';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` ADD product_id INT NOT NULL, ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_ORDER_PRODUCT FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_ORDER_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE RESTRICT');
        $this->addSql('CREATE INDEX IDX_ORDER_PRODUCT_ID ON `order` (product_id)');
        $this->addSql('CREATE INDEX IDX_ORDER_USER_ID ON `order` (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_ORDER_PRODUCT');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_ORDER_USER');
        $this->addSql('DROP INDEX IDX_ORDER_PRODUCT_ID ON `order`');
        $this->addSql('DROP INDEX IDX_ORDER_USER_ID ON `order`');
        $this->addSql('ALTER TABLE `order` DROP product_id, DROP user_id');
    }
}
