# Taam Bledi – Préservation des variétés agricoles tunisiennes

## Présentation

Ta3m Bledi est une plateforme web du projet Technologies Web à Esprit, en Tunisie. Elle permet de découvrir, préserver, cultiver et échanger des variétés agricoles traditionnelles tunisiennes.

## Fonctionnalités

- Comptes utilisateurs et badges
- Terrains et récoltes
- Plantation et suivi des cultures
- Musée des variétés, recommandations et ateliers

## Technologies

- PHP, MySQL et Apache (XAMPP)
- HTML, CSS, JavaScript et Bootstrap

## Architecture MVC

Le projet utilise une seule application et ses dossiers existants :

- Controller/ : contrôleurs des modules, dont Utilisateur, Terrain, Récolte, Badge, Plantation et Suivi
- Model/ : modèles partagés et modèles Plantation/Suivi
- View/FrontOffice/ : pages utilisateur et templates Front Office
- View/BackOffice/ : pages d’administration et template Back Office
- SQL/ : scripts SQL des modules
- index.php : routes du module Plantation et Suivi

Plantation et Suivi réutilisent les sessions de connexion du projet, Controller/AuthGuard.php et les rôles ADMIN et AGRICULTEUR. Un agriculteur ne voit et ne modifie que ses plantations vérifiées et leurs suivis. Les fonctions d’administration sont réservées au rôle ADMIN. Le CRUD Utilisateur existant reste dans le Back Office.

## Installation locale

1. Placer le dépôt dans le dossier htdocs de XAMPP.
2. Démarrer Apache et MySQL.
3. Créer/configurer la base Taam Bledi selon les scripts SQL principaux du dépôt.
4. Exécuter SQL/plantation_suivi.sql après la création de la table utilisateur du projet principal. Le script ajoute les tables, colonnes et relations du module sans supprimer ni migrer de données.
5. Ouvrir la page d’accueil à l’adresse http://localhost/ta3m-bledi-project/View/FrontOffice/index.html.

Les routes du module sont disponibles depuis les menus après connexion. La liste du CRUD Utilisateur est accessible à View/BackOffice/listeUtilisateurs.php.

## Contributeurs

Yasmine Neffeti, Yomna Ghannem, Lemis Zmander, Meriam Guerrida, Youssef Trabelsi et Belhassen Ghaleb.

Projet Technologies Web – Esprit, Tunisie.