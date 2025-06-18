<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250618105835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE template_user_access (template_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(template_id, user_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_68C2240D5DA0FB8 ON template_user_access (template_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_68C2240DA76ED395 ON template_user_access (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE template_user_access ADD CONSTRAINT FK_68C2240D5DA0FB8 FOREIGN KEY (template_id) REFERENCES template (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE template_user_access ADD CONSTRAINT FK_68C2240DA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE template ADD access TEXT DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE template_user_access DROP CONSTRAINT FK_68C2240D5DA0FB8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE template_user_access DROP CONSTRAINT FK_68C2240DA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE template_user_access
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE template DROP access
        SQL);
    }
}
