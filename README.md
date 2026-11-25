# Taam Bledi – Préservation des variétés agricoles tunisiennes

## Présentation

Ce projet a été développé dans le cadre du **Projet Technologies Web (2A)** à **l'École Supérieure Privée d'Ingénierie et de Technologies (Esprit)** – Tunisie, année universitaire 2026–2027.

**Taam Bledi** est une plateforme web permettant de découvrir, préserver, cultiver et échanger des variétés agricoles traditionnelles tunisiennes menacées par le changement climatique.

## Fonctionnalités

| Module | Front Office (agriculteur) | Back Office (admin) |
|---|---|---|
| **Utilisateurs et Badges** | Inscription, connexion / déconnexion, profil (infos + photo), changement de mot de passe, mot de passe oublié, mes badges | CRUD utilisateurs, rôles, blocage / déblocage, CRUD badges, attribution de badges |
| **Terrains et Récoltes** | Mes terrains (CRUD), détails d'un terrain, mes récoltes (CRUD), recommandation, consommation d'eau | Liste de tous les terrains et récoltes (recherche, suppression), statistiques |
| **Musée des variétés** | Catalogue des variétés, adoption de graines, témoignages | Gestion des variétés et témoignages |
| Tounes 2050, Plantation et suivi, Échange de récoltes, Ateliers | *(autres membres de l'équipe)* | |

## Technologies

- **Front-end** : HTML5, CSS3, JavaScript, Bootstrap 5 (template AgriCulture), Bootstrap 4 (template SB Admin 2)
- **Back-end** : PHP 8 (PDO), MySQL / MariaDB
- **Environnement** : XAMPP (Apache + MySQL), Git / GitHub

## Architecture (MVC)

```
ta3m-bledi-project/
├── Config/
│   └── config.php            ← connexion PDO à la base (classe config)
├── Model/                    ← classes métier (Utilisateur, Badge, Attribution, Terrain, Recolte)
├── Controller/               ← requêtes SQL préparées + contrôle de saisie côté serveur
│   ├── AuthGuard.php         ← sessions, contrôle d'accès (requireLogin / requireAdmin), e() = htmlspecialchars
│   ├── UploadHelper.php      ← upload d'images sécurisé
│   ├── UtilisateurController.php, BadgeController.php
│   └── TerrainController.php, RecolteController.php
├── View/
│   ├── FrontOffice/          ← pages publiques et espace agriculteur (template AgriCulture)
│   │   ├── partials/         ← formulaires réutilisés (terrain, récolte)
│   │   └── assets/           ← css, js (validation-*.js), images
│   └── BackOffice/           ← administration (template SB Admin 2)
└── SQL/                      ← scripts de création de la base
```

## Installation

1. **Cloner** le dépôt dans le dossier `htdocs` de XAMPP :

   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/Neff016/ta3m-bledi-project.git
   ```

2. **Démarrer** Apache et MySQL dans le panneau de contrôle XAMPP.

3. **Créer la base de données** : ouvrir http://localhost/phpmyadmin → onglet **SQL**, puis exécuter **dans cet ordre** :
   1. `SQL/ta3m_bledi_utilisateurs_badges.sql`
   2. `SQL/ta3m_bledi_terrains_recoltes.sql`
   3. `SQL/ta3m_bledi_terrains_avance.sql` (carte, catalogue des cultures, dépenses, QR, badges automatiques)

   Base déjà créée avant la mise à jour sécurité ? Exécuter aussi `SQL/mise-a-jour-securite.sql`.

   Ancienne version de `ta3m_bledi_terrains_avance.sql` déjà importée (avec carnet, diagnostic et statuts) ?
   Exécuter aussi `SQL/retrait-doublons-plantation.sql`.

   La base `ta3m_bledi` est créée avec des données de test.

## Configuration

Les paramètres de connexion sont dans `Config/config.php` (par défaut XAMPP) :

| Paramètre | Valeur |
|---|---|
| Hôte | `localhost` |
| Base | `ta3m_bledi` |
| Utilisateur | `root` |
| Mot de passe | *(vide)* |

Si le dossier du projet n'a pas le nom `ta3m-bledi-project`, modifier aussi `LOGIN_PAGE` et `HOME_PAGE` dans `Controller/AuthGuard.php`.

## Exécution

- **Front Office** : http://localhost/ta3m-bledi-project/View/FrontOffice/index.html
- **Connexion** : http://localhost/ta3m-bledi-project/View/FrontOffice/connexion.php
- **Back Office** : se connecter avec un compte admin (redirection automatique)

### Comptes de test

| Email | Mot de passe | Rôle |
|---|---|---|
| admin@taambledi.tn | Admin@2026 | Admin |
| ahmed@taambledi.tn | Ahmed@2026 | Agriculteur |
| salma@taambledi.tn | Salma@2026 | Agriculteur |

## Module Terrains et Récoltes (fonctions avancées)

| Fonction | Où | Comment |
|---|---|---|
| Tableau de bord | `tableauDeBord.php` | production par mois, comparaison année N / N-1, répartition par culture, rendement (t/ha) par terrain — graphiques SVG faits maison (marchent hors ligne) |
| Carte interactive | ajout / modification d'un terrain | Leaflet + OpenStreetMap / satellite Esri : placer un point ou **dessiner la parcelle** (surface calculée automatiquement, recalculée par le serveur) |
| Météo et alertes | onglet « Météo et eau » | Open-Meteo (sans clé) : 7 jours, alertes gel, chaleur, forte pluie, vent fort |
| Besoin en eau réel | `consommationEau.php` | méthode FAO-56 : ETc = ET0 × kc, pluie utile, volume en m³, conseil d'irrigation |
| Recommandation | `recommandation.php` | score sur 100 (sol, saison, région, eau, historique, rotation) avec les raisons |
| Rotation des cultures | formulaire récolte | avertissement si la même culture est replantée sur le même terrain |
| Quantités en kg | formulaire récolte | kg, quintal ou tonne, converti en kg pour les totaux et le rendement |
| Culture liée au Musée | formulaire récolte | autocomplétion : catalogue + variétés du Musée (base `taam_bledi`) |
| Saisie vocale | formulaire récolte | derja, arabe ou français (« zid récolte 200 kilo tmatem terrain Nabeul ») |
| QR code de traçabilité | `etiquette.php`, `tracabilite.php` | étiquette imprimable ; page publique « De la ferme à la table » (sans données privées) |
| Satellite (NDVI) | onglet « Satellite » | Agromonitoring : santé de la parcelle dessinée sur 6 mois |
| Prévision de récolte | tableau de bord | régression linéaire (moindres carrés) par culture et par terrain |
| Rentabilité | onglet « Rentabilité » | dépenses saisies − (quantités × prix du marché géré par l'admin) |
| Comparaison régionale | tableau de bord | rendement comparé aux autres agriculteurs de la wilaya (anonyme) |
| Fiche PDF | `ficheTerrain.php` | impression du navigateur → « Enregistrer en PDF » |
| Badges automatiques | — | Premier terrain, Première récolte, Une tonne récoltée, Comptable rigoureux (10 dépenses), Producteur traçable |
| Hors ligne (PWA) | `sw.js`, `manifest.webmanifest` | installable ; une récolte saisie sans Internet est gardée (IndexedDB) puis envoyée au retour du réseau |
| Arabe / français | bouton « ع » du menu | traduction (`View/FrontOffice/lang/ar.php`) et mise en page de droite à gauche |
| Back Office | `listeTerrains.php`, `listeRecoltes.php`, `carteTerrains.php`, `listeCultures.php` | filtres, tri par colonne, pagination, export CSV, carte de tous les terrains, prix du marché |

**Clé d'API (facultative)** : copier `Config/api.example.php` en `Config/api.php` et coller la clé Agromonitoring
(https://home.agromonitoring.com/). Sans clé, l'onglet Satellite affiche un message ; tout le reste fonctionne.
`Config/api.php` est dans `.gitignore`.

**Partage avec le module « Plantation et suivi »** : ici, une récolte est toujours **terminée** (quantité obligatoire).
Les plantations en cours, leur suivi (arrosage, observations) et le diagnostic des maladies sont dans le module
« Plantation et suivi », pour ne pas faire le même travail deux fois.

La carte, la météo et le satellite ont besoin d'Internet. Les clés d'accès, la saisie vocale et le mode
hors ligne marchent sur `http://localhost` (ou en HTTPS).

## Page profil

- En-tête avec photo (ou initiales), rôle, statut, date d'inscription et contact
- Chiffres clés : terrains, superficie totale, récoltes, badges et points
- Barre de **complétion du profil** (photo, téléphone, 1er terrain, 1re récolte, 1er badge)
- Onglets : **Aperçu** (derniers badges et récoltes), **Modifier le profil** (aperçu de la photo avant envoi, retirer la photo), **Sécurité** (changement du mot de passe avec indicateur de force, date de la connexion précédente), **Zone sensible** (supprimer son compte avec mot de passe + mot « SUPPRIMER »)

## Connexion sans mot de passe

- **Clé d'accès (passkey / WebAuthn)** : Face ID, empreinte digitale, Windows Hello ou PIN de l'appareil.
  On l'ajoute dans *Mon profil → Sécurité*, puis on clique « Face ID / empreinte » sur la page de connexion.
  Le serveur garde seulement la clé **publique** (table `passkey`) et vérifie la signature d'un défi aléatoire
  (`Controller/PasskeyController.php`, sans bibliothèque). Fonctionne sur `localhost` ou en HTTPS.
- **Code par email** : `ConnexionCode.php` envoie un code à 6 chiffres (valable 15 min, 5 essais, à usage unique).
- Le code par **SMS** n'est pas inclus : il demande un service payant (ex. Twilio).

## Badges et points

La page *Mes badges* explique comment gagner des points : niveau actuel (Graine → Gardien de la terre),
les 3 étapes, le prochain badge conseillé et le tableau « badge → comment l'obtenir → points ».

## Envoi des emails (mot de passe oublié)

Le code est envoyé avec Gmail (SMTP, sans bibliothèque externe : `Controller/MailHelper.php`).

1. Activez la validation en 2 étapes sur votre compte Google.
2. Créez un **mot de passe d'application** : https://myaccount.google.com/apppasswords
3. Copiez `Config/mail.example.php` en `Config/mail.php` et remplissez l'adresse Gmail et le mot de passe d'application.

`Config/mail.php` contient un secret : il est dans `.gitignore` et ne doit jamais être envoyé sur GitHub.

## Sécurité

- Mots de passe hachés (`password_hash` / `password_verify`)
- Requêtes préparées PDO (protection contre l'injection SQL)
- Échappement de l'affichage avec `htmlspecialchars` (protection XSS)
- Sessions : `session_regenerate_id` à la connexion, pages protégées par `requireLogin()` / `requireAdmin()`
- **Session unique** : un jeton de session est enregistré (haché) dans la base ; si le compte s'ouvre sur un autre navigateur, l'ancienne session est fermée automatiquement (« Session incorrecte »)
- **Déconnexion automatique** après 30 minutes d'inactivité, ou si le compte est bloqué ou supprimé
- **Jeton CSRF** dans chaque formulaire POST, vérifié automatiquement : sans jeton valide, la requête est refusée (403)
- **Compte supprimé** par l'admin : il ne peut plus se connecter (« Ce compte a été supprimé ») et ses données sont cachées ; le compte va dans la **corbeille** et l'admin peut le **restaurer** (bouton « Annuler la suppression » ou « Restaurer »)
- **Mot de passe oublié par code** à 6 chiffres **envoyé par email** (Gmail SMTP) : haché en base, valable 15 min, 5 essais maximum, renvoi possible après 60 s
- Un agriculteur ne voit et ne modifie que ses propres terrains et récoltes
- Suppressions uniquement en POST avec confirmation
- Upload d'images : vérification du vrai type de fichier, taille limitée, nom aléatoire
- Contrôle de saisie en **JavaScript** (client) **et en PHP** (serveur) sur tous les formulaires, sans validation HTML5

## Organisation Git

- `main` : version stable, intégrée
- Une branche par module : `utilisateurs-et-badges`, `terrains-recoltes`, …
- Intégration par **Pull Request** vers `main`
- Suivi des tâches : **GitHub Project Board** (To Do / In Progress / Done)

## Contributeurs

- Yasmine Neffeti
- Yomna Ghannem (modules Utilisateurs et Badges, Terrains et Récoltes)
- Lemis Zmander
- Meriam Guerrida
- Youssef Trabelsi
- Belhassen Ghaleb

## Remerciements

Nous remercions nos tuteurs à **Esprit** pour leur accompagnement et leurs conseils.
