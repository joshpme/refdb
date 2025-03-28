<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250328230558 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add event ID to conference table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE conference ADD event_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE conference DROP event_id');
    }
}
