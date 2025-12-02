<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251202140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Supprime la colonne fullname devenue obsolète';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $columns = array_change_key_case($schemaManager->listTableColumns('user'), CASE_LOWER);

        if (isset($columns['fullname'])) {
            $this->addSql('ALTER TABLE user DROP COLUMN fullname');
        }
    }

    public function down(Schema $schema): void
    {
        // On rétablit la colonne si besoin
        $schemaManager = $this->connection->createSchemaManager();
        $columns = array_change_key_case($schemaManager->listTableColumns('user'), CASE_LOWER);

        if (!isset($columns['fullname'])) {
            $this->addSql('ALTER TABLE user ADD fullname VARCHAR(100) DEFAULT NULL');
        }
    }
}
