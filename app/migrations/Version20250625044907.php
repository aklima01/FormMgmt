<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250625044907 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE "like" (id SERIAL NOT NULL, user_id INT NOT NULL, template_id INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_AC6340B3A76ED395 ON "like" (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_AC6340B35DA0FB8 ON "like" (template_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "like" ADD CONSTRAINT FK_AC6340B3A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "like" ADD CONSTRAINT FK_AC6340B35DA0FB8 FOREIGN KEY (template_id) REFERENCES template (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "like" DROP CONSTRAINT FK_AC6340B3A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "like" DROP CONSTRAINT FK_AC6340B35DA0FB8
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE "like"
        SQL);
    }
}
