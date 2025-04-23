<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250422225545 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE booking (id SERIAL NOT NULL, negotiation_id INT NOT NULL, payment_id INT DEFAULT NULL, status VARCHAR(20) NOT NULL, invoice_pdf VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_E00CEDDE67A34946 ON booking (negotiation_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_E00CEDDE4C3A3BB ON booking (payment_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE hotel (id SERIAL NOT NULL, user_id INT NOT NULL, name VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, latitude NUMERIC(10, 7) NOT NULL, longitude NUMERIC(10, 7) NOT NULL, description TEXT DEFAULT NULL, amenities JSON DEFAULT NULL, negotiation_rules JSON DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_3535ED9A76ED395 ON hotel (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE negotiation (id SERIAL NOT NULL, client_id INT NOT NULL, room_type_id INT NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, proposed_price NUMERIC(10, 2) NOT NULL, status VARCHAR(20) NOT NULL, hotel_response TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, responded_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_1798959819EB6921 ON negotiation (client_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_17989598296E3073 ON negotiation (room_type_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE notification (id SERIAL NOT NULL, user_id INT NOT NULL, type VARCHAR(10) NOT NULL, message TEXT NOT NULL, is_read BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, read_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_BF5476CAA76ED395 ON notification (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE payment (id SERIAL NOT NULL, stripe_id VARCHAR(255) NOT NULL, amount NUMERIC(10, 2) NOT NULL, status VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE room_photo (id SERIAL NOT NULL, room_type_id INT NOT NULL, url VARCHAR(255) NOT NULL, alt VARCHAR(255) DEFAULT NULL, display_order INT NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_5E0B25B3296E3073 ON room_photo (room_type_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE room_type (id SERIAL NOT NULL, hotel_id INT NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, capacity INT NOT NULL, base_price NUMERIC(10, 2) NOT NULL, available BOOLEAN NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_EFDABD4D3243BB18 ON room_type (hotel_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE "user" (id SERIAL NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, firstname VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, phone VARCHAR(20) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE67A34946 FOREIGN KEY (negotiation_id) REFERENCES negotiation (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE4C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE hotel ADD CONSTRAINT FK_3535ED9A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE negotiation ADD CONSTRAINT FK_1798959819EB6921 FOREIGN KEY (client_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE negotiation ADD CONSTRAINT FK_17989598296E3073 FOREIGN KEY (room_type_id) REFERENCES room_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE room_photo ADD CONSTRAINT FK_5E0B25B3296E3073 FOREIGN KEY (room_type_id) REFERENCES room_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE room_type ADD CONSTRAINT FK_EFDABD4D3243BB18 FOREIGN KEY (hotel_id) REFERENCES hotel (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking DROP CONSTRAINT FK_E00CEDDE67A34946
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE booking DROP CONSTRAINT FK_E00CEDDE4C3A3BB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE hotel DROP CONSTRAINT FK_3535ED9A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE negotiation DROP CONSTRAINT FK_1798959819EB6921
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE negotiation DROP CONSTRAINT FK_17989598296E3073
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification DROP CONSTRAINT FK_BF5476CAA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE room_photo DROP CONSTRAINT FK_5E0B25B3296E3073
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE room_type DROP CONSTRAINT FK_EFDABD4D3243BB18
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE booking
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE hotel
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE negotiation
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE notification
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE payment
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE room_photo
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE room_type
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE "user"
        SQL);
    }
}
