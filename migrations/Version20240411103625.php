<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240411103625 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE search_queries');
        $this->addSql('ALTER TABLE feedback ADD title VARCHAR(500) DEFAULT NULL, ADD author VARCHAR(500) DEFAULT NULL, ADD position VARCHAR(255) DEFAULT NULL, ADD custom_doi VARCHAR(100) DEFAULT NULL, ADD resolved TINYINT(1) DEFAULT 0 NOT NULL, CHANGE feedback feedback LONGTEXT DEFAULT NULL, CHANGE email email VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE fos_user ADD notifications TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE lookup_meta ADD publisher VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE search_queries (id INT AUTO_INCREMENT NOT NULL, paper_id VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, title VARCHAR(4000) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, conference VARCHAR(2000) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, location VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, searchby_date VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE feedback DROP title, DROP author, DROP position, DROP custom_doi, DROP resolved, CHANGE feedback feedback LONGTEXT NOT NULL, CHANGE email email VARCHAR(1000) DEFAULT NULL');
        $this->addSql('ALTER TABLE fos_user DROP notifications');
        $this->addSql('ALTER TABLE lookup_meta DROP publisher');
    }
}
