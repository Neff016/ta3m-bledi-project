# Taam Bledi – Préservation des variétés agricoles tunisiennes

## Présentation

Ce projet a été développé dans le cadre du **Projet Technologies Web** à **l’École Supérieure Privée d’Ingénierie et de Technologies (Esprit)** – Tunisie, durant l’année universitaire 2026–2027.

**Ta3m Bledi** est une plateforme web permettant de découvrir, préserver, cultiver et échanger des variétés agricoles traditionnelles tunisiennes menacées par le changement climatique.

## Fonctionnalités

- Comptes utilisateurs et badges
- Musée des variétés disparues et témoignages
- Mes plantations
- Tounes 2050
- Échange de récoltes
- Ateliers

## Technologies utilisées

### Frontend

- HTML5, CSS, JavaScript

### Backend

- PHP
- MySQL
- XAMPP / Apache

## Architecture

Le projet principal suit le modèle **MVC (Modèle – Vue – Contrôleur)** :

- `Controller/` : contrôleurs du projet principal
- `Model/` : modèles du projet principal
- `View/` : vues du Front Office (`View/FrontOffice/`) et du Back Office (`View/BackOffice/`)

Le module Plantation et Suivi possède une structure MVC séparée :

- `controllers/` : contrôleurs Plantation et Suivi
- `models/` : modèles Plantation et Suivi
- `views/` : vues Front Office et Back Office du module
- `config/` et `database/` : configuration PDO et scripts SQL du module

## Module Plantation et Suivi

Le module permet de créer, consulter, modifier et supprimer des plantations et leurs suivis associés, côté Front Office comme côté Back Office. Il comprend ses contrôleurs, modèles, vues et le script SQL d’installation `database/plantation_suivi.sql`.

## Contributeurs

- Yasmine Neffeti
- Yomna Ghannem
- Lemis Zmander
- Meriam Guerrida
- Youssef Trabelsi
- Belhassen Ghaleb

## Contexte académique

Développé à **l’École Supérieure Privée d’Ingénierie et de Technologies (Esprit)** – Tunisie.

Projet Technologies Web | 2026–2027

## Démarrage du projet

1. Cloner le repository :

```bash
git clone https://github.com/Neff016/ta3m-bledi-project.git
```

2. Placer le projet dans le dossier `htdocs` de XAMPP.
3. Démarrer **Apache** et **MySQL** depuis le panneau de contrôle XAMPP.
4. Ouvrir dans le navigateur :

```text
http://localhost/ta3m-bledi-project/index.php
```

## Remerciements

Nous remercions nos tuteurs à **l’École Supérieure Privée d’Ingénierie et de Technologies (Esprit)** pour leur accompagnement et leurs conseils.
