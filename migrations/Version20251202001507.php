<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Column;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251202001507 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        // 1) Renommer le prix d’achat si la colonne price existe encore
        $orderItemColumns = array_change_key_case($schemaManager->listTableColumns('order_item'), CASE_LOWER);
        if (isset($orderItemColumns['price'])) {
            $this->addSql('ALTER TABLE order_item CHANGE price product_price DOUBLE PRECISION NOT NULL');
        } elseif (!isset($orderItemColumns['product_price'])) {
            // Filet de sécurité : créer la colonne si aucune n’existe (valeur 0 par défaut)
            $this->addSql('ALTER TABLE order_item ADD product_price DOUBLE PRECISION NOT NULL DEFAULT 0');
        }

        // 2) Ajouter les champs utilisateur
        $userColumns = array_change_key_case($schemaManager->listTableColumns('user'), CASE_LOWER);
        if (!isset($userColumns['first_name'])) {
            $this->addSql('ALTER TABLE user ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) DEFAULT NULL');
        }
        if (!isset($userColumns['last_name'])) {
            $this->addSql('ALTER TABLE user ADD COLUMN IF NOT EXISTS last_name VARCHAR(100) DEFAULT NULL');
        }

        // 3) Préparer la colonne reference sur orders (nullable le temps du remplissage) si absente
        $ordersColumns = array_change_key_case($schemaManager->listTableColumns('orders'), CASE_LOWER);
        $hasReference = isset($ordersColumns['reference']);
        if (!$hasReference) {
            $this->addSql('ALTER TABLE orders ADD COLUMN IF NOT EXISTS reference VARCHAR(100) DEFAULT NULL');
        }

        // Normaliser les statuts existants pour l’enum (valeurs attendues)
        $allowed = [
            'en préparation',
            'expédiée',
            'livrée',
            'annulée',
        ];
        $inClause = "'" . implode("','", $allowed) . "'";
        $this->addSql("UPDATE orders SET status = 'en préparation' WHERE status IS NULL OR status NOT IN ({$inClause})");

        // Remplir les références existantes avec une valeur unique
        $ids = $this->connection->fetchFirstColumn('SELECT id FROM orders');
        foreach ($ids as $id) {
            $ref = 'CMD-' . $id . '-' . substr(uniqid('', true), -6);
            $this->addSql('UPDATE orders SET reference = ? WHERE id = ?', [$ref, $id]);
        }

        // Rendre reference NOT NULL et unique
        $this->addSql('ALTER TABLE orders MODIFY reference VARCHAR(100) NOT NULL');

        // Créer l’index unique si absent
        $ordersIndexes = array_change_key_case($schemaManager->listTableIndexes('orders'), CASE_LOWER);
        if (!array_key_exists(strtolower('UNIQ_E52FFDEEAEA34913'), $ordersIndexes)) {
            $this->addSql('CREATE UNIQUE INDEX UNIQ_E52FFDEEAEA34913 ON orders (reference)');
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();
        $orderItemColumns = array_change_key_case($schemaManager->listTableColumns('order_item'), CASE_LOWER);

        if (isset($orderItemColumns['product_price']) && !isset($orderItemColumns['price'])) {
            $this->addSql('ALTER TABLE order_item CHANGE product_price price DOUBLE PRECISION NOT NULL');
        }

        $userColumns = array_change_key_case($schemaManager->listTableColumns('user'), CASE_LOWER);
        if (isset($userColumns['first_name'])) {
            $this->addSql('ALTER TABLE user DROP first_name');
        }
        if (isset($userColumns['last_name'])) {
            $this->addSql('ALTER TABLE user DROP last_name');
        }
        $this->addSql('DROP INDEX UNIQ_E52FFDEEAEA34913 ON orders');
        $this->addSql('ALTER TABLE orders DROP reference');
    }
}
