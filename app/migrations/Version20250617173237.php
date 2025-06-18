<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250617173237 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP SEQUENCE templates_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE templates DROP CONSTRAINT fk_6f287d8ea76ed395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE templates DROP CONSTRAINT fk_6f287d8e1f55203d
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE template_access_users DROP CONSTRAINT fk_b7c285ef5da0fb8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE template_access_users DROP CONSTRAINT fk_b7c285efa76ed395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE templates
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE template_access_users
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE templates_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE templates (id SERIAL NOT NULL, user_id INT NOT NULL, topic_id INT NOT NULL, title VARCHAR(255) NOT NULL, description TEXT NOT NULL, image_url VARCHAR(1024) DEFAULT NULL, is_public BOOLEAN NOT NULL, tags JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_6f287d8e1f55203d ON templates (topic_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_6f287d8ea76ed395 ON templates (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN templates.created_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN templates.updated_at IS '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE template_access_users (template_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(template_id, user_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_b7c285efa76ed395 ON template_access_users (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_b7c285ef5da0fb8 ON template_access_users (template_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE templates ADD CONSTRAINT fk_6f287d8ea76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE templates ADD CONSTRAINT fk_6f287d8e1f55203d FOREIGN KEY (topic_id) REFERENCES topics (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE template_access_users ADD CONSTRAINT fk_b7c285ef5da0fb8 FOREIGN KEY (template_id) REFERENCES templates (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE template_access_users ADD CONSTRAINT fk_b7c285efa76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }
}
