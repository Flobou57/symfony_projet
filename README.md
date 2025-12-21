# SkinMarket — Symfony 7.3

Prerequis : PHP >= 8.2, Composer, MySQL.

## Installation rapide
1. Installer les dépendances PHP :
   ```
   composer install
   ```
2. Configurer `.env.local` (exemple MySQL) :
   ```
   DATABASE_URL="mysql://root:root@127.0.0.1:3306/skinmarket?serverVersion=8.0&charset=utf8mb4"
   ```
3. Créer la base + migrations :
   ```
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```
4. Charger les fixtures complètes (catégories, statuts, users, adresses, produits CS:GO, commandes) :
   ```
   php bin/console doctrine:fixtures:load
   ```
5. Compiler les assets (importmap/AssetMapper) :
   ```
   php bin/console asset-map:compile
   ```
6. Démarrer le serveur :
   ```
   symfony server:start
   ```
   (ou Apache/MAMP pointant sur `public/`)

## Comptes de test
- Administrateur : `admin@skinmarket.test` / `Admin123!`
- Client : `player@skinmarket.test` / `Player123!`

## Tests
Tests unitaires sans BDD (ex. `CartServiceTest`) :
```
php bin/phpunit
```

## Fonctionnalités
- Auth (ROLE_ADMIN / ROLE_USER)
- Boutique avec filtres (catégorie, statut, recherche live/autocomplete) + détail produit
- Panier dynamique (Stimulus) : ajout/retrait/quantité, total live, checkout, MAJ stock
- Statuts produits synchronisés au stock, statuts commandes (enum)
- Dashboard admin (ratios, dernières commandes, ventes)
- Backoffice produits/catégories/utilisateurs/commandes
- Traductions FR/EN (nav, pages, flashs)
- Profil + adresses
- Fixtures complètes thème CS:GO (produits, commandes, utilisateurs, adresses, statuts)
