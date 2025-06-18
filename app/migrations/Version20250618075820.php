<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250618075820 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE template_tag (template_id INT NOT NULL, tag_id INT NOT NULL, PRIMARY KEY(template_id, tag_id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_ADE23EA15DA0FB8 ON template_tag (template_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_ADE23EA1BAD26311 ON template_tag (tag_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE template_tag ADD CONSTRAINT FK_ADE23EA15DA0FB8 FOREIGN KEY (template_id) REFERENCES template (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE template_tag ADD CONSTRAINT FK_ADE23EA1BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE template_tag DROP CONSTRAINT FK_ADE23EA15DA0FB8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE template_tag DROP CONSTRAINT FK_ADE23EA1BAD26311
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE template_tag
        SQL);
    }
}
