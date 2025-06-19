<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250619061244 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE template DROP CONSTRAINT fk_97601f83a76ed395
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_97601f83a76ed395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE template RENAME COLUMN user_id TO author_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE template ADD CONSTRAINT FK_97601F83F675F31B FOREIGN KEY (author_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_97601F83F675F31B ON template (author_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE template DROP CONSTRAINT FK_97601F83F675F31B
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_97601F83F675F31B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE template RENAME COLUMN author_id TO user_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE template ADD CONSTRAINT fk_97601f83a76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_97601f83a76ed395 ON template (user_id)
        SQL);
    }
}
