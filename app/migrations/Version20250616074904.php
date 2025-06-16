<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250616074904 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE templates (id SERIAL NOT NULL, user_id INT NOT NULL, topic_id INT NOT NULL, title VARCHAR(255) NOT NULL, description TEXT NOT NULL, image_url VARCHAR(1024) DEFAULT NULL, is_public BOOLEAN NOT NULL, tags JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_6F287D8EA76ED395 ON templates (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_6F287D8E1F55203D ON templates (topic_id)
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
            CREATE INDEX IDX_B7C285EF5DA0FB8 ON template_access_users (template_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_B7C285EFA76ED395 ON template_access_users (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE topics (id SERIAL NOT NULL, name VARCHAR(100) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_91F646395E237E06 ON topics (name)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE templates ADD CONSTRAINT FK_6F287D8EA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE templates ADD CONSTRAINT FK_6F287D8E1F55203D FOREIGN KEY (topic_id) REFERENCES topics (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE template_access_users ADD CONSTRAINT FK_B7C285EF5DA0FB8 FOREIGN KEY (template_id) REFERENCES templates (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE template_access_users ADD CONSTRAINT FK_B7C285EFA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE templates DROP CONSTRAINT FK_6F287D8EA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE templates DROP CONSTRAINT FK_6F287D8E1F55203D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE template_access_users DROP CONSTRAINT FK_B7C285EF5DA0FB8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE template_access_users DROP CONSTRAINT FK_B7C285EFA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE templates
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE template_access_users
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE topics
        SQL);
    }
}
