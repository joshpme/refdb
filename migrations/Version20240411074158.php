<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240411074158 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE journal (id INT AUTO_INCREMENT NOT NULL, name_short VARCHAR(255) NOT NULL, name_long VARCHAR(400) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE lookup (id INT AUTO_INCREMENT NOT NULL, doi VARCHAR(255) NOT NULL, reference VARCHAR(1000) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE lookup_meta (id INT AUTO_INCREMENT NOT NULL, doi VARCHAR(255) NOT NULL, item_type VARCHAR(255) NOT NULL, journal_name VARCHAR(1000) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE journal');
        $this->addSql('DROP TABLE lookup');
        $this->addSql('DROP TABLE lookup_meta');
    }
}
