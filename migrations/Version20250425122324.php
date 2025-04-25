<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250425122324 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE booking ADD start_date DATE NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking ADD end_date DATE NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE hotel ADD city VARCHAR(100) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE payment ADD payment_method VARCHAR(50) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE payment ADD transaction_id VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE payment ALTER stripe_id DROP NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE payment DROP payment_method
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE payment DROP transaction_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE payment ALTER stripe_id SET NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking DROP start_date
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking DROP end_date
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE hotel DROP city
        SQL);
    }
}
