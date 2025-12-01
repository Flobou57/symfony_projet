<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251201164933 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des colonnes status, created_at et updated_at dans la table orders avec valeurs par défaut';
    }

    public function up(Schema $schema): void
    {
        // ✅ Ajout de colonnes avec valeurs par défaut pour éviter les erreurs SQL
        $this->addSql("
            ALTER TABLE orders 
            ADD status VARCHAR(50) NOT NULL DEFAULT 'en attente',
            ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '(DC2Type:datetime_immutable)',
            ADD updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders DROP status, DROP created_at, DROP updated_at');
    }
}
