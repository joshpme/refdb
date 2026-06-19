<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260619000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create initial database schema';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE lookup (id INT AUTO_INCREMENT NOT NULL, doi VARCHAR(255) NOT NULL, reference VARCHAR(1000) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE conference (id INT AUTO_INCREMENT NOT NULL, event_id INT DEFAULT NULL, name VARCHAR(4000) DEFAULT NULL, code VARCHAR(255) NOT NULL, conference_start DATE DEFAULT NULL, conference_end DATE DEFAULT NULL, year VARCHAR(255) DEFAULT NULL, series VARCHAR(150) DEFAULT NULL, series_number INT DEFAULT NULL, issn VARCHAR(8) DEFAULT NULL, isbn VARCHAR(13) DEFAULT NULL, pub_month INT DEFAULT NULL, pub_year INT DEFAULT NULL, doi_code VARCHAR(255) DEFAULT NULL, use_doi TINYINT(1) DEFAULT NULL, base_url VARCHAR(1000) DEFAULT NULL, location VARCHAR(2000) NOT NULL, is_published TINYINT(1) DEFAULT NULL, import_url VARCHAR(2000) DEFAULT NULL, INDEX conference_code_idx (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE lookup_meta (id INT AUTO_INCREMENT NOT NULL, doi VARCHAR(255) NOT NULL, item_type VARCHAR(255) NOT NULL, journal_name VARCHAR(1000) DEFAULT NULL, publisher VARCHAR(255) DEFAULT NULL, event_name VARCHAR(1000) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE author (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, INDEX author_search_idx (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE author_reference (author_id INT NOT NULL, reference_id INT NOT NULL, INDEX IDX_15479E3AF675F31B (author_id), INDEX IDX_15479E3A1645DEA9 (reference_id), PRIMARY KEY(author_id, reference_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE fos_user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, enabled TINYINT(1) NOT NULL, notifications TINYINT(1) DEFAULT 0 NOT NULL, UNIQUE INDEX UNIQ_957A6479F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE favourite (id INT AUTO_INCREMENT NOT NULL, reference_id INT NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_62A2CA191645DEA9 (reference_id), INDEX IDX_62A2CA19A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE feedback (id INT AUTO_INCREMENT NOT NULL, reference_id INT DEFAULT NULL, feedback LONGTEXT DEFAULT NULL, title VARCHAR(500) DEFAULT NULL, author VARCHAR(500) DEFAULT NULL, position VARCHAR(255) DEFAULT NULL, custom_doi VARCHAR(100) DEFAULT NULL, email VARCHAR(100) DEFAULT NULL, resolved TINYINT(1) DEFAULT 0 NOT NULL, INDEX IDX_D22944581645DEA9 (reference_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE journal (id INT AUTO_INCREMENT NOT NULL, short_canonical VARCHAR(255) NOT NULL, name_short VARCHAR(255) NOT NULL, name_long VARCHAR(400) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reference (id INT AUTO_INCREMENT NOT NULL, conference_id INT DEFAULT NULL, hits INT DEFAULT NULL, original_authors LONGTEXT DEFAULT NULL, contribution_id INT DEFAULT NULL, paper_id VARCHAR(255) DEFAULT NULL, title VARCHAR(500) DEFAULT NULL, author VARCHAR(500) DEFAULT NULL, position VARCHAR(255) DEFAULT NULL, in_proc TINYINT(1) DEFAULT NULL, et_al TINYINT(1) DEFAULT NULL, cache VARCHAR(600) DEFAULT NULL, doi_verified TINYINT(1) DEFAULT NULL, custom_doi VARCHAR(100) DEFAULT NULL, paper_url VARCHAR(200) DEFAULT NULL, confirmed_in_proc TINYINT(1) DEFAULT NULL, INDEX IDX_AEA34913604B8382 (conference_id), INDEX reference_paper_idx (cache), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE author_reference ADD CONSTRAINT FK_15479E3AF675F31B FOREIGN KEY (author_id) REFERENCES author (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE author_reference ADD CONSTRAINT FK_15479E3A1645DEA9 FOREIGN KEY (reference_id) REFERENCES reference (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE favourite ADD CONSTRAINT FK_62A2CA191645DEA9 FOREIGN KEY (reference_id) REFERENCES reference (id)');
        $this->addSql('ALTER TABLE favourite ADD CONSTRAINT FK_62A2CA19A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id)');
        $this->addSql('ALTER TABLE feedback ADD CONSTRAINT FK_D22944581645DEA9 FOREIGN KEY (reference_id) REFERENCES reference (id)');
        $this->addSql('ALTER TABLE reference ADD CONSTRAINT FK_AEA34913604B8382 FOREIGN KEY (conference_id) REFERENCES conference (id)');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE author_reference DROP FOREIGN KEY FK_15479E3AF675F31B');
        $this->addSql('ALTER TABLE author_reference DROP FOREIGN KEY FK_15479E3A1645DEA9');
        $this->addSql('ALTER TABLE favourite DROP FOREIGN KEY FK_62A2CA191645DEA9');
        $this->addSql('ALTER TABLE favourite DROP FOREIGN KEY FK_62A2CA19A76ED395');
        $this->addSql('ALTER TABLE feedback DROP FOREIGN KEY FK_D22944581645DEA9');
        $this->addSql('ALTER TABLE reference DROP FOREIGN KEY FK_AEA34913604B8382');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE lookup');
        $this->addSql('DROP TABLE conference');
        $this->addSql('DROP TABLE lookup_meta');
        $this->addSql('DROP TABLE author');
        $this->addSql('DROP TABLE author_reference');
        $this->addSql('DROP TABLE fos_user');
        $this->addSql('DROP TABLE favourite');
        $this->addSql('DROP TABLE feedback');
        $this->addSql('DROP TABLE journal');
        $this->addSql('DROP TABLE reference');
    }
}
