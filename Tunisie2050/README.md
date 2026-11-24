<<<<<<< HEAD
# Taam Bledi – Préservation des variétés agricoles tunisiennes

## Présentation

Ce projet a été développé dans le cadre du **Projet Technologies Web** à **l’École Supérieure Privée d’Ingénierie et de Technologies (Esprit)** – Tunisie, durant l’année universitaire 2026–2027.

**Ta3m Bledi** est une plateforme web permettant de découvrir, préserver, cultiver et échanger des variétés agricoles traditionnelles tunisiennes menacées par le changement climatique.

## Fonctionnalités

- Comptes utilisateurs et badges
- Musée des variétés disparues et témoignages
- Mes plantations&#x20;
- Tounes 2050&#x20;
- Échange de récoltes
- Ateliers&#x20;

## Technologies utilisées

### Frontend

- HTML5, CSS, JavaScript


### Backend

- PHP&#x20;
- MySQL
- XAMPP / Apache

## Architecture

Le projet suit le modèle **MVC (Modèle – Vue – Contrôleur)** :

- `Controllers/` : gèrent les requêtes et choisissent la vue à afficher
- `Models/` : contiennent les classes et assurent l'accès aux données
- `Views/` : contiennent les pages du Front Office et du Back Office, avec `includes/` et `partials/`

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
http://localhost/ta3m-bledi-project//index.php
```

## Remerciements

Nous remercions nos tuteurs à **l’École Supérieure Privée d’Ingénierie et de Technologies (Esprit)** pour leur accompagnement et leurs conseils.
=======
# Tunisie2050 – Taam Bledi (Module 2)

Module **Tounes 2050** du projet Web 2A « Taam Bledi » (Esprit School of Engineering) :
explorer comment le climat transformera l'agriculture de chaque région tunisienne
(classes **Ville** et **Projection** du diagramme de classes).

## Fonctionnalités

**Front-Office** (thème vert Taam Bledi)
- Carte Leaflet + liste des régions avec recherche
- Page région : navigation dans le temps (2025 → 2050), graphique température / pluie, cultures menacées
- Page « Cultures menacées » par année
- Explication simulée par IA (service `Model/ExplicationIA.php`, remplaçable par une vraie API)

**Back-Office** (template PlainAdmin)
- Connexion par e-mail + mot de passe, réservée au rôle `admin`
- CRUD **Régions** (Ville), CRUD **Projections**, CRUD **Utilisateurs** (rôles, blocage / déblocage)
- Tableau de bord + réinitialisation des données de démonstration

## Architecture (MVC + intégration du template)

```
Tunisie2050/
├── index.php            point d'entrée (session sécurisée, constantes)
├── routes.php           routage  index.php?page=<clé>
├── helpers.php          e(), h(), url(), admin_url(), csrf_*()
├── Controller/          Controller, AdminController (sécurité), Auth, Users, Regions, Projections, Dashboard, Ville
├── Model/               Ville, Projection, Utilisateur, ExplicationIA, Store (JSON), seed/
├── View/
│   ├── FrontOffice/     layout.php + includes/ (head, header, footer, scripts) + pages
│   └── BackOffice/      layout.php + partials/ (head, sidebar, header, footer, scripts) + pages/
└── storage/             données JSON (créées automatiquement depuis Model/seed/)
```

Chaque page est du HTML pur : `Controller::render()` capture la vue dans `$content`,
puis `layout.php` assemble les blocs communs (workshop *Template Integration*).
Le lien actif du menu est calculé avec `$currentPage`.

## Sécurité (workshop Sessions & Sécurité)

- Mots de passe hachés avec `password_hash()` / vérifiés avec `password_verify()`
- `session_regenerate_id(true)` à la connexion, cookie `HttpOnly` + `SameSite=Lax`, mode strict
- Déconnexion : session détruite et cookie supprimé (POST + jeton CSRF)
- Protection des routes : chaque contrôleur du back-office appelle `requireLogin()` ; le compte est relu
  à chaque requête (un admin bloqué ou rétrogradé perd l'accès immédiatement) ; expiration après 30 min d'inactivité
- Rôles : `admin` / `utilisateur` ; un simple utilisateur est refusé au back-office
- Règles métier : on ne peut pas se supprimer, se bloquer ou se rétrograder soi-même ; il reste toujours un admin actif
- Jeton **CSRF** sur tous les formulaires POST
- Anti **XSS** : toute sortie passe par `e()` / `h()` ; JSON injecté dans les pages avec `JSON_HEX_*`
- Limitation des tentatives de connexion (5 échecs → blocage 60 s)

## Validation des saisies

- **Côté client** : `View/BackOffice/assets/js/validation.js` (attributs HTML `required`, `minlength`,
  `min`/`max`, `pattern`… sur tous les formulaires `data-validate`)
- **Côté serveur** : revalidation complète dans `Utilisateur::validate()` et dans les `save()` des contrôleurs
  (formats, bornes, listes autorisées, unicité e-mail / nom de région / année par région)

## Installation et exécution

Prérequis : **PHP ≥ 8.1** (aucune base de données : les données sont dans `storage/*.json`).

```bash
cd Tunisie2050
php -S localhost:8000
```

Puis ouvrir <http://localhost:8000/index.php> (ou copier le dossier dans `htdocs` pour XAMPP / `www` pour WAMP
et ouvrir `http://localhost/Tunisie2050/index.php`).
Pour afficher les erreurs PHP en développement : variable d'environnement `APP_DEBUG=1`.

| Page | URL |
|------|-----|
| Front-Office | `index.php?page=tounes` |
| Back-Office | `index.php?page=admin-login` |

### Comptes de test

| Rôle | E-mail | Mot de passe |
|------|--------|--------------|
| admin | `admin@tunisie2050.tn` | `Admin@2050` |
| utilisateur (refusé au back-office) | `user@tunisie2050.tn` | `User@2050` |

Supprimer `storage/*.json` régénère toutes les données depuis `Model/seed/`.

## Intégration avec les autres modules

`Utilisateur` est la classe commune (Module 1). Ici elle est stockée en JSON ; à l'intégration,
remplacer `Model/Utilisateur.php` par le modèle PDO du groupe : `AdminController::requireLogin()`
ne dépend que de `$_SESSION['user_id']` et de `Utilisateur::findById()`.
`Ville.idAdmin` pointe vers l'`idUtilisateur` de l'administrateur qui gère la région.

## Git

Une branche par fonctionnalité, commits réguliers, fusion vers `main` par pull request.
Nom du dépôt : respecter la nomenclature demandée (ex. `2A20-PCPP-2A22-Group-2-...`).
>>>>>>> 8f8c5a5 (Add my project updates*)
