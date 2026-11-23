# Taam Bledi

Application PHP en MVC pour gérer les plantations et leurs suivis. L’accès est public : aucun compte, connexion ou déconnexion n’est nécessaire.

## Installation locale (XAMPP)

1. Démarrer Apache et MySQL.
2. Créer la base `taam_bledi` dans phpMyAdmin si elle n’existe pas.
3. Sélectionner la base `taam_bledi`, puis exécuter `database/plantation_suivi.sql`.
4. Vérifier les identifiants PDO dans `config/database.php`.
5. Ouvrir `http://localhost/ta3m-bledi-project/index.php`.

Le script SQL installe les tables manquantes et complète le schéma Plantation/Suivi. Il conserve les tables et données déjà présentes. Les associations historiques aux comptes sont facultatives.

## Pages

- Tableau de bord : `http://localhost/ta3m-bledi-project/index.php?action=dashboard`
- Plantations : `http://localhost/ta3m-bledi-project/index.php?action=plantations`
- Suivis : `http://localhost/ta3m-bledi-project/index.php?action=suivis`
- Gestion des plantations : `http://localhost/ta3m-bledi-project/index.php?action=admin_plantations`
- Gestion des suivis : `http://localhost/ta3m-bledi-project/index.php?action=admin_suivis`

## Modules Plantation et Suivi

Le Front Office permet à toute personne de créer, consulter, modifier et supprimer les plantations, ainsi que les suivis liés à chacune. Le Back Office de gestion des plantations et des suivis est également accessible sans compte. Les formulaires gardent une session technique pour la protection CSRF et les messages temporaires, sans authentification utilisateur.

Le routage est centralisé dans `index.php`. `PlantationController` gère le CRUD Plantation ; `SuiviController` gère le CRUD Suivi ; `PlantationModel` et `SuiviModel` regroupent les requêtes PDO préparées. Les vues Front Office et Back Office réutilisent leurs layouts Taam Bledi.

Routes CRUD : `plantations`, `plantation_create`, `plantation_show&id=ID`, `plantation_update&id=ID`, `plantation_delete` ; `suivis&plantation=ID`, `suivi_create&plantation=ID`, `suivi_update&id=ID`, `suivi_delete`. Les pages de gestion sont `admin_plantations`, `admin_plantation_show&id=ID`, `admin_plantation_delete`, `admin_suivis` et `admin_suivi_delete`.
