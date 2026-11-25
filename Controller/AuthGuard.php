<?php
/*
 * Sécurité commune à toutes les pages PHP du site
 * À inclure tout en haut de chaque page.
 *
 *  1. Session            : démarrage, expiration après inactivité, session unique par compte
 *  2. Protection routes  : requireLogin() / requireAdmin()
 *  3. Jeton CSRF         : chaque formulaire POST doit contenir le jeton secret de la session
 *  4. Outils             : e() (htmlspecialchars), messages flash
 *
 * Variables de session utilisées par tous les modules :
 *   $_SESSION['user_id'], $_SESSION['role'] ('ADMIN' / 'AGRICULTEUR'),
 *   $_SESSION['nom'], $_SESSION['prenom'], $_SESSION['session_token']
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Chemins (à adapter si le dossier du projet ou la page de connexion ont un autre nom)
const LOGIN_PAGE = '/ta3m-bledi-project/View/FrontOffice/connexion.php';
const HOME_PAGE  = '/ta3m-bledi-project/View/FrontOffice/index.html';

// Déconnexion automatique après 30 minutes sans activité
const DUREE_INACTIVITE = 30 * 60;

/* =====================================================================
 *  1) SESSION
 * ===================================================================== */

/** Vide la session et renvoie vers la connexion avec un message. */
function terminerSession(string $message): void
{
    $_SESSION = [];
    session_regenerate_id(true);
    setFlash($message, 'warning');
    header('Location: ' . LOGIN_PAGE);
    exit;
}

/**
 * Vérifie que la session est encore valide :
 *  - pas d'inactivité trop longue
 *  - le compte existe toujours, n'est ni supprimé ni bloqué
 *  - le jeton de session correspond à celui de la base
 *    (sinon : connexion depuis un autre navigateur / appareil → l'ancienne session est fermée)
 */
function verifierSession(): void
{
    // Inactivité
    if (isset($_SESSION['derniere_activite']) && time() - $_SESSION['derniere_activite'] > DUREE_INACTIVITE) {
        terminerSession('Session expirée après 30 minutes d’inactivité. Veuillez vous reconnecter.');
    }
    $_SESSION['derniere_activite'] = time();

    // État du compte et jeton de session (vérifiés dans la base)
    require_once __DIR__ . '/UtilisateurController.php';
    $etat = (new UtilisateurController())->validerSession(
        (int) $_SESSION['user_id'],
        (string) ($_SESSION['session_token'] ?? '')
    );
    if (!$etat['ok']) {
        terminerSession($etat['message']);
    }
}

/* =====================================================================
 *  2) PROTECTION DES ROUTES
 * ===================================================================== */

/**
 * Page réservée aux utilisateurs connectés.
 * Pas de session (ou session invalide) → page de connexion.
 * Retourne l'id de l'utilisateur connecté.
 */
function requireLogin(): int
{
    if (empty($_SESSION['user_id'])) {
        setFlash('Veuillez vous connecter pour accéder à cette page.', 'warning');
        header('Location: ' . LOGIN_PAGE);
        exit;
    }
    verifierSession();
    return (int) $_SESSION['user_id'];
}

/** Page réservée à l'administrateur (Back Office). */
function requireAdmin(): int
{
    $id = requireLogin();
    if (($_SESSION['role'] ?? '') !== 'ADMIN') {
        header('Location: ' . HOME_PAGE);
        exit;
    }
    return $id;
}

/* =====================================================================
 *  3) JETON CSRF (protection des formulaires)
 *  Un jeton secret est créé pour chaque session. Chaque formulaire POST
 *  l'envoie dans un champ caché ; s'il est absent ou faux, la requête est refusée.
 *  → un autre site ne peut pas envoyer de formulaire à la place de l'utilisateur.
 * ===================================================================== */

function jetonCsrf(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Champ caché à mettre dans chaque <form method="POST"> : <?= champCsrf() ?> */
function champCsrf(): string
{
    return '<input type="hidden" name="csrf_token" value="' . jetonCsrf() . '">';
}

/** Appelé automatiquement pour chaque requête POST (voir en bas du fichier). */
function verifierCsrf(): void
{
    $recu = $_POST['csrf_token'] ?? '';
    if (!is_string($recu) || !hash_equals(jetonCsrf(), $recu)) {
        // Requête refusée : page d'erreur 403
        http_response_code(403);
        $retour = e(strtok($_SERVER['REQUEST_URI'], '#'));
        echo '<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><title>Accès refusé</title>'
           . '<style>body{font-family:Arial,sans-serif;background:#f3f6f4;color:#1e2b24;display:flex;'
           . 'align-items:center;justify-content:center;min-height:100vh;margin:0}'
           . '.carte{background:#fff;border-radius:16px;padding:40px;max-width:460px;text-align:center;'
           . 'box-shadow:0 8px 24px rgba(0,0,0,.08)}h1{color:#c0392b;margin-top:0}'
           . 'a{display:inline-block;margin-top:16px;background:#2f5d45;color:#fff;padding:10px 20px;'
           . 'border-radius:10px;text-decoration:none}</style></head><body><div class="carte">'
           . '<h1>403 – Accès refusé</h1>'
           . '<p>Le jeton de sécurité du formulaire est absent, invalide ou expiré.<br>'
           . 'La requête a été bloquée pour protéger votre compte.</p>'
           . '<a href="' . $retour . '">Revenir à la page</a></div></body></html>';
        exit;
    }
}

/* =====================================================================
 *  4) OUTILS
 * ===================================================================== */

/**
 * Affichage sécurisé (protection XSS) : à utiliser pour TOUT ce qui vient de la base ou de l'utilisateur.
 * Exemple : <?= e($terrain['nom']) ?>
 */
function e($valeur): string
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
}

/** Message affiché une seule fois (après ajout / modification / suppression…). */
function setFlash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function getFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/* =====================================================================
 *  OUVERTURE DE SESSION (mot de passe, clé d'accès ou code par email)
 * ===================================================================== */

/**
 * Connecte $user : ferme l'ancienne session, crée le jeton de session unique,
 * retient la date de la connexion précédente. Retourne la page où aller.
 * $utilisateurC est un UtilisateurController.
 */
function demarrerSession($utilisateurC, array $user): string
{
    // 1) Déconnexion automatique de la session précédente (autre compte)
    if (!empty($_SESSION['user_id'])) {
        $utilisateurC->fermerSession((int) $_SESSION['user_id'], (string) ($_SESSION['session_token'] ?? ''));
    }
    $_SESSION = [];
    session_regenerate_id(true);                   // nouvel identifiant de session (sécurité)

    // 2) Nouvelle session (un seul navigateur à la fois par compte)
    $_SESSION['user_id']              = (int) $user['id'];  // utilisé par tous les modules
    $_SESSION['role']                 = $user['role'];      // 'ADMIN' ou 'AGRICULTEUR'
    $_SESSION['nom']                  = $user['nom'];
    $_SESSION['prenom']               = $user['prenom'];
    $_SESSION['session_token']        = $utilisateurC->ouvrirSession((int) $user['id']);
    $_SESSION['connexion_precedente'] = $user['derniereConnexion'] ?? null;   // affichée dans le profil
    $_SESSION['derniere_activite']    = time();
    $utilisateurC->enregistrerConnexion((int) $user['id']);

    setFlash('Bienvenue ' . $user['prenom'] . ' !');
    return $user['role'] === 'ADMIN' ? '../BackOffice/listeUtilisateurs.php' : 'profil.php';
}

/* ---------------------------------------------------------------------
 *  Vérification automatique du jeton CSRF sur toutes les requêtes POST
 * --------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();
}
