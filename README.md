# Symfony 7.3 Boilerplate 

Attention : Il vous faut PHP 8.2 pour faire fonctionner ce projet.

## Initialisation de votre IDE

### PHPStorm

1. Ouvrir le projet dans PHPStorm
2. Installer les extensions Twig et Symfony
    - Aller dans File > Settings > Plugins
    - Installer les extensions (Twig, EA Inspection, PHP Annotations, .env files support)

### Visual Studio Code

1. Ouvrir le projet dans Visual Studio Code
2. Installer les extensions pour PHP, Twig et Symfony
    - Aller dans l'onglet Extensions
    - Installer les extensions (whatwedo.twig, TheNouillet.symfony-vscode, DEVSENSE.phptools-vscode, 
    bmewburn.vscode-intelephense-client, zobo.php-intellisense)

## Installation avec Firebase (https://firebase.google.com/)

1. Fork le projet sur votre compte GitHub
2. Importer le projet depuis votre GitHub sur Firebase (https://studio.firebase.google.com/ => Import Repo => Coller le lien de votre repo GitHub)
3. Une fois le projet lancé, dans le terminal, lancez la commande `composer install` pour installer les dépendances du projet.
4. Dans ce même terminal, cliquez sur le bouton `onStart` (ou tapez la commande `symfony server:start`) puis cliquer sur le lien `localhost` ou `http://127.0.0.1:8000` selon ce qui est affiché
5. Pour accéder à la base de données `mysql -u root`, dans un fichier à la racine `.env.local` mettre cette variable d'environnement 
`DATABASE_URL="mysql://root:@127.0.0.1:3306/app?serverVersion=10.11.2-MariaDB&charset=utf8mb4"`
6. Créer la base de données avec la commande `php bin/console doctrine:database:create`

## Installation en local

1. Cloner le projet
2. Installer PHP >= 8.2 et Composer (Sur votre machine utiliser XAMPP pour windows, MAMP pour mac ou LAMP pour linux et bien prendre la version PHP 8.2)
3. Installer les dépendances du projet avec la commande `composer install`
4. Faire un virtual host sur votre serveur local (XAMPP par exemple pour Windows) 
 - Ouvrir le fichier `httpd-vhosts.conf` dans le répertoire `C:\xampp\apache\conf\extra`
    - Ajouter le code suivant à la fin du fichier
    ```
    <VirtualHost *>
        DocumentRoot "C:\Users\votre_username\Documents\iut\symfony_base\public"
        ServerName symfony_base.local
        
        <Directory "C:\Users\votre_username\Documents\iut\symfony_base\public">
            AllowOverride All
            Require all granted
        </Directory>
    </VirtualHost>
    ```
    - Ajouter l'adresse IP de votre machine dans le fichier `C:\Windows\System32\drivers\etc\hosts`
    ```
    127.0.0.1 symfony_base.local
    ```
    - Redémarrer Apache
    - Accéder à l'adresse `symfony_base.local` dans votre navigateur

4. Pour accéder à la base de données `mysql -u root`, dans un fichier à la racine `.env.local` mettre cette variable d'environnement
   `DATABASE_URL="mysql://root:@127.0.0.1:3306/app?serverVersion=10.11.2-MariaDB&charset=utf8mb4"`
5. Créer la base de données avec la commande `php bin/console doctrine:database:create`

## Utilisation

- N'hésitez pas à consulter la documentation de Symfony pour plus d'informations sur l'utilisation du framework : https://symfony.com/doc/current/index.html

- Documentation Firebase : https://firebase.google.com/docs/studio

- Notez comment fonctionne votre projet dans le fichier README.md et mettez à jour ce fichier au fur et à mesure de l'avancement de votre projet pour aider les autres développeurs à comprendre comment fonctionne votre projet.

## Démarrage rapide (base de données + fixtures)

1. Configurer votre `.env.local` (exemple MySQL local) :
   ```
   DATABASE_URL="mysql://root:root@127.0.0.1:3306/skinmarket?serverVersion=8.0&charset=utf8mb4"
   ```
2. Créer la base de données :
   ```
   php bin/console doctrine:database:create
   ```
3. Lancer les migrations :
   ```
   php bin/console doctrine:migrations:migrate
   ```
4. Charger les fixtures complètes (catégories, statuts, utilisateurs, adresses, produits CS:GO, commandes) :
   ```
   php bin/console doctrine:fixtures:load
   ```
5. Démarrer le serveur Symfony :
   ```
   symfony server:start
   ```
   (ou utilisez Apache/MAMP en pointant sur `public/`)

## Comptes de test
- Administrateur : `admin@skinmarket.test` / mot de passe `Admin123!`
- Client : `player@skinmarket.test` / mot de passe `Player123!`

## Fonctionnalités principales
- Authentification (ROLE_ADMIN / ROLE_USER)
- Boutique avec filtres (catégorie, statut, recherche par nom/description) et page détail produit (ajout panier)
- Panier dynamique (Stimulus) avec modification de quantité, vérification de stock, checkout + mise à jour stock
- Statuts produits synchronisés au stock (listener) + statuts commandes (enum)
- Dashboard admin (ratios, dernières commandes, ventes)
- Backoffice produits/catégories/utilisateurs/commandes
- Traductions fr/en (nav, pages, flashs)
- Profil utilisateur avec ajout/suppression d’adresses
- Fixtures complètes thème CS:GO (produits, commandes, utilisateurs, adresses, statuts)
