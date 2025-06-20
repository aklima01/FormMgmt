<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250620145841 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE answer ADD string_value VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE answer ADD int_value INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE answer ADD bool_value BOOLEAN DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE answer DROP value
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE answer ADD value TEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE answer DROP string_value
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE answer DROP int_value
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE answer DROP bool_value
        SQL);
    }
}
